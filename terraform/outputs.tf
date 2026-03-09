output "alb_dns_name" {
  value = aws_lb.laravel_alb.dns_name
}

output "instance_public_ips" {
  value = aws_instance.laravel_server[*].public_ip
}

output "rds_endpoint" {
  value = aws_db_instance.laravel_db.endpoint
}
