# OpenGRC Docker Documentation

This document provides instructions for building and running OpenGRC using Docker.

## Building the Image

Build the Docker image with a simple command (no build arguments required):

```bash
docker build -t opengrc:latest .
```

## Running OpenGRC

### Basic Configuration

Run OpenGRC with minimal configuration (uses SQLite database):

```bash
docker run -d -p 8080:8080 \
  -e ADMIN_EMAIL=admin@example.com \
  -e ADMIN_PASSWORD=SecurePassword123 \
  --name opengrc-app \
  opengrc:latest
```

### Production Configuration with External Database

Run with MySQL/PostgreSQL database:

```bash
docker run -d -p 8080:8080 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=your-db-host \
  -e DB_PORT=3306 \
  -e DB_DATABASE=opengrc_production \
  -e DB_USERNAME=opengrc_user \
  -e DB_PASSWORD=your_db_password \
  -e ADMIN_EMAIL=admin@company.com \
  -e ADMIN_PASSWORD=SecureAdminPass123 \
  -e APP_NAME="Company GRC System" \
  -e APP_URL=https://grc.company.com \
  --name opengrc-app \
  opengrc:latest
```

### Configuration with S3 Storage

Run with AWS S3 for file storage:

```bash
docker run -d -p 8080:8080 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=db.company.com \
  -e DB_USERNAME=opengrc_user \
  -e DB_PASSWORD=db_password \
  -e ADMIN_EMAIL=admin@company.com \
  -e ADMIN_PASSWORD=AdminPass123 \
  -e APP_NAME="Company GRC" \
  -e APP_URL=https://grc.company.com \
  -e S3_ENABLED=true \
  -e AWS_BUCKET=my-opengrc-bucket \
  -e AWS_DEFAULT_REGION=us-east-1 \
  -e AWS_ACCESS_KEY_ID=AKIA... \
  -e AWS_SECRET_ACCESS_KEY=your_secret_key \
  --name opengrc-app \
  opengrc:latest
```

## Environment Variables

### Required Variables
- `ADMIN_PASSWORD` - Admin user password (minimum 8 characters)

### Database Configuration
- `DB_CONNECTION` - Database driver (`mysql`, `pgsql`, or `sqlite`) [default: `mysql`]
- `DB_HOST` - Database host [default: `127.0.0.1`]
- `DB_PORT` - Database port (auto-detected: 3306 for MySQL, 5432 for PostgreSQL)
- `DB_DATABASE` - Database name [default: `opengrc`]
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password

### Application Configuration
- `APP_NAME` - Application name [default: `OpenGRC`]
- `APP_URL` - Application URL [default: `https://opengrc.test`]
- `APP_KEY` - Laravel application key (auto-generated if not provided)
- `ADMIN_EMAIL` - Admin user email [default: `admin@example.com`]

### S3 Storage Configuration
- `S3_ENABLED` - Enable S3 storage (`true`/`false`) [default: `false`]
- `AWS_BUCKET` - S3 bucket name
- `AWS_DEFAULT_REGION` - AWS region
- `AWS_ACCESS_KEY_ID` - AWS access key
- `AWS_SECRET_ACCESS_KEY` - AWS secret key

### Runtime Control
- `RUN_DEPLOYMENT` - Force re-run deployment (`true`/`false`) [default: `false`]

## Docker Compose Example

Create a `docker-compose.yml` file for easier management:

```yaml
version: '3.8'

services:
  opengrc:
    build: .
    ports:
      - "8080:8080"
    environment:
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - DB_DATABASE=opengrc
      - DB_USERNAME=opengrc_user
      - DB_PASSWORD=secure_password
      - ADMIN_EMAIL=admin@company.com
      - ADMIN_PASSWORD=SecureAdminPass123
      - APP_NAME=Company GRC
      - APP_URL=https://grc.company.com
    depends_on:
      - db
    volumes:
      - opengrc_storage:/var/www/html/storage

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=opengrc
      - MYSQL_USER=opengrc_user
      - MYSQL_PASSWORD=secure_password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

volumes:
  opengrc_storage:
  mysql_data:
```

Run with Docker Compose:

```bash
docker-compose up -d
```

## Container Management

### View Logs
```bash
docker logs -f opengrc-app
```

### Access Container Shell
```bash
docker exec -it opengrc-app bash
```

### Stop Container
```bash
docker stop opengrc-app
```

### Remove Container
```bash
docker rm opengrc-app
```

### Force Re-deployment
```bash
docker run -d -p 8080:8080 \
  -e RUN_DEPLOYMENT=true \
  -e ADMIN_PASSWORD=YourPassword \
  [other environment variables...] \
  --name opengrc-app \
  opengrc:latest
```

## Accessing OpenGRC

Once the container is running:

1. **Web Interface**: http://localhost:8080
2. **Login**: Use the admin email and password you specified
3. **Health Check**: The container includes a health check that monitors the application status

## Troubleshooting

### Database Connection Issues
- Ensure database is accessible from the container
- Check that database credentials are correct
- For external databases, ensure the database server accepts connections from the container's IP

### Permission Issues
- The container runs as the `www-data` user for security
- Storage directories are automatically configured with proper permissions

### Memory/Performance
- For production use, consider setting memory limits:
  ```bash
  docker run --memory=2g --cpus=1.5 [other options...] opengrc:latest
  ```

### Logs
- Application logs are sent to stderr and visible via `docker logs`
- For persistent logging, mount a volume to `/var/www/html/storage/logs`

## Persistent Storage

To persist uploaded files and logs across container restarts:

```bash
docker run -d -p 8080:8080 \
  -v opengrc_storage:/var/www/html/storage \
  [other options...] \
  opengrc:latest
```

## Security Considerations

- Use strong passwords for `ADMIN_PASSWORD` and database credentials
- Consider using Docker secrets for sensitive environment variables in production
- Regularly update the base images by rebuilding
- Use HTTPS in production (configure reverse proxy like nginx)
- Restrict database access to only the OpenGRC container

## Production Deployment

For production deployments:

1. Use external managed databases (RDS, Cloud SQL, etc.)
2. Use cloud storage (S3, etc.) instead of local storage
3. Set up proper monitoring and logging
4. Use container orchestration (Kubernetes, Docker Swarm)
5. Implement proper backup strategies
6. Use secrets management systems
7. Set up SSL/TLS termination via reverse proxy