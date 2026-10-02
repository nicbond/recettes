# TECHNICAL.md

## Stack

See the `README.md` file for the complete technology stack.

---

## Development Principles

* Keep controllers thin
* Prefer dependency injection
* Use DTOs for structured input data
* Keep persistence logic inside repositories
* Use entity validation for domain constraints
* Avoid N+1 database queries
* Keep frontend behavior isolated in Stimulus controllers
* Use Turbo Frames where partial page updates improve UX
* Decouple secondary behaviors through events
* Let Messenger handle retries and failure natively for async operations
* Maintain strict static analysis with PHPStan
* Cover application behavior with automated tests

---

## SOLID Principles

The application follows SOLID principles where appropriate:

* Controllers focused on HTTP/application orchestration
* Repositories responsible for database queries
* Dedicated form types for form configuration
* DTOs for structured filter input
* Subscribers/listeners for cross-cutting concerns
* Factories for notification creation
* Services receiving dependencies through constructor injection

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
     ├── Application Services
     └── Repository
              │
              ▼
          Doctrine ORM
              │
              ▼
            MySQL
```

For event-driven operations such as the contact form:

```text
Controller
     ↓
ContactRequestEvent
     └── MailingSubscriber → sends email, catches errors, marks event as failed
```

For asynchronous operations such as account verification and password reset:

```text
Controller
     ↓
Message dispatched via Messenger
     ↓
MessageHandler
     ↓
Event dispatched
     └── MailingSubscriber
              ↓
          sends email
```

## Administration Back-Office

The project includes a dedicated administration back-office with:

* Recipe management
* Category management
* Tag management
* Ingredient creation during recipe editing
* Quantity management through recipes
* Unit management
* Recipe creation without requiring ingredients
* Online/offline recipe status management
* Responsive sidebar navigation
* Sortable listings
* Pagination
* Bootstrap-based interface
* Turbo-powered modal forms
* Client-side image preview
* Password visibility toggle
* Responsive design for desktop, tablet and mobile

The administration interface also provides granular authorization through Symfony Security voters and a dedicated permission-management interface for Super Admins.

---

## Authentication and Authorization

The application uses Symfony Security for authentication and authorization.

The security model distinguishes between:

* authenticated users;
* administrators;
* Super Administrators;
* fine-grained administrative permissions.

Authentication and authorization are intentionally separated:

* **roles** control broad access;
* **permissions** control individual administrative operations.

---

## User Hierarchy

The application uses Doctrine single-table inheritance to manage different types of users:

```text
User
 ├── Admin      → discriminator: admin
 └── Consumer   → discriminator: consumer
