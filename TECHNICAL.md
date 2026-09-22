# TECHNICAL.md

## Stack

See the `README.md` file for the complete technology stack.

---

## Development Principles

- Keep controllers thin
- Prefer dependency injection
- Use DTOs for structured input data
- Keep persistence logic inside repositories
- Use entity validation for domain constraints
- Avoid N+1 database queries
- Keep frontend behavior isolated in Stimulus controllers
- Use Turbo Frames where partial page updates improve UX
- Decouple secondary behaviors through events
- Let Messenger handle retries and failure natively for async operations
- Maintain strict static analysis with PHPStan
- Cover application behavior with automated tests

---

## SOLID Principles

The application follows SOLID principles where appropriate:

- Controllers focused on HTTP/application orchestration
- Repositories responsible for database queries
- Dedicated form types for form configuration
- DTOs for structured filter input
- Subscribers/listeners for cross-cutting concerns
- Factories for notification creation
- Services receiving dependencies through constructor injection

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

For event-driven operations (contact form — synchronous):

```text
Controller
     ↓
ContactRequestEvent
     └── MailingSubscriber → sends email, catches errors, marks event as failed
```

For async operations (account verification, password reset):

```text
Controller
     ↓
Message dispatched via Messenger (async)
     ↓
MessageHandler
     ↓
Event dispatched
     └── MailingSubscriber → sends email (exceptions propagate to Messenger)
```

---

## Administration Back-Office

The project includes a dedicated administration back-office with:

- Recipe management
- Category management
- Ingredient and quantity management
- Unit management
- Recipe creation without requiring ingredients
- Online/offline recipe status management
- Responsive sidebar navigation
- Sortable recipe listings
- Pagination
- Bootstrap-based interface
- Turbo-powered modal forms
- Client-side image preview
- Password visibility toggle
- Responsive design for desktop, tablet and mobile

The administration interface also includes UX improvements such as contextual image actions, responsive form layouts and optimized controls for managing dynamic ingredient collections.

---

## Symfony UX

### Turbo

