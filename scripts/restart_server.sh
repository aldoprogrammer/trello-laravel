#!/bin/bash
cd /home/ubuntu/trello-laravel
# Kasih izin eksekusi biar aman
chmod -R 755 /home/ubuntu/trello-laravel
# Restart docker atau service lo biar kodingan baru kebaca
docker-compose up -d --build