```

The discriminator column in the `user` table determines the concrete user type.

## Roles

The application uses the following roles:

* `ROLE_USER` — automatically assigned to authenticated users.
* `ROLE_ADMIN` — automatically assigned to every `Admin`.
* `ROLE_SUPER_ADMIN` — identifies a Super Admin with unrestricted administrative permissions.

The `Admin` entity adds `ROLE_ADMIN` through `getRoles()`:

```php
public function getRoles(): array
{
    return array_unique(array_merge(parent::getRoles(), ['ROLE_ADMIN']));
}
```

A Super Admin is identified by:

```php
public function isSuperAdmin(): bool
{
    return in_array('ROLE_SUPER_ADMIN', $this->getRoles(), true);
}
```

## Routes and Access Control

The main access-control rules are:

| Area               | Access                   |
| ------------------ | ------------------------ |
| Login              | `PUBLIC_ACCESS`          |
| Registration       | `PUBLIC_ACCESS`          |
| Account activation | `PUBLIC_ACCESS`          |
| Password reset     | `PUBLIC_ACCESS`          |
| Contact            | `PUBLIC_ACCESS`          |
| `/admin/*`         | `ROLE_ADMIN`             |
| Application        | `IS_AUTHENTICATED_FULLY` |

The broad `/admin` restriction provides the first level of protection.

Individual administrative actions are then protected by granular permissions.

---

# Granular Permissions

The administration back-office implements a fine-grained **role-based access control (RBAC)** system.

The architecture combines:

* Symfony roles;
* a dedicated `Permission` PHP enum;
* permissions stored on the `Admin` entity;
* a custom Symfony `Voter`;
* controller-level `#[IsGranted]` attributes;
* Twig visibility checks;
* a dedicated permission-management interface for Super Admins.

The goal is to keep authorization centralized and avoid duplicating security logic throughout controllers.

---

## Permission Enum

All available permissions are centralized in:

```text
src/Enum/Permission.php
```

The enum is the single source of truth for the permission system.

Each permission defines:

1. a unique technical value;
2. a human-readable label;
3. a logical group.

For example:

```php
case RECIPE_EDIT = 'recipe.edit';
```

The enum also exposes all values:

```php
public static function values(): array
{
    return array_map(
        fn (self $p) => $p->value,
        self::cases()
    );
}
```

This method is used by the voter to automatically support every declared permission.

---

## Permission Groups

Current permissions are organized into the following groups.

### Recipes

```text
recipe.menu
recipe.create
recipe.edit
recipe.delete
```

These permissions respectively control:

* access to the Recipes administration menu;
* recipe creation;
* recipe edition;
* recipe deletion.

Recipe thumbnail editing is currently protected by the same `recipe.edit` permission.

### Categories

```text
category.create
category.edit
category.delete
```

### Tags

```text
tag.create
tag.edit
tag.delete
```

### Ingredients

```text
ingredient.create
```

There is currently no dedicated Ingredient administration menu.

Ingredients are created dynamically from the recipe form through the Tom Select / AJAX workflow.

Consequently, only ingredient creation currently exists as a dedicated permission.

There are no `ingredient.edit` or `ingredient.delete` permissions because the application does not currently expose dedicated ingredient management screens or routes.

### Users

```text
user.create
user.edit
user.delete
```

These permissions are already defined by the permission model for future user-management functionality.

The dedicated administrator permission-management interface itself remains protected by `ROLE_SUPER_ADMIN`.

---

## Admin Entity

Permissions are stored directly on the `Admin` entity as a JSON array:

```php
#[ORM\Column(type: 'json')]
private array $permissions = [];
```

The entity exposes:

```php
/**
 * @return list<string>
 */
public function getPermissions(): array
{
    return $this->permissions;
}
```

Permissions can be assigned through:

```php
/**
 * @param list<string> $permissions
 */
public function setPermissions(array $permissions): static
{
    $this->permissions = $permissions;

    return $this;
}
```

The permission check is centralized in:

```php
public function hasPermission(Permission $permission): bool
{
    return in_array($permission->value, $this->permissions, true);
}
```

This keeps the entity API strongly typed around the `Permission` enum instead of exposing permission checks as arbitrary string comparisons throughout the application.

---

## Super Admin Behavior

Super Admins do not need to have individual permissions stored in their `permissions` JSON field.

The `AdminVoter` automatically grants every permission to a Super Admin.

```text
Admin
 │
 ├── ROLE_SUPER_ADMIN
 │       └── all permissions granted
 │
 └── regular Admin
         └── only assigned permissions granted
```

This prevents the Super Admin account from depending on a potentially incomplete permission list.

---

## AdminVoter

Fine-grained authorization is handled by:

```text
src/Security/Voter/AdminVoter.php
```

The voter extends Symfony's:

```php
Voter
```

and supports every permission declared by:

```php
Permission::values()
```

The authorization flow is:

```text
Symfony Security
       │
       ▼
   AdminVoter
       │
       ├── User is not an Admin
       │        └── DENY
       │
       ├── Admin is Super Admin
       │        └── GRANT
       │
       └── Regular Admin
                │
                ▼
          hasPermission()
                │
          ┌─────┴─────┐
          ▼           ▼
        GRANT        DENY
