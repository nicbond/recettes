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

## Testing

### Setup the test database
Run the following commands inside your docker container:

- ``php bin/console doctrine:database:create --env=test``
- ``php bin/console doctrine:migrations:migrate --env=test -n``
- ``php bin/console doctrine:fixtures:load --env=test -n``

### Grant privileges to the test database (if needed)
If you get an "Access denied" error, run the following command:
- ``docker exec -it <mysql_container_name> mysql -u root -proot -e "GRANT ALL PRIVILEGES ON recettes_test.* TO 'dev'@'%'; FLUSH PRIVILEGES;"``

### Run the tests
- ``php bin/phpunit``

### Run a specific test file
- ``php bin/phpunit tests/Controller/Admin/CategoryControllerTest.php``

### Run a specific test method
- ``php bin/phpunit --filter testIndex``

### Run tests with detailed output
- ``php bin/phpunit --testdox``

### Test database behavior
The project uses `dama/doctrine-test-bundle` to ensure test isolation. Each test runs inside a transaction that is automatically rolled back after execution, meaning:

- The test database is always clean between test runs
- No data pollution between tests
- No need to purge the database between runs
- You can run `php bin/phpunit` as many times as you want without side effects