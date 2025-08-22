output "service_url" {
  value       = aws_apprunner_service.this.service_url
  description = "Public URL of the App Runner service"
}

output "github_connection_status" {
  value       = aws_apprunner_connection.github.status
  description = "Expect PENDING_HANDSHAKE on first apply; complete the OAuth in console"
}

output "rds_endpoint" {
  value       = aws_db_instance.this.address
  description = "RDS writer endpoint"
}

# Intentionally do NOT output secret values.
output "secrets_created" {
  value = [
    aws_secretsmanager_secret.app_key.name,
    aws_secretsmanager_secret.db_user.name,
    aws_secretsmanager_secret.db_pass.name,
    aws_secretsmanager_secret.admin_pass.name
  ]
  description = "Secrets Manager secret names created"
}
