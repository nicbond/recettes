# Symfony - Docker
This project will init a Symfony project with Docker

## Requirements
- Docker
- Docker-compose
- Make

## Installation
- Replace all word ``symfony_docker`` by your project name
- Edit variable ``PROJECT_PORT`` port in ``.env``
- ``make .env.local``
- Edit your ``.env.local``
- ``make install`` or ``make reset`` :)

## Commande pour trouver un port libre sur sa machine
python3 -c "import socket; s=socket.socket(); s.bind(('',0)); print(s.getsockname()[1]); s.close()"