```

The implementation is intentionally small:

```php
protected function voteOnAttribute(
    string $attribute,
    mixed $subject,
    TokenInterface $token
): bool {
    $user = $token->getUser();

    if (!$user instanceof Admin) {
        return false;
    }

    if ($user->isSuperAdmin()) {
        return true;
    }

    return $user->hasPermission(Permission::from($attribute));
}
```

The voter therefore remains responsible only for authorization decisions.

Business logic stays inside the corresponding controllers, repositories and services.

---

## Controller-Level Authorization

Administrative controllers are protected at two levels.

### Global administration access

Controllers are protected with:

```php
#[IsGranted('ROLE_ADMIN')]
```

This ensures that only administrators can access the administration area.

### Individual permissions

Specific operations are protected with the corresponding permission:

```php
#[IsGranted(Permission::RECIPE_EDIT->value)]
public function edit(Recipe $recipe, Request $request): Response
{
    // ...
}
```

This means that having `ROLE_ADMIN` alone does not automatically grant access to every administrative action.

---

## Recipe Permissions

The `RecipeController` uses separate permissions for its operations:

```php
#[IsGranted(Permission::RECIPE_CREATE->value)]
```

for recipe creation,

```php
#[IsGranted(Permission::RECIPE_EDIT->value)]
```

for recipe editing,

```php
#[IsGranted(Permission::RECIPE_DELETE->value)]
```

for recipe deletion.

Recipe thumbnail editing also uses:

```php
#[IsGranted(Permission::RECIPE_EDIT->value)]
```

This keeps image editing consistent with the rest of the recipe editing functionality.

The recipe listing itself remains protected by the broader:

```php
#[IsGranted('ROLE_ADMIN')]
```

requirement.

The dedicated `recipe.menu` permission is used by the administration navigation to determine whether the Recipes menu should be displayed.

---

## Category Permissions

`CategoryController` protects individual actions with:

```php
Permission::CATEGORY_CREATE
Permission::CATEGORY_EDIT
Permission::CATEGORY_DELETE
```

For example:

```php
#[IsGranted(Permission::CATEGORY_CREATE->value)]
#[Route('/create', name: 'create', methods: ['GET', 'POST'])]
public function create(Request $request): RedirectResponse|Response
{
    // ...
}
```

This separates category listing access from category modification rights.

---

## Tag Permissions

`TagController` follows the same pattern:

```php
Permission::TAG_CREATE
Permission::TAG_EDIT
Permission::TAG_DELETE
```

The controller therefore does not contain manual checks such as:

```php
if ($user->hasPermission(...)) {
    // ...
}
```

Authorization is delegated to Symfony Security and the voter.

---

## Ingredient Permissions

Ingredients are currently handled differently because there is no dedicated administration menu.

The recipe form uses Tom Select to create an ingredient dynamically.

The endpoint:

```text
POST /ingredient/create-ajax
```

is protected with:

```php
#[IsGranted(Permission::INGREDIENT_CREATE->value)]
```

The current flow is:

```text
Recipe form
    │
    ▼
Tom Select
    │
    ├── Existing ingredient → select it
    │
    └── New ingredient
            │
            ▼
      POST /ingredient/create-ajax
            │
            ▼
      IngredientController
            │
            ▼
      ingredient.create
            │
            ▼
      IngredientRepository
```

No ingredient edition or deletion permission currently exists because there is no corresponding administration functionality.

---

## Permission Management Interface

Permission assignment is available only to Super Admins.

The interface is handled by:

```text
src/Controller/Admin/AdminPermissionsController.php
```

and is protected by:

```php
#[IsGranted('ROLE_SUPER_ADMIN')]
```

The controller provides:

* administrator listing;
* filtering by email;
* permission editing;
* administrator activation/deactivation.

---

## Super Admin Protection

A Super Admin cannot modify another Super Admin's permissions.

The controller explicitly prevents this:

```php
if ($admin->isSuperAdmin()) {
    $this->addFlash(
        'danger',
        'Impossible de modifier les permissions d\'un Super Admin.'
    );

    return $this->redirectToRoute(
        'admin.permissions.index'
    );
}
```

This avoids creating an inconsistent state where a Super Admin would appear to have a limited permission set even though Super Admin status grants full access.

---

## AdminPermissionsType

The permission form is implemented by:

```text
src/Form/User/AdminPermissionsType.php
```

The form is generated dynamically from:

```php
Permission::cases()
```

Permissions are grouped using the enum's `group()` method:

```php
$choices[$permission->group()]
    [$permission->label()] = $permission->value;
```

This produces a structure similar to:

```text
Recettes
 ├── Menu Recettes
 ├── Créer une recette
 ├── Modifier une recette
 └── Supprimer une recette

Catégories
 ├── Créer une catégorie
 ├── Modifier une catégorie
 └── Supprimer une catégorie

Tags
 ├── Créer un tag
 ├── Modifier un tag
 └── Supprimer un tag

Ingrédients
 └── Créer un ingrédient

Utilisateurs
 ├── Créer un utilisateur
 ├── Modifier un utilisateur
 └── Supprimer un utilisateur
