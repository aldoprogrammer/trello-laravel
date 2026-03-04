resource "aws_instance" "laravel_server" {
  ami           = "ami-0c55b159cbfafe1f0" # Ubuntu LTS
  instance_type = "t2.micro"

  tags = {
    Name = "Laravel-Prod-Server"
  }
}

resource "aws_security_group" "allow_web" {
  name        = "allow_web_traffic"
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
}