[Hotwire Turbo](https://turbo.hotwired.dev/) is integrated via `symfony/ux-turbo`.

- **Turbo Drive** — page navigation without full page reloads
- **Turbo Frames** — partial page updates, notably for edit modals
- **Turbo Streams** — dynamic DOM updates

### Stimulus

[Stimulus](https://stimulus.hotwired.dev/) is a lightweight JavaScript framework based on controllers attached to HTML elements using `data-controller`.

Used for:

- Sidebar dropdown open/close behavior
- Image preview before upload (`thumbnail_preview_controller`)
- Dynamic form collections for ingredients and quantities (`form-collection_controller`)
- Password visibility toggle (`password-visibility_controller`)

---

## Asset Management

The project uses **Symfony AssetMapper** instead of a traditional JavaScript bundler. No Node.js or Webpack build is required.

Dependencies are declared in `importmap.php`:

```php
'bootstrap' => ['version' => '5.3.8'],
'@hotwired/turbo' => ['version' => '7.3.0'],
'@hotwired/stimulus' => ['version' => '3.2.2'],
```

---

## Turbo Frame Modal

The application uses Bootstrap modals combined with Turbo Frames to edit entities without a full page reload.

Clicking the **Edit** button or the recipe image opens the corresponding form inside a Bootstrap modal.

### How it works

1. The link contains the `data-turbo-frame="modal"` attribute.
2. Turbo intercepts the navigation and sends a request with the `Turbo-Frame: modal` header.
3. Symfony detects the header and returns only the `<turbo-frame id="modal">` content.
4. JavaScript detects the frame load and triggers `modal.show()`.

### Index page

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

```twig
{% if app.request.headers.get('turbo-frame') == 'modal' %}
    <turbo-frame id="modal">
        {# Modal content #}
    </turbo-frame>
{% else %}
    {# Full page content #}
{% endif %}
```

### Controller

The form action is explicitly configured to avoid URL context issues with Turbo Frames:

```php
$form = $this->createForm(RecipeType::class, $recipe, [
    'action' => $this->generateUrl('admin.recipe.edit', [
        'id' => $recipe->getId(),
    ]),
]);
```

Invalid form submissions return HTTP `422` so Turbo keeps the modal open and displays validation errors:

```php
$status = $form->isSubmitted() && !$form->isValid() ? 422 : 200;

return $this->render(
    'admin/recipe/edit.html.twig',
    [...],
    new Response(status: $status)
);
```

---

## Tom Select

[Tom Select](https://tom-select.js.org/) enhances `<select>` fields with:

- Real-time search
- Multi-selection
- Custom placeholders
- On-the-fly entity creation

### Ingredient creation on the fly

When the entered ingredient does not exist, Tom Select displays an option to create it.

The application sends a `POST` request to `/ingredient/create-ajax` to create the ingredient without leaving the recipe form.

### Multi-select with placeholder

```php
'tom_select_options' => [
    'placeholder' => 'Choisir un ou des tags',
],
```

This is preferable to the standard Symfony `placeholder` option when `multiple => true` is used.

---

## Symfony UX Autocomplete

Custom autocomplete fields are implemented using `AsEntityAutocompleteField` and `BaseEntityAutocompleteType`.

Example for tags:

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

Autocomplete is used for: categories, tags, ingredients.

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

The recipe listing supports filtering by title, category and tags through a dedicated filter form using the `GET` method:

```php
$resolver->setDefaults([
    'method' => 'GET',
    'csrf_protection' => false,
]);
```

Filter data is represented by a dedicated DTO for strong typing:

```php
final class RecipeFilter
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

- Maximum file size: `7000k`
- Accepted formats: `image/jpeg`, `image/png`, `image/webp`
- Maximum dimensions: `1080x1080`

Uploaded images are automatically converted to WebP format by `ImageConvertSubscriber`.

### Image forms

- `RecipeType` — complete recipe form including image upload
- `RecipeThumbnailType` — dedicated image-only form (used when clicking the card image)

`RecipeThumbnailType` is embedded in `RecipeType` using `inherit_data: true`.

### Image preview

A Stimulus controller provides a client-side image preview using the browser `FileReader` API.

---

## Forms

### RecipeType

Main recipe form containing: title, slug, category, content, duration, online status, thumbnail, tags, ingredients and quantities.

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

This ensures quantities are deleted when removed from the recipe.

---

## Doctrine Query Optimization

Repository queries use fetch joins to avoid N+1 problems:

```php
$qb = $this->createQueryBuilder('recipe')
    ->select('recipe', 'category', 'tag')
    ->leftJoin('recipe.category', 'category')
    ->leftJoin('recipe.tags', 'tag');
```

---

## Event-Driven Architecture

### Contact form (synchronous)

The contact form uses a synchronous event-driven approach because the result must be returned to the user immediately.

### Async user notifications

For account verification and password reset, failure handling is delegated entirely to Messenger. No `try/catch` is needed in the subscriber — exceptions propagate naturally and Messenger handles retries and dead-lettering.

```php
// MailingSubscriber — async user notifications
public function onUserVerifyRequestEvent(UserVerifyRequestEvent $event): void
{
    $this->sendUserEmail(
        $event->user,
        'Confirmation de votre compte',
        'emails/user/user_account_confirmation.html.twig',
        ['signedUrl' => $event->getSignatureUrl()]
    );
}

private function sendUserEmail(User $user, string $subject, string $template, array $context): void
{
    $this->notificationFactory
        ->createForUser()
        ->sendToUser($user, $subject, $template, $context);
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

- `ContactNotificationInterface::send(ContactDTO $data)` — sends contact emails
- `UserNotificationInterface::sendToUser(User $user, ...)` — sends emails to a user

The factory depends on interfaces, not concrete implementations.

---

## Asynchronous Messaging

### Transports

| Transport | Queue | Purpose |
|-----------|-------|---------|
| `async` | default | Generic async messages |
| `async-contact` | async-contact | Contact form emails |
| `async-pdf` | async-pdf | Recipe PDF generation |
| `async-user-account-verify` | async-user-account-verify | Account verification emails |
| `async-user-reset-password` | async-user-reset-password | Password reset emails |

Each transport has a dedicated failure transport (e.g. `async-contact-failed`) using Doctrine. The global `failed` transport catches everything else.

### Retry strategy

Each transport retries up to 3 times with an exponential backoff (multiplier: 2). After 3 failures, the message is moved to the corresponding failure transport.

### Message flow

```text
Controller
     ↓
Message dispatched (e.g. UserVerifyAccountMessage)
     ↓
MessageHandler (async, via Messenger worker)
     ↓
Event dispatched
     └── MailingSubscriber → sends email
                              (exception propagates → Messenger retries)
```

---

## Authentication

The application uses Symfony Security for authentication.

### User hierarchy

The application uses Doctrine single-table inheritance to manage different types of users:

```text
User (base entity)
 ├── Admin  → discriminator: admin
 └── Consumer → discriminator: consumer
```

The `discriminator` column in the `user` table determines the type of user.

### Roles

- `ROLE_USER` — automatically assigned to every user
- `ROLE_ADMIN` — assigned to every `Admin` entity through `getRoles()`

### Routes and access control

| Route | Path | Access |
|-------|------|--------|
| `app_login` | `/login` | PUBLIC |
| `app_register` | `/inscription` | PUBLIC |
| `app_activate_account` | `/activate` | PUBLIC |
| `app_forgot_password_request` | `/reset-password` | PUBLIC |
| `app_check_email` | `/reset-password/check-email` | PUBLIC |
| `app_reset_password` | `/reset-password/reset/{token}` | PUBLIC |
| `app_logout` | `/logout` | Authenticated |
| `home` | `/` | `IS_AUTHENTICATED_FULLY` |
| Admin routes | `/admin/*` | `ROLE_ADMIN` |

### Security configuration

```yaml
access_control:
    - { path: ^/maintenance, roles: PUBLIC_ACCESS }
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/inscription, roles: PUBLIC_ACCESS }
    - { path: ^/contact, roles: PUBLIC_ACCESS }
    - { path: ^/activate, roles: PUBLIC_ACCESS }
    - { path: ^/reset-password, roles: PUBLIC_ACCESS }
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/, roles: IS_AUTHENTICATED_FULLY }
```

### Login

The login form uses `form_login` provided by Symfony Security.

After successful authentication, the user is redirected to the `home` route.

CSRF protection is enabled on the login form:

```yaml
form_login:
    login_path: app_login
    check_path: app_login
    default_target_path: home
    enable_csrf: true
```

### Registration

The registration form creates an `Admin` entity directly.

Doctrine automatically sets the `discriminator` column to `admin` when persisting an `Admin` instance.

Password constraints are enforced at form level:

- Minimum 12 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one digit
- At least one special character (`@$!%*?&^#`)

After registration, a `UserVerifyAccountMessage` is dispatched asynchronously to send a verification email.

### Remember me

The remember me feature is enabled with a lifetime of 7 days:

```yaml
remember_me:
    secret: '%kernel.secret%'
    lifetime: 604800
    path: /
    always_remember_me: true
```

### Tests

Tests requiring authentication use the `loginUser()` method provided by Symfony:

```php
private function createAuthenticatedClient(): KernelBrowser
{
    $client = static::createClient();
    $admin = new Admin();
    $admin->setEmail('admin@test.com');
    $admin->setPassword($passwordHasher->hashPassword($admin, 'password'));
    $em->persist($admin);
    $em->flush();
    $client->loginUser($admin);

    return $client;
}
```

---

## Email Verification

Account verification is handled by **symfonycasts/verify-email-bundle**.

### How it works

1. After registration, a `UserVerifyAccountMessage` is dispatched asynchronously.
2. The `UserVerifyAccountMessageHandler` generates a signed URL using `VerifyEmailHelperInterface`.
3. A `UserVerifyRequestEvent` is dispatched and handled by `MailingSubscriber`.
4. The user receives an email with the signed URL.
5. Clicking the link triggers `VerifyEmailHelperInterface::validateEmailConfirmationFromRequest()`.
6. If valid, the user is marked as verified (`isVerified = true`) and the confirmation token is cleared.

### Configuration

```yaml
# config/packages/verify_email.yaml
symfonycasts_verify_email:
    lifetime: 86400 # 24 hours
```

The signed URL is generated without exposing the token directly in the email template:

```php
$signatureComponents = $this->verifyEmailHelper->generateSignature(
    'app_activate_account',
    (string) $user->getId(),
    (string) $user->getEmail(),
    ['id' => $user->getId(), 'token' => $message->token]
);
```

---

## Password Reset

Password reset is handled by **symfonycasts/reset-password-bundle**.

### How it works

1. The user submits their email on `/reset-password`.
2. If the user exists, a `UserResetPasswordMessage` is dispatched asynchronously.
3. The `UserResetPasswordMessageHandler` generates a reset token using `ResetPasswordHelperInterface`.
4. A `UserResetPasswordRequestEvent` is dispatched and handled by `MailingSubscriber`.
5. The user receives an email with a secure reset link valid for **30 minutes**.
6. The token is stored in the session (not in the URL) for security (anti-leak pattern).
7. After a successful password change, the token is invalidated immediately.

### Configuration

```yaml
# config/packages/reset_password.yaml
symfonycasts_reset_password:
    request_password_repository: App\Repository\ResetPasswordRequestRepository
    lifetime: 1800  # 30 minutes
    throttle_limit: 3
```

### Security note

The controller never reveals whether an email exists in the database. In all cases the user is redirected to `/reset-password/check-email`.

---

## Maintenance Mode

Access is restricted to a configured IP whitelist when maintenance mode is enabled.

### Configuration

```dotenv
ACTIVE_MAINTENANCE_PAGE=1
ALLOWED_IP=57.128.19.245,192.168.1.10
```

### How it works

The `MaintenanceListener` listens to `kernel.request`:

1. Only the main HTTP request is processed.
2. Requests to `/maintenance` are always allowed.
3. The client IP is compared against `ALLOWED_IP`.
4. Allowed IPs bypass maintenance mode.
5. All other requests receive an HTTP `302` redirect to `/maintenance`.

```php
if (!$event->isMainRequest()) {
    return;
}

if ('/maintenance' === $request->getPathInfo()) {
    return;
}
```

---

## Validation

Validation is applied at entity level using Symfony Validator constraints.

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

Validation groups allow certain constraints to be applied only in specific contexts (e.g. form creation vs API).

---

## Testing

Tests are written with **PHPUnit**.

### Shared fixtures

```php
trait FixturesTrait
{
    private function createCategory(EntityManagerInterface $em, string $name = 'Apéritif'): Category { ... }
    private function createRecipe(EntityManagerInterface $em, string $title = 'Recette'): Recipe { ... }
}
```

### CSRF in tests

CSRF is kept enabled in tests because:

- Tests reflect real-world production conditions.
- CSRF token handling is verified.
- Retrieving the token from HTML mirrors actual user behavior.

### Test isolation

The project uses `dama/doctrine-test-bundle` to wrap each test in a transaction that is automatically rolled back.

### JSON responses

```php
/** @return array<string, mixed> */
private function decodeResponse(KernelBrowser $client): array
{
    $content = $client->getResponse()->getContent();
    assert(is_string($content));

    /** @var array<string, mixed> $response */
    $response = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    return $response;
}
```

---

## Database Fixtures

| Fixture | Purpose |
|---------|---------|
| `IngredientFixtures` | Pre-fills the `ingredient` table |
| `UnitFixtures` | Pre-fills the `unit` table |
| `RecipeFixtures` | Generates sample recipes with ingredients, quantities and units |

`RecipeFixtures` depends on `IngredientFixtures` and `UnitFixtures`.

---

## Code Quality

| Tool | Purpose |
|------|---------|
| **PHPStan** | Static analysis |
| **PHP-CS-Fixer** | PHP code style |
| **Twig CS Fixer** | Twig code style |
| **GrumPHP** | Pre-commit quality checks |
| **PHPUnit** | Automated testing |

---

## Dependency Injection

Services receive their dependencies through constructor injection:

```php
readonly class MaintenanceListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }
}
```

---

## Mailpit

Mailpit captures outgoing emails locally without sending them to real recipients.

- SMTP: `localhost:1025`
- Web interface: http://localhost:8025/