```

The form uses:

```php
'expanded' => true,
'multiple' => true,
```

which allows the permissions to be displayed as individual checkboxes.

This design means that adding a new permission to the enum automatically makes it available in the administration interface without having to manually update the form type.

---

## Permission Grid and Stimulus

The permission grid uses a dedicated Stimulus controller:

```text
assets/controllers/permission_group_controller.js
```

Each permission group is represented by a card containing:

* the group name;
* a master checkbox;
* the individual permission checkboxes.

The master checkbox allows all permissions in a group to be selected or deselected at once.

The controller also synchronizes the master checkbox when individual permissions are changed.

```text
Permission group
       │
       ├── Master checkbox
       │       │
       │       └── toggleAll()
       │
       └── Individual checkboxes
               │
               └── checkMaster()
                       │
                       ▼
                updateMasterState()
```

This behavior is purely client-side and does not participate in authorization.

The server remains authoritative.

---

## Twig Permission Visibility

The administration templates can hide actions that the current administrator is not allowed to perform.

For example:

```twig
{% if is_granted('recipe.edit') %}
    <a href="{{ path('admin.recipe.edit', {id: recipe.id}) }}">
        Modifier
    </a>
{% endif %}
```

For Super Admins, `is_granted()` automatically returns `true` because the voter grants all permissions.

Twig visibility checks are only a **user-interface convenience**.

They must never be considered a security boundary.

The corresponding controller action remains protected by:

```php
#[IsGranted(Permission::RECIPE_EDIT->value)]
```

Therefore, manually calling a protected URL cannot bypass the permission system.

---

## Permission Architecture

The complete permission flow is:

```text
                    Permission enum
                          │
              ┌───────────┼───────────┐
              │           │           │
            value       label       group
              │           │           │
              └───────────┴───────────┘
                          │
                          ▼
                AdminPermissionsType
                          │
                          ▼
                 Admin.permissions
                      (JSON)
                          │
                          ▼
                    AdminVoter
                          │
                 ┌────────┴────────┐
                 │                 │
          Super Admin          Regular Admin
                 │                 │
               GRANT          hasPermission()
                                   │
                              GRANT / DENY
                                   │
                                   ▼
                         #[IsGranted(...)]
                                   │
                                   ▼
                         Controller action
```

The system is therefore centralized around three main components:

```text
Permission enum
      +
Admin entity
      +
AdminVoter
```

Controllers only declare which permission is required.


### 1. Protect the corresponding controller action

```php
#[IsGranted(Permission::RECIPE_CREATE->value)]
```

### 2. Add UI visibility if necessary

```twig
{% if is_granted('recipe.create') %}
    ...
{% endif %}
```

No modification is required in `AdminPermissionsType` because it automatically reads `Permission::cases()`.

No modification is required in `AdminVoter` because it automatically supports `Permission::values()`.

This keeps the system extensible while maintaining a single source of truth.

---

## Symfony UX

### Turbo

[Hotwire Turbo](https://turbo.hotwired.dev/) is integrated via `symfony/ux-turbo`.

Used features include:

* **Turbo Drive** — page navigation without full page reloads
* **Turbo Frames** — partial page updates, notably edit modals
* **Turbo Streams** — dynamic DOM updates

### Stimulus

[Stimulus](https://stimulus.hotwired.dev/) is used for lightweight frontend interactions.

Controllers include:

* sidebar dropdown behavior;
* image preview before upload;
* dynamic form collections for ingredients and quantities;
* password visibility toggle;
* permission group checkbox management.

---

## Asset Management

The project uses **Symfony AssetMapper** instead of a traditional JavaScript bundler.

No Node.js or Webpack build is required.

Dependencies are declared in `importmap.php`:

```php
'bootstrap' => ['version' => '5.3.8'],
'@hotwired/turbo' => ['version' => '7.3.0'],
'@hotwired/stimulus' => ['version' => '3.2.2'],
```

---

## Turbo Frame Modal

The application uses Bootstrap modals combined with Turbo Frames to edit entities without a full page reload.

Clicking an **Edit** button or a recipe image opens the corresponding form inside a Bootstrap modal.

### How it works

1. The link contains `data-turbo-frame="modal"`.
2. Turbo intercepts the navigation.
3. Symfony receives the `Turbo-Frame: modal` header.
4. Symfony returns the corresponding `<turbo-frame id="modal">`.
5. JavaScript displays the Bootstrap modal.

### Modal container

```twig
<div class="modal fade" id="turbo-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <turbo-frame id="modal"></turbo-frame>
        </div>
    </div>
