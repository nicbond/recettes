## Recipes

Symfony 7 application for managing cooking recipes.

This personal project was created to experiment with modern Symfony best practices and explore architectural patterns used in professional projects.

## Features

- Recipe management
- Category management
- Full CRUD operations
- Form validation
- Recipe image upload and preview
- Image validation (JPEG, PNG and WebP)
- Image resizing and WebP support
- Category autocomplete with Symfony UX Autocomplete
- Pagination and sortable recipe listings
- Email notifications
- SMS notifications through a notification factory
- Service-oriented architecture
- Responsive administration back-office
- Turbo-powered modal forms

## Tech Stack

- PHP 8.4
- Symfony 7
- Doctrine ORM
- MySQL
- Docker / Docker Compose
- Twig
- Symfony UX Autocomplete
- Symfony UX Turbo
- Bootstrap 5
- Font Awesome
- PHPUnit
- PHPStan
- PHP-CS-Fixer
- Twig CS Fixer
- GrumPHP
- Mailpit

## Code Quality

The project integrates several tools to ensure code quality :

- PHPStan for static analysis
- PHP-CS-Fixer for PHP code style
- Twig CS Fixer for Twig templates
- GrumPHP for automated quality checks
- PHPUnit for automated testing

## Architecture

The project demonstrates:

- Notification Factory supporting Email and SMS delivery
- Factory Pattern
- Symfony Dependency Injection
- Service-oriented architecture
- SOLID principles
- Custom Symfony Form Types
- Symfony UX Autocomplete custom field
- Separation of responsibilities
- Doctrine ORM relationships
- Fetch joins to avoid N+1 query problems

## Administration

The project includes a dedicated administration back-office with:

- Recipe management
- Category management
- Responsive sidebar navigation
- Sortable recipe listings
- Pagination
- Bootstrap-based interface
- Turbo-powered modal forms
- Client-side image preview
- Responsive design for desktop, tablet and mobile

## Image Management

Recipe thumbnails are handled using VichUploaderBundle.
Uploaded images are validated using Symfony Validator:

- Maximum file size: 7 MB
- JPEG
- PNG
- WebP
- Maximum dimensions: 1080 × 1080 pixels

The administration interface also provides a client-side preview when selecting a new image.

## Docker
The project runs in a Docker-based development environment using Docker Compose.
The development environment includes:

- PHP 8.4.24 / PHP-FPM
- Symfony 7.4.16
- Apache
- MySQL
- phpMyAdmin
- Mailpit

## Requirements
- Docker
- Docker Compose
- Make

## Installation
- Replace all occurrences ``symfony_docker`` by your project name
- Edit the ``PROJECT_PORT`` variable in ``.env`` to set the application port
- ``make .env.local``
- Edit your ``.env.local``
- ``make install`` or ``make reset`` :)

## Command to find a free port on your machine
python3 -c "import socket; s=socket.socket(); s.bind(('',0)); print(s.getsockname()[1]); s.close()"

## Command used to initialize all components necessary for creating a web application
- ``composer require webapp``

## Compile assets (CSS, JS)
- ``php bin/console asset-map:compile``

## Database for migration
- ``php bin/console make:migration``
- ``php bin/console doctrine:migrations:migrate``

## Sending emails

Mailpit is used locally to simulate email delivery.

By default, Mailpit runs as a Docker Compose service using the `axllent/mailpit` image.

- SMTP: `localhost:1025`
- Web interface: http://localhost:8025/

### Manual installation

If you prefer to run Mailpit directly on macOS instead of using Docker, you can download it manually:

- Download Mailpit to the `bin` directory:
  `curl -LO https://github.com/axllent/mailpit/releases/latest/download/mailpit-darwin-arm64.tar.gz`
- Extract the archive:
  `tar -xvzf mailpit-darwin-arm64.tar.gz`
- Make the binary executable:
  `chmod +x mailpit`

## To run GrumPHP locally, type the following command inside your Docker container:
- ``vendor/bin/grumphp run``

## To run only phpstan, type the following command inside your Docker container:
- ``vendor/bin/phpstan analyse src --level=1``

## To lint Twig templates, type the following command inside your Docker container:
- ``vendor/bin/twig-cs-fixer lint templates/``

## To fix Twig templates, type the following command inside your Docker container:
- ``vendor/bin/twig-cs-fixer fix templates/``

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

### Run All tests with detailed output
- ``php bin/phpunit --testdox``

### Test database behavior
The project uses `dama/doctrine-test-bundle` to ensure test isolation. Each test runs inside a transaction that is automatically rolled back after execution, meaning:

- The test database is always clean between test runs
- No data pollution between tests
- No need to purge the database between runs
- Tests can be executed repeatedly without side effects with the command `php bin/phpunit`
- The test database remains consistent between test runs

### Development Goals

This project is continuously evolving and is used to experiment with:

- Modern Symfony features
- PHP language features
- Symfony UX components
- Design Patterns
- SOLID principles
- Clean Code principles
- Automated testing
- Static analysis
- Code quality tools
- Docker-based development environments
- Performance and Doctrine query optimization
- Modern administration interfaces