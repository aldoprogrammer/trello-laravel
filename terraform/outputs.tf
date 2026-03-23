output "alb_dns_name" {
  value = aws_lb.laravel_alb.dns_name
}

output "instance_public_ips" {
  value = aws_instance.laravel_server[*].public_ip
}

output "rds_endpoint" {
  value     = aws_db_instance.laravel_db.endpoint
  sensitive = false
}

output "rds_replica_endpoint" {
  description = "Read replica host (use as DB_SLAVE_HOST in Laravel when enable_rds_read_replica = true)"
  value       = var.enable_rds_read_replica ? aws_db_instance.laravel_db_replica[0].address : null
}
