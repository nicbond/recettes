# Symfony - Docker
This project will init a Symfony project with Docker using PHP 8.3 and Symfony 7.3.5

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

## Command to find a free port on your machine
python3 -c "import socket; s=socket.socket(); s.bind(('',0)); print(s.getsockname()[1]); s.close()"

## Command used to initialize all components necessary for creating a web application
- ``composer require webapp``

## Sending emails part
- Download mailpit to bin directory: ``curl -LO https://github.com/axllent/mailpit/releases/latest/download/mailpit-darwin-arm64.tar.gz``
- Then extract with this command: ``tar -xvzf mailpit-darwin-arm64.tar.gz``
- Make the binary file executable: ``chmod +x mailpit``
- Launch Mailpit from directory recettes/bin and this url will available which simulates a web client: ``http://localhost:8025/``

## To run GrumPHP locally, type the following command in your docker container:
- ``vendor/bin/grumphp run``

## To run only phpstan, type the following command in your docker container:
- ``vendor/bin/phpstan analyse src --level=1``