FROM php:8.3-apache AS base

# --------------
# Install needed Debian/Ubuntu packages
# ------------------------------------------------
RUN apt-get clean && apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    zip \
    unzip \
    mysql-client \
    postgresql-client

RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql bcmath intl zip gd

##############################
# 1) Stage: Build everything
##############################

FROM base AS build

# Install nodejs and npm

ENV NODE_VERSION=20.19.1

RUN apt-get update && apt-get install -y gnupg2

RUN ARCH= && dpkgArch="$(dpkg --print-architecture)" \
  && case "${dpkgArch##*-}" in \
    amd64) ARCH='x64';; \
    ppc64el) ARCH='ppc64le';; \
    s390x) ARCH='s390x';; \
    arm64) ARCH='arm64';; \
    armhf) ARCH='armv7l';; \
    i386) ARCH='x86';; \
    *) echo "unsupported architecture"; exit 1 ;; \
  esac \
  # use pre-existing gpg directory, see https://github.com/nodejs/docker-node/pull/1895#issuecomment-1550389150
  && export GNUPGHOME="$(mktemp -d)" \
  # gpg keys listed at https://github.com/nodejs/node#release-keys
  && set -ex \
  && for key in \
    C0D6248439F1D5604AAFFB4021D900FFDB233756 \
    DD792F5973C6DE52C432CBDAC77ABFA00DDBF2B7 \
    CC68F5A3106FF448322E48ED27F5E38D5B0A215F \
    8FCCA13FEF1D0C2E91008E09770F7A9A5AE15600 \
    890C08DB8579162FEE0DF9DB8BEAB4DFCF555EF4 \
    C82FA3AE1CBEDC6BE46B9360C43CEC45C17AB93C \
    108F52B48DB57BB0CC439B2997B01419BD92F80A \
    A363A499291CBBC940DD62E41F10027AF002F8B0 \
  ; do \
      gpg --batch --keyserver hkps://keys.openpgp.org --recv-keys "$key" || \
      gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$key" ; \
  done \
  && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-$ARCH.tar.xz" \
  && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/SHASUMS256.txt.asc" \
  && gpg --batch --decrypt --output SHASUMS256.txt SHASUMS256.txt.asc \
  && gpgconf --kill all \
  && rm -rf "$GNUPGHOME" \
  && grep " node-v$NODE_VERSION-linux-$ARCH.tar.xz\$" SHASUMS256.txt | sha256sum -c - \
  && tar -xJf "node-v$NODE_VERSION-linux-$ARCH.tar.xz" -C /usr/local --strip-components=1 --no-same-owner \
  && rm "node-v$NODE_VERSION-linux-$ARCH.tar.xz" SHASUMS256.txt.asc SHASUMS256.txt \
  && ln -s /usr/local/bin/node /usr/local/bin/nodejs \
  # smoke tests
  && node --version \
  && npm --version \
  && rm -rf /tmp/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

COPY composer.json composer.lock /var/www/html/

# Install Composer dependencies
RUN composer install --no-scripts

# Copy application code
COPY . .

# Install Composer dependencies (including dev dependencies) and run initial setup
RUN composer update 
#&& php artisan opengrc:install --unattended

########################################
# 2) Stage: Final - Production runtime
########################################
FROM base AS production

# Set default production environment (can be overridden at runtime)
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV LOG_LEVEL=warning

# Set default database values (can be overridden at runtime)
ENV DB_CONNECTION=mysql
ENV DB_HOST=127.0.0.1
ENV DB_PORT=
ENV DB_DATABASE=opengrc
ENV DB_USERNAME=
ENV DB_PASSWORD=

# Set default application values (can be overridden at runtime)
ENV APP_NAME="OpenGRC"
ENV APP_URL=https://opengrc.test
ENV APP_KEY=
ENV ADMIN_EMAIL=admin@example.com
ENV ADMIN_PASSWORD=

# Set default S3 values (can be overridden at runtime)
ENV S3_ENABLED=false
ENV FILESYSTEM_DISK=local
ENV AWS_BUCKET=
ENV AWS_DEFAULT_REGION=
ENV AWS_ACCESS_KEY_ID=
ENV AWS_SECRET_ACCESS_KEY=

# Copy Composer binary (needed to remove dev dependencies and cache)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy entire Laravel app (including vendor) from build stage
COPY --from=build /var/www/html .

# Remove PHP development dependencies and clear Composer cache
RUN composer install --no-dev --optimize-autoloader && \
    composer clear-cache && \
    rm -rf /root/.composer/cache

# Remove node_modules
RUN rm -rf /var/www/html/node_modules

# Make sure storage and bootstrap/cache are writable
RUN mkdir -p storage/framework/cache/data bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /var/www/html \
    && chmod -R 775 storage bootstrap/cache /var/www/html
    
# Create database directory and ensure correct permissions
RUN mkdir -p /var/www/html/database \
    && touch /var/www/html/database/opengrc.sqlite \
    && chown -R www-data:www-data /var/www/html/database

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Listen on port 8080 instead of 80
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
EXPOSE 8080

# Update the default vhost to point to /var/www/html/public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Replace the VirtualHost port in 000-default.conf
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides and full access
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n" >> /etc/apache2/apache2.conf

# Set a server name
RUN echo "ServerName 0.0.0.0" >> /etc/apache2/apache2.conf