</div>
```

Invalid form submissions return HTTP `422` so Turbo keeps the modal open and displays validation errors.

---

## Tom Select

Tom Select enhances select fields with:

* real-time search;
* multi-selection;
* custom placeholders;
* on-the-fly entity creation.

### Ingredient creation

When the entered ingredient does not exist, Tom Select allows the user to create it without leaving the recipe form.

The application sends:

```text
POST /ingredient/create-ajax
```

The endpoint is protected by:

```php
#[IsGranted(Permission::INGREDIENT_CREATE->value)]
```

---

## Symfony UX Autocomplete

Custom autocomplete fields are implemented using:

```text
AsEntityAutocompleteField
BaseEntityAutocompleteType
```

Autocomplete is used for:

* categories;
* tags;
* ingredients.

Example:

```php
#[AsEntityAutocompleteField]
class TagAutocompleteField extends AbstractType
{
    public function configureOptions(
        OptionsResolver $resolver
    ): void {
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

---

## Pagination

Pagination is handled by **KnpPaginatorBundle**:

```php
$pagination = $this->paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    $this->numberPerPageRecipe
);
```

Sortable columns are rendered with:

```twig
{{ knp_pagination_sortable(pagination, 'Titre', 'recipe.title') }}
```

---

## Recipe Filtering

The recipe listing supports filtering by:

* title;
* category;
* tags.

Filtering uses a dedicated DTO and the `GET` method.

```php
$resolver->setDefaults([
    'method' => 'GET',
    'csrf_protection' => false,
]);
```

Example DTO:

```php
final class RecipeFilterDTO
{
    public ?string $title = null;
    public ?Category $category = null;

    /** @var list<Tag> */
    public array $tags = [];
}
```

---

## Image Upload

Images are managed using **VichUploaderBundle**.

Constraints:

* Maximum file size: `7000k`
* Accepted formats: `image/jpeg`, `image/png`, `image/webp`
* Maximum dimensions: `1080x1080`

Uploaded images are automatically converted to WebP by `ImageConvertSubscriber`.

### Image forms

* `RecipeType` — complete recipe form including image upload
* `RecipeThumbnailType` — dedicated image-only form

`RecipeThumbnailType` is embedded in `RecipeType` using `inherit_data: true`.

### Image preview

A Stimulus controller provides a client-side preview using the browser `FileReader` API.

---

## Forms

### RecipeType

Main recipe form containing:

* title;
* slug;
* category;
* content;
* duration;
* online status;
* thumbnail;
* tags;
* ingredients;
* quantities.

### RecipeThumbnailType

Dedicated image-only form used by the Turbo modal when clicking the recipe image.

---

## Doctrine Relationships

```text
Recipe
 ├── Category
 ├── Tag[]
 └── Quantity[]
       ├── Ingredient
       └── Unit
```

Required relationships are non-nullable:

```php
#[ORM\JoinColumn(nullable: false)]
```

The `Quantity` collection uses `orphanRemoval: true`:

```php
#[ORM\OneToMany(
    mappedBy: 'recipe',
    targetEntity: Quantity::class,
    cascade: ['persist'],
    orphanRemoval: true
)]
```

This ensures that quantities removed from a recipe are also removed from the database.

---

## Doctrine Query Optimization

Repository queries use fetch joins to avoid N+1 problems.

Example:

```php
$qb = $this->createQueryBuilder('recipe')
    ->select('recipe', 'category', 'tag')
    ->leftJoin('recipe.category', 'category')
    ->leftJoin('recipe.tags', 'tag');
```

Persistence logic remains inside repositories rather than controllers.

---

## Event-Driven Architecture

### Contact form

The contact form uses a synchronous event-driven approach because the result must be returned to the user immediately.

### Async user notifications

Account verification and password reset use Messenger.

Exceptions are allowed to propagate so Messenger can handle:

* retries;
* failure transports;
* dead-letter handling.

Example:

```php
public function onUserVerifyRequestEvent(
    UserVerifyRequestEvent $event
): void {
    $this->sendUserEmail(
        $event->user,
        'Confirmation de votre compte',
        'emails/user/user_account_confirmation.html.twig',
        ['signedUrl' => $event->getSignatureUrl()]
    );
}
```

---

## Notification Architecture

Notifications use a factory-based architecture:

```text
NotificationFactory
     ├── createForContact() → ContactNotificationInterface
     └── createForUser()    → UserNotificationInterface
```

Interfaces are used instead of coupling the factory to concrete implementations.

---

## Asynchronous Messaging

### Transports

| Transport                   | Queue                     | Purpose                |
| --------------------------- | ------------------------- | ---------------------- |
| `async`                     | default                   | Generic async messages |
| `async-contact`             | async-contact             | Contact form emails    |
| `async-pdf`                 | async-pdf                 | Recipe PDF generation  |
| `async-user-account-verify` | async-user-account-verify | Account verification   |
| `async-user-reset-password` | async-user-reset-password | Password reset emails  |

Each transport has a dedicated failure transport using Doctrine.

The global `failed` transport catches messages that do not belong to a dedicated failure transport.

### Retry strategy

Each transport retries up to three times with exponential backoff.

After the retry limit is reached, the message is moved to the corresponding failure transport.

---

## Email Verification

Account verification is handled by **symfonycasts/verify-email-bundle**.

### Flow

1. A `UserVerifyAccountMessage` is dispatched asynchronously.
2. The handler generates a signed URL.
3. A `UserVerifyRequestEvent` is dispatched.
4. `MailingSubscriber` sends the email.
5. The user follows the signed URL.
6. The signature is validated.
7. The account is marked as verified.

The verification URL has a limited lifetime.

---

## Password Reset

Password reset is handled by **symfonycasts/reset-password-bundle**.

### Flow

1. The user submits their email.
2. A reset message is dispatched asynchronously.
3. The handler generates a secure reset token.
4. The reset email is sent.
5. The user follows the reset link.
6. The token is validated.
7. The password is changed.
8. The token is invalidated.

The controller does not reveal whether an email address exists in the database.

---

## Maintenance Mode

Access is restricted to a configured IP whitelist when maintenance mode is enabled.

### Configuration

```dotenv
ACTIVE_MAINTENANCE_PAGE=1
ALLOWED_IP=57.128.19.245,192.168.1.10
```

### Flow

`MaintenanceListener` listens to `kernel.request`.

1. Only the main HTTP request is processed.
2. `/maintenance` is always allowed.
3. The client IP is compared against `ALLOWED_IP`.
4. Allowed IPs bypass maintenance mode.
5. Other requests receive an HTTP `302` redirect to `/maintenance`.

---

## Validation

Validation is primarily applied through Symfony Validator constraints.

Example:

```php
#[Assert\Length(
    min: 5,
    minMessage: 'Le titre doit être supérieur à {{ limit }} caractères.',
    groups: ['Extra']
)]
#[BanWord(groups: ['Extra'])]
private string $title;
```

Validation groups allow constraints to be activated only in specific contexts.

---

## Testing

Tests are written with **PHPUnit**.

### Shared fixtures

The project provides reusable helpers through `FixturesTrait`:

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
        string $title = 'Recette',
        ?Category $category = null
    ): Recipe {
        // ...
    }
}
```

Authenticated functional tests create an `Admin` and authenticate it through Symfony's `loginUser()`.

The shared test setup uses a Super Admin when a test requires unrestricted administration access.

```php
$admin = new Admin();

