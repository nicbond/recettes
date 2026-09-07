# TECHNICAL.md

## Stack

See the `README.md` file for the complete technology stack.

---

## Symfony UX

### Turbo

[Hotwire Turbo](https://turbo.hotwired.dev/) is integrated via `symfony/ux-turbo`.

The application uses the following Turbo features:

- **Turbo Drive** — page navigation without full page reloads
- **Turbo Frames** — partial page updates, notably for edit modals
- **Turbo Streams** — dynamic DOM updates

### Stimulus

[Stimulus](https://stimulus.hotwired.dev/) is integrated into the application.

It is a lightweight JavaScript framework based on controllers attached to HTML elements using `data-controller`.

Stimulus is used for:

- Sidebar dropdown open/close behavior
- Image preview before upload (`thumbnail_preview_controller`)
- Dynamic form collections for ingredients and quantities (`form-collection_controller`)

---

## Asset Management

The project uses **Symfony AssetMapper** instead of a traditional JavaScript bundler.

No Node.js or Webpack build is required.

Dependencies are declared in `importmap.php` and can be loaded from CDN providers such as jsDelivr.

Example:

```php
// importmap.php
'bootstrap' => ['version' => '5.3.8'],
'@hotwired/turbo' => ['version' => '7.3.0'],
'@hotwired/stimulus' => ['version' => '3.2.2'],
```

---

## Turbo Frame Modal

The application uses Bootstrap modals combined with Turbo Frames to edit entities without requiring a full page reload.

For example, clicking the **Edit** button or the recipe image opens the corresponding form inside a Bootstrap modal.

### How it works

1. The link contains the `data-turbo-frame="modal"` attribute.
2. Turbo intercepts the navigation and sends a request with the `Turbo-Frame: modal` header.
3. Symfony detects the Turbo Frame request and returns the modal content inside a `<turbo-frame id="modal">`.
4. The JavaScript modal integration detects the frame load.
5. The Bootstrap modal is displayed.

### Index page

The index page contains an empty Bootstrap modal waiting to be populated by Turbo:

```twig
<div class="modal fade" id="turbo-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <turbo-frame id="modal"></turbo-frame>
        </div>
    </div>
</div>
```

### Edit template

The template can return a Turbo Frame when the request originates from the modal:

```twig
{% if app.request.headers.get('turbo-frame') == 'modal' %}
    <turbo-frame id="modal">
        {# Modal content #}
    </turbo-frame>
{% else %}
    {# Full page content #}
{% endif %}
```

This allows the same route to support both:

- Direct browser navigation
- Turbo Frame modal navigation

### Controller

The form action is explicitly configured to avoid URL context issues when the form is submitted from a Turbo Frame:

```php
$form = $this->createForm(RecipeType::class, $recipe, [
    'action' => $this->generateUrl('admin.recipe.edit', [
        'id' => $recipe->getId(),
    ]),
]);
```

Invalid form submissions return HTTP `422 Unprocessable Entity` so Turbo keeps the modal open and displays the validation errors:

```php
$status = $form->isSubmitted() && !$form->isValid() ? 422 : 200;

return $this->render(
    'admin/recipe/edit.html.twig',
    [...],
    new Response(status: $status)
);
```

---

## Pagination

Pagination is handled by **KnpPaginatorBundle**.

Example:

```php
$pagination = $this->paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    $this->numberPerPageRecipe
);
```

Sortable columns are rendered using `knp_pagination_sortable()`.

Example:

```twig
{{ knp_pagination_sortable(pagination, 'Titre', 'recipe.title') }}
```

The listing supports sorting by relevant recipe properties such as:

- Recipe title
- Category

---

## Recipe Filtering

The recipe listing provides filtering capabilities through a dedicated filter form.

Available filters include:

- Recipe title
- Category
- Tags

The filter form uses the `GET` method so the filtering parameters are reflected in the URL.

Example:

```php
$resolver->setDefaults([
    'method' => 'GET',
    'csrf_protection' => false,
]);
```

The filtering data is represented by a dedicated DTO instead of passing untyped form data directly to the repository.

Example:

```php
final class RecipeFilter
{
    public ?string $title = null;

    public ?Category $category = null;

    /** @var list<Tag> */
    public array $tags = [];
}
```

The form is associated with the DTO through the `data_class` option.

This avoids relying on `mixed` form data and provides stronger static typing, particularly with PHPStan.

---

## Symfony UX Autocomplete

The application uses **Symfony UX Autocomplete** for entity selection fields.

Custom autocomplete fields are implemented using `AsEntityAutocompleteField` and `BaseEntityAutocompleteType`.

For example, tags can be selected through a dedicated `TagAutocompleteField`.

```php
#[AsEntityAutocompleteField]
class TagAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Tag::class,
            'choice_label' => 'name',
            'multiple' => true,
            'tom_select_options' => [
                'placeholder' => 'Choisir un ou des tags',
            ],
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
```

Autocomplete fields are used for entities such as:

- Categories
- Tags
- Ingredients

---

## Tom Select

[Tom Select](https://tom-select.js.org/) is used to enhance `<select>` fields.

It provides a better user experience for entity selection and supports features such as:

- Real-time search
- Multi-selection
- Custom placeholders
- Creating entities dynamically where required

### Ingredient creation

The ingredient field supports creating an ingredient directly from the autocomplete interface.

When the entered ingredient does not exist, Tom Select can display an option to create it.

The application sends a `POST` request to:

```text
/ingredient/create-ajax
```

This allows ingredients to be created without leaving the recipe form.

### Multi-select

Tom Select is also used for selecting multiple tags.

For multiple selections, the placeholder is configured through `tom_select_options`:

```php
'tom_select_options' => [
    'placeholder' => 'Choisir un ou des tags',
],
```

This is preferable to the standard Symfony `placeholder` option when the field uses `multiple => true`.

---

## Image Upload

Images are managed using **VichUploaderBundle**.

Recipe images have the following constraints:

- Maximum file size: `7000k`
- Accepted formats:
    - `image/jpeg`
    - `image/png`
    - `image/webp`
- Maximum dimensions: `1080x1080`

Uploaded images are automatically converted to WebP format by the application's `ImageConvertSubscriber`.

### Recipe image forms

Two dedicated forms are used for recipe images:

- `RecipeType` — complete recipe form including the image upload
- `RecipeThumbnailType` — dedicated image-only form

The second form is used when clicking the recipe image from the recipe listing.

This provides a focused workflow for changing an image without having to edit the complete recipe.

### Image preview

A Stimulus controller provides an image preview before the upload is submitted.

The preview uses the browser `FileReader` API to display the selected image immediately.

---

## Recipe Management

Recipes support the following information:

- Title
- Slug
- Category
- Description/content
- Duration
- Online status
- Thumbnail
- Tags
- Ingredients
- Quantities
- Units

### Slug

The recipe slug can be automatically generated when no slug is provided.

### Online status

The `online` property controls whether the recipe can later be exposed through the application's front/API layer.

A recipe can therefore be created and prepared before being made available online.

---

## Recipe Quantities

Recipe ingredients are represented through a dedicated `Quantity` entity.

The relationship is structured as follows:

```text
Recipe
  └── Quantity
        ├── Ingredient
        └── Unit
```

The `Recipe` entity has a `OneToMany` relationship with `Quantity`:

```php
#[ORM\OneToMany(
    mappedBy: 'recipe',
    targetEntity: Quantity::class,
    cascade: ['persist'],
    orphanRemoval: true
)]
private Collection $quantities;
```

The `Quantity` entity has mandatory relationships with:

- Recipe
- Ingredient
- Unit

```php
#[ORM\JoinColumn(nullable: false)]
```

This ensures that every quantity is associated with a recipe, ingredient and unit.

### Dynamic quantity collection

Ingredients and quantities are managed through a dynamic Symfony form collection.

The collection is handled by the Stimulus `form-collection_controller`.

The interface allows users to:

- Add ingredients
- Select an ingredient
- Select a unit
- Enter a quantity
- Remove an ingredient line

On desktop screens, the fields are displayed on a single line where possible, with a dedicated delete action.

---

## Forms

### RecipeType

`RecipeType` is the main recipe form.

It contains:

- Title
- Slug
- Category
- Content
- Duration
- Online status
- Thumbnail
- Tags
- Ingredients and quantities

The image upload is handled through the dedicated `RecipeThumbnailType` using `inherit_data`.

### RecipeThumbnailType

`RecipeThumbnailType` is a dedicated form for updating a recipe thumbnail.

It is used by the Turbo modal opened when clicking the recipe image.

This separation keeps the image update workflow independent from the complete recipe form.

---

## Validation

Validation is handled primarily through Symfony Validator constraints on entities.

For example, the recipe title can be validated using:

```php
#[ORM\Column(length: 255, unique: true)]
#[Assert\Length(
    min: 5,
    minMessage: 'Le titre doit être supérieur à {{ limit }} caractères.',
    groups: ['Extra']
)]
#[BanWord(groups: ['Extra'])]
private string $title;
```

The recipe content is also validated:

```php
#[ORM\Column(type: Types::TEXT)]
#[Assert\NotBlank(
    message: 'Le descriptif de la recette est obligatoire.'
)]
#[Assert\Length(
    min: 5,
    minMessage: 'Le descriptif doit être supérieur à {{ limit }} caractères.'
)]
private string $content = '';
```

Entity-level validation ensures that validation rules remain available independently of the HTML form, including when the entities are used by other application layers.

---

## Doctrine Relationships

Doctrine ORM is used for persistence.

Recipe-related relationships include:

```text
Recipe
 ├── Category
 ├── Tag[]
 └── Quantity[]
       ├── Ingredient
       └── Unit
```

Required relationships are explicitly declared as non-nullable:

```php
#[ORM\JoinColumn(nullable: false)]
```

This ensures the database schema reflects the domain constraints.

Database schema changes are managed through Doctrine migrations.

Typical workflow:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## Doctrine Query Optimization

Repository queries use Doctrine QueryBuilder.

When related entities are required by the listing, joins can be used to avoid unnecessary additional database queries.

For example:

```php
$qb = $this->createQueryBuilder('recipe')
    ->select('recipe', 'category', 'tag')
    ->leftJoin('recipe.category', 'category')
    ->leftJoin('recipe.tags', 'tag');
```

This allows the recipe listing to retrieve the required related entities efficiently and helps prevent N+1 query problems.

---

## Event-Driven Architecture

The application uses Symfony's EventDispatcher for decoupling certain application behaviors.

### Contact form

The contact form uses an event-driven architecture.

The flow is:

```text
Controller
    ↓
ContactRequestEvent
    ↓
MailingSubscriber
    ↓
Email
```

A separate logger subscriber handles successful contact requests:

```text
Controller
    ↓
ContactRequestEvent
    ├── MailingSubscriber
    └── LoggerSubscriber
```

This approach makes it possible to add additional behaviors without modifying the controller.

For example, additional subscribers could handle:

- SMS notifications
- Slack notifications
- Audit logging
- Other asynchronous or secondary actions

---

## Event Listeners

The application also uses Symfony event listeners for cross-cutting HTTP concerns.

### Maintenance Mode

The `MaintenanceListener` listens to the Symfony `kernel.request` event.

It is responsible for applying the maintenance-mode restriction before the request reaches the application's controllers.

The listener only processes the main HTTP request:

```php
if (!$event->isMainRequest()) {
    return;
}
```

This avoids applying the restriction to sub-requests.

The maintenance route itself is excluded to prevent an infinite redirect loop:

```php
if ('/maintenance' === $request->getPathInfo()) {
    return;
}
```

The listener retrieves the client IP and compares it against the configured whitelist.

---

## Maintenance Mode

The application includes a maintenance mode that restricts access to the application to a configured IP whitelist.

### Configuration

The maintenance mode is controlled through environment variables:

```dotenv
###> MAINTENANCE PAGE ###
## 0 or 1
ACTIVE_MAINTENANCE_PAGE=1
ALLOWED_IP=57.128.19.245
###< MAINTENANCE PAGE ###
```

- `ACTIVE_MAINTENANCE_PAGE` — enables or disables maintenance mode (`0` = disabled, `1` = enabled)
- `ALLOWED_IP` — comma-separated list of IP addresses allowed to access the application while maintenance mode is enabled

Multiple IP addresses can be configured:

```dotenv
ALLOWED_IP=57.128.19.245,192.168.1.10
```

### Request Handling

When maintenance mode is enabled:

1. Only the main HTTP request is processed.
2. Requests to `/maintenance` are allowed.
3. The client's IP address is retrieved from the request.
4. The `ALLOWED_IP` environment variable is parsed as a comma-separated list.
5. Requests from an allowed IP bypass maintenance mode.
6. All other requests are redirected to the maintenance page.

Unauthorized requests receive an HTTP `302 Found` redirect:

```php
$maintenanceUrl = $this->urlGenerator->generate('maintenance');

$response = new RedirectResponse(
    $maintenanceUrl,
    Response::HTTP_FOUND
);

$event->setResponse($response);
```

The maintenance check is therefore handled centrally at the HTTP kernel level, without requiring individual controllers to implement access restrictions.

This allows the application to remain accessible for authorized development or administrative access while preventing normal public access during maintenance operations.

---

## Notification Architecture

Notifications are handled through a factory-based architecture.

The notification factory is responsible for creating the appropriate notification implementation based on the requested notification type.

This allows different notification channels to be introduced without coupling the business logic to a specific implementation.

Potential notification channels include:

- Email
- SMS
- Other external notification services

The architecture follows the Factory Pattern and dependency injection principles.

---

## Testing

Tests are written with **PHPUnit** through Symfony's test pack.

Run the test suite with:

```bash
php bin/phpunit
```

The application uses Symfony's testing tools such as `KernelBrowser` for functional and integration tests.

### Shared fixtures

Common test setup is centralized through a reusable `FixturesTrait`.

Example:

```php
trait FixturesTrait
{
    private function createCategory(
        EntityManagerInterface $em,
        string $name = 'Apéritif'
    ): Category {
        // ...
    }

    private function createRecipe(
        EntityManagerInterface $em,
        string $title = 'Recette'
    ): Recipe {
        // ...
    }
}
```

This avoids duplicating entity creation logic across test classes.

### JSON API responses

JSON responses used in tests are decoded with `JSON_THROW_ON_ERROR`.

Example:

```php
/**
 * @return array<string, mixed>
 */
private function decodeResponse(KernelBrowser $client): array
{
    $content = $client->getResponse()->getContent();

    assert(is_string($content));

    /** @var array<string, mixed> $response */
    $response = json_decode(
        $content,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    return $response;
}
```

The explicit PHPStan type annotation ensures that the decoded JSON is correctly represented as an associative array with string keys and mixed values.

---

## Database Fixtures

Fixtures are loaded using **DoctrineFixturesBundle**.

The project contains dedicated fixtures for the main reference data.

### IngredientFixtures

Pre-populates the ingredient table with commonly used cooking ingredients.

### UnitFixtures

Pre-populates the unit table with available measurement units, such as:

- g
- kg
- l
- ml
- cl
- dl
- tablespoon
- teaspoon
- pinch
- glass

### RecipeFixtures

Generates sample recipes with:

- Random recipes
- Categories
- Ingredients
- Quantities
- Units

`RecipeFixtures` depends on the ingredient and unit fixtures.

Load the fixtures with:

```bash
php bin/console doctrine:fixtures:load
```

---

## Code Quality

The project uses several tools to maintain code quality and consistency.

| Tool | Purpose |
|------|---------|
| **PHPStan** | Static analysis |
| **PHP-CS-Fixer** | PHP code style |
| **Twig CS Fixer** | Twig code style |
| **GrumPHP** | Pre-commit quality checks |
| **PHPUnit** | Automated testing |

### PHPStan

PHPStan is configured to perform strict static analysis.

The project targets a high analysis level and uses explicit PHPDoc annotations where necessary to provide accurate type information for dynamically typed framework APIs.

Run PHPStan with:

```bash
vendor/bin/phpstan analyse
```

### PHP-CS-Fixer

PHP-CS-Fixer is used to enforce consistent PHP coding standards.

```bash
vendor/bin/php-cs-fixer fix
```

### GrumPHP

GrumPHP is used as a pre-commit hook to automatically run quality checks before commits are created.

This helps prevent incorrectly formatted or statically invalid code from being committed.

---

## Dependency Injection

Symfony Dependency Injection is used throughout the application.

Services receive their dependencies through constructor injection rather than instantiating dependencies directly.

Example:

```php
readonly class MaintenanceListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }
}
```

This improves:

- Testability
- Separation of concerns
- Maintainability
- Dependency inversion

---

## SOLID Principles

The application follows SOLID principles where appropriate.

Examples include:

- Controllers focused on HTTP/application orchestration
- Repositories responsible for database queries
- Dedicated form types for form configuration
- DTOs for structured filter input
- Subscribers/listeners for cross-cutting concerns
- Factories for notification creation
- Services receiving dependencies through dependency injection

The goal is to keep responsibilities separated and make individual components easier to test and evolve.

---

## Architecture Overview

The main application flow follows Symfony's standard layered architecture:

```text
HTTP Request
     │
     ▼
Event Listeners
     │
     ▼
Controller
     │
     ├── Form / DTO
     │
     ├── Application Services
     │
     └── Repository
              │
              ▼
          Doctrine ORM
              │
              ▼
            MySQL
```

For event-driven operations:

```text
Controller
     │
     ▼
Domain/Application Event
     │
     ├── Subscriber
     ├── Subscriber
     └── Subscriber
```

This architecture keeps HTTP concerns, business logic, persistence and secondary actions separated.

---

## Mailpit

**Mailpit** is used during development to capture outgoing emails without sending them to real recipients.

It provides a local interface for inspecting:

- Email content
- Recipients
- Subjects
- Headers
- HTML rendering

This allows email functionality to be tested safely in the development environment.

---

## Docker

The development environment is containerized using Docker and Docker Compose.

The environment contains the services required by the application, including:

- PHP
- Apache
- MySQL
- MongoDB where required
- phpMyAdmin
- Mailpit

The exact service configuration is defined in `docker-compose.yml`.

This provides a consistent development environment and avoids requiring the host machine to have the complete application stack installed directly.

---

## Database

The primary relational database is **MySQL**, accessed through Doctrine ORM.

Database schema changes are managed through Doctrine migrations.

Typical commands:

```bash
php bin/console doctrine:migrations:migrate
```

To create a new migration:

```bash
php bin/console make:migration
```

The development environment also provides phpMyAdmin for database inspection and administration.

---

## Development Principles

The project aims to follow the following principles:

- Keep controllers thin
- Prefer dependency injection
- Use dedicated services for business logic
- Use DTOs for structured input data
- Keep persistence logic inside repositories
- Use Symfony Forms for form handling
- Use entity validation for domain constraints
- Avoid duplicated logic
- Avoid N+1 database queries
- Keep frontend behavior isolated in Stimulus controllers
- Use Turbo Frames where partial page updates improve UX
- Keep asynchronous or secondary behaviors decoupled through events where appropriate
- Maintain strict static analysis with PHPStan
- Cover application behavior with automated tests
