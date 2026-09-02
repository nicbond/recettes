## Recipes

Symfony 7 application for managing cooking recipes.

This personal project was created to experiment with modern Symfony best practices and explore architectural patterns used in professional projects.

## Features

* Recipe management
* Category management
* Ingredient and quantity management
* Unit management
* Full CRUD operations
* Form validation
* Character counter on recipe content fields
* Recipe image upload and preview
* Dynamic image preview during recipe creation and editing
* Image hover overlay with contextual actions
* Image validation (JPEG, PNG and WebP)
* Image resizing and WebP support
* Restricted image preview dimensions to preserve layout consistency
* Responsive image management
* Category autocomplete with Symfony UX Autocomplete
* Unit autocomplete with Symfony UX Autocomplete
* Recipes can be created without ingredients
* Online/offline recipe status management
* Offline recipes can be prepared before being published through the API
* Protection against deleting categories linked to recipes
* Pagination and sortable recipe listings
* Email notifications
* SMS notifications through a notification factory
* Service-oriented architecture
* Responsive administration back-office
* Turbo-powered modal forms

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

## Code Quality

The project integrates several tools to ensure code quality:

* PHPStan for static analysis
* PHP-CS-Fixer for PHP code style
* Twig CS Fixer for Twig templates
* GrumPHP for automated quality checks
* PHPUnit for automated testing

Quality checks are integrated into the development workflow to detect code style, static analysis and functional issues as early as possible.

## Architecture

The project demonstrates:

* Notification Factory supporting Email and SMS delivery
* Factory Pattern
* Symfony Dependency Injection
* Service-oriented architecture
* SOLID principles
* Clean Code principles
* Custom Symfony Form Types
* Symfony UX Autocomplete custom fields
* Separation of responsibilities
* Doctrine ORM relationships
* Entity relationships between recipes, ingredients, quantities and units
* Fetch joins to avoid N+1 query problems
* Business rules enforced at application level
* Protection against deleting entities that are still referenced
* Recipe publication state management through an `online` flag
* Separation between recipe creation and recipe publication

## Recipe Management

Recipes can be created and edited through the administration back-office.

Each recipe can contain:

* A title
* A description
* A category
* Multiple ingredients
* A quantity for each ingredient
* A unit for each quantity
* A thumbnail
* An online/offline publication status

Ingredients and quantities are managed dynamically using a Symfony form collection.

Each ingredient entry combines:

* Ingredient
* Quantity
* Unit

Units are managed through a dedicated `Unit` entity and can be selected using Symfony UX Autocomplete.

Recipes can also be created without ingredients, allowing administrators to progressively prepare their content before completing and publishing it.

## Recipe Publication

Recipes have an `online` status that controls whether they can be exposed through the API.

When creating a recipe, ingredients are optional. This allows an administrator to create and prepare a recipe progressively without having to provide all ingredients immediately.

* `online = false`: the recipe remains unavailable to the front-end API
* `online = true`: the recipe can be exposed through the API with its ingredients

This separates the recipe creation workflow from its publication and allows recipes to be prepared before being made available to users.

## Administration

The project includes a dedicated administration back-office with:

* Recipe management
* Category management
* Ingredient and quantity management
* Unit management
* Recipe creation without requiring ingredients
* Online/offline recipe status management
* Responsive sidebar navigation
* Sortable recipe listings
* Pagination
* Bootstrap-based interface
* Turbo-powered modal forms
* Client-side image preview
* Character counter for recipe content
* Responsive design for desktop, tablet and mobile

The administration interface also includes UX improvements such as contextual image actions, responsive form layouts and optimized controls for managing dynamic ingredient collections.

## Image Management

Recipe thumbnails are handled using VichUploaderBundle.

Uploaded images are validated using Symfony Validator:

* Maximum file size: 7 MB
* JPEG
* PNG
* WebP
* Maximum dimensions: 1080 × 1080 pixels

The administration interface provides a client-side preview when selecting a new image.

Image management has been designed to preserve the layout of the administration interface:

* Preview dimensions are restricted to avoid oversized images
* Existing images are displayed while editing
* New images are previewed immediately in the browser
* Recipe cards provide a contextual hover overlay for image actions
* Image editing is handled through a dedicated Turbo modal
* The image editing modal uses a balanced responsive layout

## Category Management

Categories are managed through the administration back-office.

A business rule prevents the deletion of a category that is still associated with recipes.

This protects data integrity and prevents recipes from being left with an invalid category reference.

The behavior is covered by automated tests.

## Ingredient and Quantity Management

Recipes support dynamic ingredient collections.

Each recipe can contain multiple ingredient entries with:

* Ingredient
* Quantity
* Unit

Ingredient entries can be dynamically added or removed from the recipe form.

The delete action is positioned in the top-right corner of each ingredient block to keep the interface compact and consistent.

