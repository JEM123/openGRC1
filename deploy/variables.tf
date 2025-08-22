variable "region"            { type = string  default = "us-east-1" }
variable "site"              { type = string }                           # e.g., "prod"
variable "repo_url"          { type = string default = "https://github.com/LeeMangold/OpenGRC" }
variable "branch"            { type = string default = "main" }
variable "auto_deploy"       { type = bool   default = true }

variable "db_engine"         { type = string default = "postgres" }      # "postgres" or "mysql"
variable "db_instance_class" { type = string default = "db.t4g.micro" }
variable "db_name"           { type = string default = "opengrc" }
variable "db_username"       { type = string default = "opengrc" }
variable "db_allocated_gb"   { type = number default = 20 }

variable "admin_email"       { type = string default = "admin@example.com" }
variable "s3_enabled"        { type = bool   default = true }

# Optional: pass your own APP_KEY/ADMIN_PASSWORD instead of randoms
variable "app_key_override"  { type = string default = "" }
variable "admin_pass_override" { type = string default = "" }