$admin
    ->setEmail('admin@test.com')
    ->setPassword(
        $passwordHasher->hashPassword(
            $admin,
            '@Password1986'
        )
    )
    ->setRoles(['ROLE_SUPER_ADMIN']);

$em->persist($admin);
$em->flush();

$client->loginUser($admin);
```

This allows functional tests to exercise protected administration routes without reproducing the complete login workflow.

### CSRF in tests

CSRF remains enabled in tests because:

* tests reflect production conditions;
* CSRF token handling is verified;
* retrieving the token from HTML mirrors actual user behavior.

### Test isolation

The project uses `dama/doctrine-test-bundle` to wrap each test in a transaction that is automatically rolled back.

### JSON responses

JSON responses are decoded using:

```php
/** @return array<string, mixed> */
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

---

## Database Fixtures

| Fixture              | Purpose                                                         |
| -------------------- | --------------------------------------------------------------- |
| `IngredientFixtures` | Pre-fills the `ingredient` table                                |
| `UnitFixtures`       | Pre-fills the `unit` table                                      |
| `RecipeFixtures`     | Generates sample recipes with ingredients, quantities and units |

`RecipeFixtures` depends on `IngredientFixtures` and `UnitFixtures`.

---

## Code Quality

| Tool              | Purpose                   |
| ----------------- | ------------------------- |
| **PHPStan**       | Static analysis           |
| **PHP-CS-Fixer**  | PHP code style            |
| **Twig CS Fixer** | Twig code style           |
| **GrumPHP**       | Pre-commit quality checks |
| **PHPUnit**       | Automated testing         |