Units are represented by a dedicated `Unit` entity and are available through Symfony UX Autocomplete.

This structure keeps ingredients, quantities and units properly separated while maintaining a simple administration experience.

## User Experience

The administration interface includes several UX improvements:

* Turbo-powered modal forms
* Responsive forms
* Dynamic ingredient collections
* Contextual image hover actions
* Client-side image preview
* Optimized image dimensions
* Dedicated image editing modal
* Character counter for recipe content
* Autocomplete fields for categories and units
* Responsive layouts for desktop, tablet and mobile

The goal is to keep the administration interface efficient while maintaining a clean and modern user experience.

## Docker

The project runs in a Docker-based development environment using Docker Compose.

The development environment includes:

* PHP 8.4.24 / PHP-FPM
* Symfony 7.4.16
* Apache
* MySQL
* phpMyAdmin
* Mailpit

## Requirements

* Docker
* Docker Compose
* Make

## Installation

* Replace all occurrences `symfony_docker` by your project name
* Edit the `PROJECT_PORT` variable in `.env` to set the application port
* `make .env.local`
* Edit your `.env.local`
* `make install` or `make reset` :)

## Command to find a free port on your machine

```bash
python3 -c "import socket; s=socket.socket(); s.bind(('',0)); print(s.getsockname()[1]); s.close()"
```

## Command used to initialize all components necessary for creating a web application

```bash
composer require webapp
```

## Compile assets (CSS, JS)

```bash
php bin/console asset-map:compile
```

## Database for migration

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Sending emails

Mailpit is used locally to simulate email delivery.

By default, Mailpit runs as a Docker Compose service using the `axllent/mailpit` image.

* SMTP: `localhost:1025`
* Web interface: http://localhost:8025/

### Manual installation

If you prefer to run Mailpit directly on macOS instead of using Docker, you can download it manually:

* Download Mailpit to the `bin` directory:

```bash
curl -LO https://github.com/axllent/mailpit/releases/latest/download/mailpit-darwin-arm64.tar.gz
```

* Extract the archive:

```bash
tar -xvzf mailpit-darwin-arm64.tar.gz
```

* Make the binary executable:

```bash
chmod +x mailpit
```

## To run GrumPHP locally, type the following command inside your Docker container

```bash
vendor/bin/grumphp run
```

## To run only PHPStan, type the following command inside your Docker container

```bash
vendor/bin/phpstan analyse src --level=9
```

## To lint Twig templates

```bash
vendor/bin/twig-cs-fixer lint templates/
```

## To fix Twig templates

```bash
vendor/bin/twig-cs-fixer fix templates/
```

## Testing

For all tests, we run them with CSRF enabled because:

* We are testing under real-world conditions — if CSRF is active in production, it should be active in tests too.
* We ensure the CSRF token is correctly handled in forms.
* We avoid false positives — a test that passes only because CSRF is disabled does not reflect reality.
* Retrieving the token from the HTML more closely mirrors actual user behavior.
* The tests reflect the application's actual behavior.

Disabling CSRF during testing is a shortcut that can mask bugs.

### Setup the test database

Run the following commands inside your Docker container:

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test -n
php bin/console doctrine:fixtures:load --env=test -n
```

### Grant privileges to the test database (if needed)

If you get an "Access denied" error, run the following command:

```bash
docker exec -it <mysql_container_name> mysql -u root -proot -e "GRANT ALL PRIVILEGES ON recettes_test.* TO 'dev'@'%'; FLUSH PRIVILEGES;"
```

### Run the tests

```bash
php bin/phpunit
```

### Run a specific test file

```bash
php bin/phpunit tests/Controller/Admin/CategoryControllerTest.php
```

### Run a specific test method

```bash
php bin/phpunit --filter testIndex
```

### Run all tests with detailed output

```bash
php bin/phpunit --testdox
```

### Test database behavior

The project uses `dama/doctrine-test-bundle` to ensure test isolation.

Each test runs inside a transaction that is automatically rolled back after execution, meaning:

* The test database is always clean between test runs
* No data pollution between tests
* No need to purge the database between runs
* Tests can be executed repeatedly without side effects with the command `php bin/phpunit`
* The test database remains consistent between test runs

New business rules and administration workflows are also covered by automated tests, including category deletion constraints and recipe management behavior.

## Development Goals

This project is continuously evolving and is used to experiment with:

* Modern Symfony features
* PHP language features
* Symfony UX components
* Design Patterns
* SOLID principles
* Clean Code principles
* Automated testing
* Static analysis
* Code quality tools
* Docker-based development environments
* Performance and Doctrine query optimization
* Modern administration interfaces
* Responsive user experience
* Dynamic Symfony forms
* Progressive recipe creation and publication workflows
* Business rule enforcement
* Data integrity
* Maintainable application architecture
