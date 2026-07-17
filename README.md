## Recipes

Symfony 7 application for managing cooking recipes.

This personal project was created to experiment with modern Symfony best practices and explore architectural patterns used in professional projects.

## Features

- Recipe management
- Full CRUD operations
- Form validation
- Email notifications
- SMS notifications through a notification factory
- Service-oriented architecture

## Tech Stack

- PHP 8.3
- Symfony 7
- Doctrine ORM
- MySQL
- Docker
- Twig
- PHPUnit
- PHPStan
- PHP-CS-Fixer
- GrumPHP

## Code Quality

The project integrates several tools to ensure code quality :

- PHPStan
- PHP-CS-Fixer
- GrumPHP
- PHPUnit

## Architecture

The project demonstrates:

- Notification Factory supporting Email and SMS delivery
- Factory Pattern
- Symfony Dependency Injection
- Service-oriented architecture
- SOLID principles

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