---

## Dependency Injection

Services receive their dependencies through constructor injection.

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

This keeps dependencies explicit and makes services easier to test.

---

## Mailpit

Mailpit captures outgoing emails locally without sending them to real recipients.

* SMTP: `localhost:1025`
* Web interface: `http://localhost:8025/`

---

# Two-Factor Authentication (2FA)

Two-factor authentication is implemented using **scheb/2fa-bundle** with Google Authenticator (TOTP).

## Packages

```bash
composer require scheb/2fa-bundle scheb/2fa-google-authenticator
```

## Authentication Flow

1. The administrator logs in with email and password.
2. If Google Authenticator is enabled, Symfony Security starts the 2FA process.
3. The user is redirected to `/2fa`.
4. The user enters the six-digit TOTP code.
5. The code is verified.
6. The user becomes fully authenticated.

## Setup Flow

The setup is handled by `TwoFactorController`.

1. A secret is generated through `GoogleAuthenticatorInterface::generateSecret()`.
2. The secret is stored temporarily in the session.
3. The secret is temporarily assigned to the user to generate the provisioning URI.
4. A QR code is generated using `endroid/qr-code`.
5. The user scans the QR code.
6. The user enters the generated six-digit code.
7. The code is verified using `spomky-labs/otphp`.
8. If valid, the secret is persisted.
9. The temporary session value is removed.

A five-second leeway is applied during enrollment to tolerate small clock differences at TOTP window boundaries.

## Configuration

```yaml
scheb_two_factor:
    security_tokens:
        - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
        - Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken

    google:
        enabled: true
        server_name: Recipes
        digits: 6
        leeway: 5
        template: security/2fa_form.html.twig
```

## Firewall Configuration

```yaml
main:
    two_factor:
        auth_form_path: 2fa_login
        check_path: 2fa_login_check
```

## Access Control

The 2FA form route must be available while authentication is in progress:

```yaml
access_control:
    - { path: ^/2fa, roles: IS_AUTHENTICATED_2FA_IN_PROGRESS }
```

## User Entity

The `User` entity implements:

```php
Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface
```

The secret is stored in an optional database column:

```php
#[ORM\Column(length: 255, nullable: true)]
private ?string $googleAuthenticatorSecret = null;
```

The entity exposes:

```php
public function isGoogleAuthenticatorEnabled(): bool
{
    return null !== $this->googleAuthenticatorSecret;
}

public function getGoogleAuthenticatorUsername(): ?string
{
    return $this->email;
}

public function getGoogleAuthenticatorSecret(): ?string
{
    return $this->googleAuthenticatorSecret;
}
```

The serialization methods explicitly include the authenticator secret so the authentication state remains compatible with Symfony sessions.

## UserChecker

A custom `UserChecker` verifies that the account is activated before authentication:

```php
public function checkPreAuth(UserInterface $user): void
{
    if (!$user instanceof User) {
        return;
    }

    if (!$user->isVerified()) {
        throw new CustomUserMessageAccountStatusException(
            "Votre compte n'est pas encore activé."
        );
    }
}
```

## Login Success Listener

A `LoginSuccessEventListener` updates `lastLoginAt` after a successful authentication:

```php
#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessEventListener
{
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setLastLoginAt(new \DateTime());

        $this->userRepository->save($user, true);
    }
}
```

## Templates

The 2FA workflow uses:

```text
security/2fa_setup.html.twig
security/2fa_form.html.twig
```

The first template is responsible for authenticator enrollment and QR code display.

The second template is used when a six-digit TOTP code is required during authentication.