#
#
# Create deployment startup script
#
#
RUN cat > /usr/local/bin/deploy-and-start.sh << 'EOF'
#!/bin/bash
set -e

echo "Starting OpenGRC deployment..."

# Set default port based on database driver if not specified
if [ -z "$DB_PORT" ]; then
    if [ "$DB_CONNECTION" = "mysql" ]; then
        export DB_PORT=3306
    elif [ "$DB_CONNECTION" = "pgsql" ]; then
        export DB_PORT=5432
    fi
fi

# Wait for database connection if external database
if [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Waiting for database connection..."
    until timeout 1 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null; do
        echo "Database not ready, waiting..."
        sleep 2
    done
    echo "Database connection established!"
fi

# Function to check if users table exists
check_users_table() {
    if [ "$DB_CONNECTION" = "sqlite" ]; then
        php -r "
        try {
            \$pdo = new PDO('sqlite:/var/www/html/database/opengrc.sqlite');
            \$result = \$pdo->query(\"SELECT name FROM sqlite_master WHERE type='table' AND name='users'\");
            exit(\$result->rowCount() > 0 ? 0 : 1);
        } catch (Exception \$e) {
            exit(1);
        }"
    else
        php -r "
        try {
            \$port = getenv('DB_PORT') ?: (getenv('DB_CONNECTION') === 'mysql' ? '3306' : '5432');
            \$dsn = getenv('DB_CONNECTION') . ':host=' . getenv('DB_HOST') . ';port=' . \$port . ';dbname=' . getenv('DB_DATABASE');
            \$pdo = new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            if (getenv('DB_CONNECTION') === 'mysql') {
                \$result = \$pdo->query(\"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '\" . getenv('DB_DATABASE') . \"' AND table_name = 'users'\");
            } else {
                \$result = \$pdo->query(\"SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'users'\");
            }
            \$count = \$result->fetchColumn();
            exit(\$count > 0 ? 0 : 1);
        } catch (Exception \$e) {
            exit(1);
        }"
    fi
}

# Check if deployment should run (if ADMIN_PASSWORD is set and users table doesn't exist or RUN_DEPLOYMENT=true)
if [ -n "$ADMIN_PASSWORD" ] && (! check_users_table || [ "$RUN_DEPLOYMENT" = "true" ]); then
    echo "Users table not found or RUN_DEPLOYMENT=true. Running OpenGRC deployment..."
    
    # Build deployment command with available parameters
    DEPLOY_CMD="php artisan opengrc:deploy --accept"
    
    # Add database parameters
    DEPLOY_CMD="$DEPLOY_CMD --db-driver=$DB_CONNECTION"
    DEPLOY_CMD="$DEPLOY_CMD --db-host=$DB_HOST"
    DEPLOY_CMD="$DEPLOY_CMD --db-port=$DB_PORT"
    DEPLOY_CMD="$DEPLOY_CMD --db-name=$DB_DATABASE"
    DEPLOY_CMD="$DEPLOY_CMD --db-user=$DB_USERNAME"
    DEPLOY_CMD="$DEPLOY_CMD --db-password=$DB_PASSWORD"
    
    # Add application parameters
    DEPLOY_CMD="$DEPLOY_CMD --admin-email=$ADMIN_EMAIL"
    DEPLOY_CMD="$DEPLOY_CMD --admin-password=$ADMIN_PASSWORD"
    DEPLOY_CMD="$DEPLOY_CMD --site-name=$APP_NAME"
    DEPLOY_CMD="$DEPLOY_CMD --site-url=$APP_URL"
    
    # Add app key if provided
    if [ -n "$APP_KEY" ]; then
        DEPLOY_CMD="$DEPLOY_CMD --app-key=$APP_KEY"
    fi
    
    # Add S3 parameters if enabled
    if [ "$S3_ENABLED" = "true" ] && [ -n "$AWS_BUCKET" ]; then
        DEPLOY_CMD="$DEPLOY_CMD --s3"
        DEPLOY_CMD="$DEPLOY_CMD --s3-bucket=$AWS_BUCKET"
        DEPLOY_CMD="$DEPLOY_CMD --s3-region=$AWS_DEFAULT_REGION"
        DEPLOY_CMD="$DEPLOY_CMD --s3-key=$AWS_ACCESS_KEY_ID"
        DEPLOY_CMD="$DEPLOY_CMD --s3-secret=$AWS_SECRET_ACCESS_KEY"
    fi
    
    echo "Executing: $DEPLOY_CMD"
    eval $DEPLOY_CMD
    
    echo "Deployment completed successfully!"
else
    echo "Users table exists - skipping deployment, running standard startup..."
    
    # Standard Laravel startup
    if [ -f "/var/www/html/.env" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        
        # Run migrations in case of updates (safe to run)
        php artisan migrate --force
    else
        echo "Warning: No .env file found and no deployment parameters provided"
        echo "Creating basic .env from template..."
        if [ -f "/var/www/html/.env.example" ]; then
            cp /var/www/html/.env.example /var/www/html/.env
            php artisan key:generate
        fi
    fi
fi

# Start Apache
echo "Starting Apache web server..."
exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/deploy-and-start.sh

# Set proper ownership for startup script
RUN chown www-data:www-data /usr/local/bin/deploy-and-start.sh

USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

# Start with deployment script
CMD ["/usr/local/bin/deploy-and-start.sh"]

