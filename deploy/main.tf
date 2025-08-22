locals {
  db_port       = var.db_engine == "mysql" ? 3306 : 5432
  db_driver     = var.db_engine == "mysql" ? "mysql" : "pgsql"
  bucket_name   = "opengrc-${var.site}-files"
  service_name  = "opengrc-${var.site}"
  site_url      = "https://opengrc.${var.site}.example.com"
  site_name     = "OpenGRC"
}

# --- Default VPC & default subnets ---
data "aws_vpc" "default" {
  default = true
}

data "aws_subnets" "default_vpc_default_az" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
  filter {
    name   = "default-for-az"
    values = ["true"]
  }
}

# --- Security groups ---
resource "aws_security_group" "apprunner_egress" {
  name        = "apprunner-egress-${var.site}"
  description = "Egress from App Runner VPC connector"
  vpc_id      = data.aws_vpc.default.id

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group" "rds" {
  name        = "rds-${var.site}"
  description = "RDS for ${var.site}"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port                = local.db_port
    to_port                  = local.db_port
    protocol                 = "tcp"
    security_groups          = [aws_security_group.apprunner_egress.id]
    description              = "App Runner VPC connector"
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# --- RDS subnet group & instance ---
resource "aws_db_subnet_group" "this" {
  name       = "rds-${var.site}"
  subnet_ids = data.aws_subnets.default_vpc_default_az.ids
}

resource "random_password" "db_password" {
  length  = 24
  special = false
}

resource "aws_db_instance" "this" {
  identifier              = "opengrc-${var.site}"
  engine                  = var.db_engine
  instance_class          = var.db_instance_class
  username                = var.db_username
  password                = random_password.db_password.result
  allocated_storage       = var.db_allocated_gb
  db_subnet_group_name    = aws_db_subnet_group.this.name
  vpc_security_group_ids  = [aws_security_group.rds.id]
  port                    = local.db_port
  publicly_accessible     = false
  skip_final_snapshot     = true
}

# --- S3 bucket for uploads ---
resource "aws_s3_bucket" "files" {
  count  = var.s3_enabled ? 1 : 0
  bucket = local.bucket_name
}

resource "aws_s3_bucket_public_access_block" "files" {
  count  = var.s3_enabled ? 1 : 0
  bucket = aws_s3_bucket.files[0].id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# --- Secrets (APP_KEY, DB creds, Admin password) ---
resource "random_password" "admin_password" {
  length  = 18
  special = false
}

resource "random_password" "app_key_raw" {
  length  = 32
  special = true
}

locals {
  app_key_value     = var.app_key_override != "" ? var.app_key_override : format("base64:%s", base64encode(random_password.app_key_raw.result))
  admin_pass_value  = var.admin_pass_override != "" ? var.admin_pass_override : random_password.admin_password.result
}

resource "aws_secretsmanager_secret" "app_key" {
  name = "opengrc/${var.site}/app_key"
}

resource "aws_secretsmanager_secret_version" "app_key" {
  secret_id     = aws_secretsmanager_secret.app_key.id
  secret_string = local.app_key_value
}

resource "aws_secretsmanager_secret" "db_user" {
  name = "opengrc/${var.site}/db/username"
}

resource "aws_secretsmanager_secret_version" "db_user" {
  secret_id     = aws_secretsmanager_secret.db_user.id
  secret_string = var.db_username
}

resource "aws_secretsmanager_secret" "db_pass" {
  name = "opengrc/${var.site}/db/password"
}

resource "aws_secretsmanager_secret_version" "db_pass" {
  secret_id     = aws_secretsmanager_secret.db_pass.id
  secret_string = random_password.db_password.result
}

resource "aws_secretsmanager_secret" "admin_pass" {
  name = "opengrc/${var.site}/admin/password"
}

resource "aws_secretsmanager_secret_version" "admin_pass" {
  secret_id     = aws_secretsmanager_secret.admin_pass.id
  secret_string = local.admin_pass_value
}

# --- IAM role for App Runner instances ---
data "aws_iam_policy_document" "apprunner_trust" {
  statement {
    effect = "Allow"
    principals {
      type        = "Service"
      identifiers = ["apprunner.amazonaws.com"]
    }
    actions = ["sts:AssumeRole"]
  }
}

resource "aws_iam_role" "apprunner_instance" {
  name               = "AppRunnerOpenGRC-${var.site}-role"
  assume_role_policy = data.aws_iam_policy_document.apprunner_trust.json
}

data "aws_iam_policy_document" "apprunner_inline" {
  statement {
    effect = "Allow"
    actions = ["secretsmanager:GetSecretValue"]
    resources = [
      aws_secretsmanager_secret.app_key.arn,
      aws_secretsmanager_secret.db_user.arn,
      aws_secretsmanager_secret.db_pass.arn,
      aws_secretsmanager_secret.admin_pass.arn
    ]
  }

  dynamic "statement" {
    for_each = var.s3_enabled ? [1] : []
    content {
      effect = "Allow"
      actions = ["s3:PutObject","s3:GetObject","s3:ListBucket","s3:DeleteObject"]
      resources = [
        aws_s3_bucket.files[0].arn,
        "${aws_s3_bucket.files[0].arn}/*"
      ]
    }
  }
}

resource "aws_iam_role_policy" "apprunner_inline" {
  role   = aws_iam_role.apprunner_instance.id
  policy = data.aws_iam_policy_document.apprunner_inline.json
}

# --- App Runner VPC connector ---
resource "aws_apprunner_vpc_connector" "this" {
  vpc_connector_name = "apprunner-vpc-${var.site}"
  subnets            = data.aws_subnets.default_vpc_default_az.ids
  security_groups    = [aws_security_group.apprunner_egress.id]
}

# --- App Runner GitHub connection (one-time handshake needed) ---
resource "aws_apprunner_connection" "github" {
  connection_name = "github-${var.site}"
  provider_type   = "GITHUB"
}

# --- App Runner service from GitHub repo ---
resource "aws_apprunner_service" "this" {
  service_name = local.service_name

  source_configuration {
    auto_deployments_enabled = var.auto_deploy

    authentication_configuration {
      connection_arn = aws_apprunner_connection.github.arn
    }

    code_repository {
      repository_url = var.repo_url
      source_code_version {
        type  = "BRANCH"
        value = var.branch
      }

      code_configuration {
        configuration_source = "API"
        code_configuration_values {
          runtime        = "PHP_83"
          port           = "8080"
          build_command  = "composer update --no-dev --optimize-autoloader"
          start_command  = "bash -lc './deploy/deploy.sh && ./deploy/startup.sh'"

          runtime_environment_variables = {
            DB_DRIVER          = local.db_driver
            DB_HOST            = aws_db_instance.this.address
            DB_PORT            = tostring(local.db_port)
            DB_NAME            = var.db_name
            SITE_NAME          = local.site_name
            SITE_URL           = local.site_url
            ADMIN_EMAIL        = var.admin_email
            S3_ENABLED         = var.s3_enabled ? "true" : "false"
            AWS_BUCKET         = var.s3_enabled ? local.bucket_name : ""
            AWS_DEFAULT_REGION = var.region
          }

          runtime_environment_secrets = {
            DB_USER        = aws_secretsmanager_secret.db_user.arn
            DB_PASSWORD    = aws_secretsmanager_secret.db_pass.arn
            ADMIN_PASSWORD = aws_secretsmanager_secret.admin_pass.arn
            APP_KEY        = aws_secretsmanager_secret.app_key.arn
          }
        }
      }
    }
  }

  instance_configuration {
    cpu               = "1 vCPU"
    memory            = "2 GB"
    instance_role_arn = aws_iam_role.apprunner_instance.arn
  }

  network_configuration {
    egress_configuration {
      egress_type       = "VPC"
      vpc_connector_arn = aws_apprunner_vpc_connector.this.arn
    }
    ingress_configuration {
      is_publicly_accessible = true
    }
  }
}
