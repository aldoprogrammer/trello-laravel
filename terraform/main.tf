# ==========================================
# 1. AMI (set in terraform.tfvars — no ec2:DescribeImages or ssm:GetParameter)
# ==========================================

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
    path                = "/api/health"
    matcher             = "200"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 2
    unhealthy_threshold = 3
  }
  stickiness {
    type            = "lb_cookie"
    cookie_duration = 86400
    enabled         = true
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
  ami                    = var.ami_id
  instance_type          = var.instance_type
  subnet_id              = aws_subnet.public[count.index].id
  vpc_security_group_ids = [aws_security_group.laravel_sg.id]
  key_name               = var.ec2_key_name != "" ? var.ec2_key_name : null
  iam_instance_profile   = aws_iam_instance_profile.ec2_app.name

  depends_on = [
    time_sleep.ec2_after_instance_profile,
    aws_route_table_association.public,
  ]

  timeouts {
    create = "15m"
  }

  root_block_device {
    volume_size = 20
    volume_type = "gp3"
  }

  user_data = <<-EOF
              #!/bin/bash
              set -e
              export DEBIAN_FRONTEND=noninteractive
              apt-get update -y
              apt-get install -y nginx ruby-full wget curl ca-certificates gnupg unzip

              # Docker official repo (docker-compose-plugin is not in Ubuntu's default repos)
              install -m 0755 -d /etc/apt/keyrings
              curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
              chmod a+r /etc/apt/keyrings/docker.gpg
              echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list
              apt-get update -y
              apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

              systemctl enable docker
              usermod -aG docker ubuntu

              # AWS CLI v2
              curl -fsSL https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip -o /tmp/awscliv2.zip
              unzip -qo /tmp/awscliv2.zip -d /tmp
              /tmp/aws/install
              rm -rf /tmp/aws /tmp/awscliv2.zip

              systemctl start nginx
              systemctl enable nginx

              CODEDEPLOY_URL="https://aws-codedeploy-${data.aws_region.current.id}.s3.${data.aws_region.current.id}.amazonaws.com/latest/install"
              cd /tmp
              wget -q "$CODEDEPLOY_URL" -O install
              chmod +x ./install
              ./install auto
              systemctl enable codedeploy-agent
              EOF

  tags = {
    Name = "Laravel-Server-Dev-${count.index + 1}"
    App  = "laravel-trello"
    Env  = "development"
  }
}

# ==========================================
# 4b. PRODUCTION EC2 INSTANCES
# ==========================================
resource "aws_instance" "prod_server" {
  count                  = 2
  ami                    = var.ami_id
  instance_type          = var.instance_type
  subnet_id              = aws_subnet.public[count.index].id
  vpc_security_group_ids = [aws_security_group.laravel_sg.id]
  key_name               = var.ec2_key_name != "" ? var.ec2_key_name : null
  iam_instance_profile   = aws_iam_instance_profile.ec2_app.name

  depends_on = [
    time_sleep.ec2_after_instance_profile,
    aws_route_table_association.public,
  ]

  timeouts {
    create = "15m"
  }

  root_block_device {
    volume_size = 20
    volume_type = "gp3"
  }

  user_data = <<-EOF
              #!/bin/bash
              set -e
              export DEBIAN_FRONTEND=noninteractive
              apt-get update -y
              apt-get install -y nginx ruby-full wget curl ca-certificates gnupg unzip

              install -m 0755 -d /etc/apt/keyrings
              curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
              chmod a+r /etc/apt/keyrings/docker.gpg
              echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list
              apt-get update -y
              apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

              systemctl enable docker
              usermod -aG docker ubuntu

              curl -fsSL https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip -o /tmp/awscliv2.zip
              unzip -qo /tmp/awscliv2.zip -d /tmp
              /tmp/aws/install
              rm -rf /tmp/aws /tmp/awscliv2.zip

              echo "prod" > /home/ubuntu/.deploy_env
              chown ubuntu:ubuntu /home/ubuntu/.deploy_env

              systemctl start nginx
              systemctl enable nginx

              CODEDEPLOY_URL="https://aws-codedeploy-${data.aws_region.current.id}.s3.${data.aws_region.current.id}.amazonaws.com/latest/install"
              cd /tmp
              wget -q "$CODEDEPLOY_URL" -O install
              chmod +x ./install
              ./install auto
              systemctl enable codedeploy-agent
              EOF

  tags = {
    Name = "Laravel-Server-Prod-${count.index + 1}"
    App  = "laravel-trello-prod"
    Env  = "production"
  }
}

# ==========================================
# 3b. PRODUCTION LOAD BALANCER
# ==========================================
resource "aws_lb" "prod_alb" {
  name               = "laravel-prod-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = aws_subnet.public[*].id
}

resource "aws_lb_target_group" "prod_tg" {
  name     = "laravel-prod-tg"
  port     = 80
  protocol = "HTTP"
  vpc_id   = aws_vpc.main.id
  health_check {
    path                = "/api/health"
    matcher             = "200"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 2
    unhealthy_threshold = 3
  }
  stickiness {
    type            = "lb_cookie"
    cookie_duration = 86400
    enabled         = true
  }
}

resource "aws_lb_listener" "prod_http" {
  load_balancer_arn = aws_lb.prod_alb.arn
  port              = "80"
  protocol          = "HTTP"
  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.prod_tg.arn
  }
}

resource "aws_lb_target_group_attachment" "prod_attachment" {
  count            = 2
  target_group_arn = aws_lb_target_group.prod_tg.arn
  target_id        = aws_instance.prod_server[count.index].id
  port             = 80
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
  allocated_storage          = 20
  storage_type               = "gp3"
  engine                     = "mysql"
  engine_version             = "8.0"
  instance_class             = "db.t3.micro"
  db_name                    = "laravel_trello"
  username                   = "admin"
  password                   = var.db_password
  parameter_group_name       = "default.mysql8.0"
  db_subnet_group_name       = aws_db_subnet_group.laravel_db_subnet.name
  vpc_security_group_ids     = [aws_security_group.db_sg.id]
  skip_final_snapshot        = true
  publicly_accessible        = false
  backup_retention_period    = var.enable_rds_read_replica ? 7 : 0
  apply_immediately          = true
  auto_minor_version_upgrade = true
}

resource "aws_db_instance" "laravel_db_replica" {
  count = var.enable_rds_read_replica ? 1 : 0

  identifier             = "laravel-trello-replica"
  replicate_source_db    = aws_db_instance.laravel_db.identifier
  instance_class         = "db.t3.micro"
  skip_final_snapshot    = true
  vpc_security_group_ids = [aws_security_group.db_sg.id]
  publicly_accessible    = false
}
