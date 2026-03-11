#!/bin/bash
# Pindah ke folder project
cd /home/ubuntu/trello-laravel

# Paksa pake Docker V2 (Spasi)
sudo docker compose down
sudo docker compose up -d --build --force-recreate
