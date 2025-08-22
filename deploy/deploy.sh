#!/usr/bin/env bash
set -euo pipefail

cmd=(php artisan opengrc:deploy
  --db-driver="${DB_DRIVER}"
  --db-host="${DB_HOST}"
  --db-port="${DB_PORT}"
  --db-name="${DB_NAME:-opengrc}"
  --db-user="${DB_USER}"
  --db-password="${DB_PASSWORD}"
  --admin-email="${ADMIN_EMAIL:-admin@example.com}"
  --admin-password="${ADMIN_PASSWORD}"
  --site-name="${SITE_NAME:-OpenGRC}"
  --site-url="${SITE_URL}"
  --accept
)

if [[ "${S3_ENABLED:-false}" == "true" ]]; then
  cmd+=( --s3 --s3-bucket="${AWS_BUCKET}" --s3-region="${AWS_DEFAULT_REGION}" )
  # Only if not using an instance role:
  if [[ -n "${S3_KEY:-}" && -n "${S3_SECRET:-}" ]]; then
    cmd+=( --s3-key="${S3_KEY}" --s3-secret="${S3_SECRET}" )
  fi
fi

echo "Running: ${cmd[*]}"
"${cmd[@]}"
