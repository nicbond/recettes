# Recipes

Symfony 7 application for managing cooking recipes.

This personal project was created to experiment with modern Symfony best practices and explore architectural patterns used in professional projects.

## Features

* Recipe management (CRUD, online/offline status, tags, ingredients, quantities)
* Category and tag management
* Ingredient creation on-the-fly via Tom Select autocomplete
* Recipe image upload, preview and WebP conversion
* Turbo-powered modal forms (edit without page reload)
* Pagination and sortable listings
* Email and SMS notifications
* Responsive administration back-office
* Business rules enforced at application level (e.g. protected deletions)

## Tech Stack

* PHP 8.4
* Symfony 7
* Doctrine ORM
* MySQL
* Docker / Docker Compose
* Twig
* Symfony UX Autocomplete
* Symfony UX Turbo
* Bootstrap 5
* Font Awesome
* PHPUnit
* PHPStan
* PHP-CS-Fixer
* Twig CS Fixer
* GrumPHP
* Mailpit

> See [TECHNICAL.md](TECHNICAL.md) for a detailed description of the architecture and implementation choices.

## Requirements

* Docker
* Docker Compose
* Make

## Installation

* Replace all occurrences of `symfony_docker` with your project name
* Edit the `PROJECT_PORT` variable in `.env` to set the application port
* `make .env.local`
* Edit your `.env.local`
* `make install` or `make reset`

## Useful commands

### Find a free port on your machine

```bash
python3 -c "import socket; s=socket.socket(); s.bind(('',0)); print(s.getsockname()[1]); s.close()"
```

### Initialize all components for a web application

```bash
composer require webapp
```

### Compile assets

```bash
php bin/console asset-map:compile
```

### Database migrations

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Load fixtures

```bash
php bin/console doctrine:fixtures:load
```

## Testing

```bash
php bin/phpunit
php bin/phpunit tests/Controller/Admin/CategoryControllerTest.php
php bin/phpunit --filter testIndex
php bin/phpunit --testdox
```

### Setup the test database

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test -n
php bin/console doctrine:fixtures:load --env=test -n
```

### Grant privileges to the test database (if needed)

```bash
docker exec -it <mysql_container_name> mysql -u root -proot -e "GRANT ALL PRIVILEGES ON recettes_test.* TO 'dev'@'%'; FLUSH PRIVILEGES;"
```

## Code Quality

```bash
vendor/bin/phpstan analyse src --level=9
vendor/bin/php-cs-fixer fix
vendor/bin/twig-cs-fixer lint templates/
vendor/bin/twig-cs-fixer fix templates/
vendor/bin/grumphp run
```

## Sending emails

Mailpit captures outgoing emails locally without sending them to real recipients.

* SMTP: `localhost:1025`
* Web interface: http://localhost:8025/

### Manual installation (macOS)

```bash
curl -LO https://github.com/axllent/mailpit/releases/latest/download/mailpit-darwin-arm64.tar.gz
tar -xvzf mailpit-darwin-arm64.tar.gz
chmod +x mailpit
```

## Docker

The development environment includes:

* PHP 8.4 / PHP-FPM
* Apache
* MySQL
* phpMyAdmin
* Mailpit
