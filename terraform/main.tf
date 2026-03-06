# 1. PROVIDER: Definisi AWS
provider "aws" {
  region = "us-east-1"
}

# 2. VARIABLE: Biar fleksibel ganti spek
variable "instance_type" {
  default = "t3.micro"
}

# 3. SECURITY GROUP: Pagar Keamanan
resource "aws_security_group" "laravel_sg" {
  name        = "laravel-prod-sg"
  description = "Allow HTTP, HTTPS, and SSH"

  # Port 80 (Web Biasa)
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Port 443 (Web Aman/SSL)
  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Port 22 (SSH - Akses Remote)
  # Di industri, cidr_blocks harusnya IP kantor/pribadi kamu saja
  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Izin Keluar (Egress): Biar server bisa download update/docker image
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# 4. EC2 INSTANCE: Server Utama
resource "aws_instance" "laravel_server" {
  ami           = "ami-0c55b159cbfafe1f0" # Ubuntu 22.04 LTS
  instance_type = var.instance_type
  vpc_security_group_ids = [aws_security_group.laravel_sg.id]

  # USER DATA: Skrip Auto-Install saat pertama kali nyala (Automation)
  user_data = <<-EOF
              #!/bin/bash
              apt-get update
              apt-get install -y docker.io docker-compose
              systemctl start docker
              systemctl enable docker
              EOF

  tags = {
    Name        = "Laravel-T3-Production"
    ManagedBy   = "Terraform"
    Environment = "Prod"
  }
}
