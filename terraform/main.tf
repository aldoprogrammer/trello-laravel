# ==========================================
# 1. DATA SOURCES & AMI
# ==========================================
data "aws_ami" "ubuntu_noble" {
  most_recent = true
  owners      = ["099720109477"]
  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*"]
  }
}

# ==========================================
# 2. SECURITY GROUPS (The Security Layers)
# ==========================================

# SG untuk Load Balancer (Pintu masuk dari internet)
resource "aws_security_group" "alb_sg" {
  name   = "alb-sg"
  vpc_id = aws_vpc.main.id
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# SG untuk EC2 Laravel (Hanya terima traffic dari ALB)
resource "aws_security_group" "laravel_sg" {
  name   = "laravel-prod-sg"
  vpc_id = aws_vpc.main.id

  ingress {
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb_sg.id]
  }
  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# SG untuk RDS Database (Hanya terima traffic dari EC2 Laravel)
resource "aws_security_group" "db_sg" {
  name   = "laravel-db-sg"
  vpc_id = aws_vpc.main.id

  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.laravel_sg.id]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# ==========================================
# 3. LOAD BALANCER (The Traffic Manager)
# ==========================================
resource "aws_lb" "laravel_alb" {
  name               = "laravel-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = aws_subnet.public[*].id
}

resource "aws_lb_target_group" "laravel_tg" {
  name     = "laravel-tg"
  port     = 80
  protocol = "HTTP"
  vpc_id   = aws_vpc.main.id
  health_check {
    path                = "/"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 2
    unhealthy_threshold = 2
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.laravel_alb.arn
  port              = "80"
  protocol          = "HTTP"
  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.laravel_tg.arn
  }
}

resource "aws_lb_target_group_attachment" "laravel_attachment" {
  count            = 2
  target_group_arn = aws_lb_target_group.laravel_tg.arn
  target_id        = aws_instance.laravel_server[count.index].id
  port             = 80
}

# ==========================================
# 4. EC2 INSTANCES (The Baristas)
# ==========================================
resource "aws_instance" "laravel_server" {
  count                  = 2
  ami                    = data.aws_ami.ubuntu_noble.id
  instance_type          = var.instance_type
  subnet_id              = aws_subnet.public[count.index].id
  vpc_security_group_ids = [aws_security_group.laravel_sg.id]
  key_name               = "laravel-trello"

  root_block_device {
    volume_size = 20
    volume_type = "gp3"
  }

  user_data = <<-EOF
              #!/bin/bash
              sudo apt-get update -y
              sudo apt-get install -y nginx docker.io docker-compose
              sudo systemctl start nginx
              sudo systemctl enable nginx
              EOF

  tags = {
    Name = "Laravel-Server-Prod-${count.index + 1}"
  }
}

# ==========================================
# 5. RDS DATABASE (The Vault)
# ==========================================
resource "aws_db_subnet_group" "laravel_db_subnet" {
  name       = "laravel-db-subnet-group"
  subnet_ids = aws_subnet.public[*].id
  tags       = { Name = "Laravel-DB-Subnet-Group" }
}

resource "aws_db_instance" "laravel_db" {
  allocated_storage    = 20
  storage_type         = "gp3"
  engine               = "mysql"
  engine_version       = "8.0"
  instance_class       = "db.t3.micro"
  db_name              = "laravel_trello"
  username             = "admin"
  password             = "PasswordRahasia123"
  parameter_group_name = "default.mysql8.0"
  db_subnet_group_name = aws_db_subnet_group.laravel_db_subnet.name
  vpc_security_group_ids = [aws_security_group.db_sg.id]
  skip_final_snapshot  = true
  publicly_accessible  = false # SANGAT PENTING: Private!
}
