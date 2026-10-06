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

---

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

---

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

---

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

* `recipe.menu`
* `recipe.create`
* `recipe.edit`
* `recipe.delete`
* `recipe.promote`

### Categories

* `category.create`
* `category.edit`
* `category.delete`

### Tags

* `tag.create`
* `tag.edit`
* `tag.delete`

### Ingredients

* `ingredient.create`
* `ingredient.edit`
* `ingredient.delete`

### Users

* `user.create`
* `user.edit`
* `user.delete`

Grouping permissions in the enum makes the administration interface easier to maintain and allows the permission-management form to be generated dynamically.

---

## Admin Entity

Permissions are stored directly on the `Admin` entity as a JSON array:

```php
#[ORM\Column(type: 'json')]
private array $permissions = [];
```

The entity exposes a dedicated permission check:

```php
public function hasPermission(Permission $permission): bool
{
    return in_array($permission->value, $this->permissions, true);
}
```

The permission list contains the technical values defined by the `Permission` enum.

Super Admins bypass the individual permission list and are automatically authorized for every permission.

---

## Symfony Voter

Fine-grained authorization is handled by:

```text
src/Security/Voter/AdminVoter.php
```

The voter supports the permissions declared by the `Permission` enum.

Its authorization flow is:

```text
Symfony Security
       │
       ▼
   AdminVoter
       │
       ├── Not an Admin → DENY
       │
       ├── Super Admin → GRANT
       │
       └── Regular Admin
              │
              ▼
        hasPermission()
              │
          GRANT / DENY
```

The voter first ensures that the authenticated user is an `Admin`.

A Super Admin is automatically granted access.

For a regular administrator, the voter checks whether the requested permission exists in the administrator's permission list.

Controllers therefore use Symfony's `#[IsGranted]` attribute with the permission value instead of implementing authorization logic themselves:

```php
#[IsGranted(Permission::RECIPE_EDIT->value)]
public function edit(Recipe $recipe, Request $request): Response
{
    // ...
}
```

This keeps authorization centralized and prevents permission checks from being duplicated across controllers.

---

## Controller-Level Protection

Administrative controllers are protected at two levels:

1. `#[IsGranted('ROLE_ADMIN')]` protects the administration area at a broad level.
2. `#[IsGranted(Permission::...->value)]` protects individual operations such as create, edit and delete.

For example, recipe management uses separate permissions for creation, edition, deletion and promotion.

Category and tag controllers follow the same pattern.

The ingredient controller currently exposes the AJAX creation endpoint because ingredients do not have a dedicated administration menu. Ingredient creation is therefore protected by `ingredient.create`.

---

## Object-Level Authorization

For operations where authorization depends on a specific entity, the controller can pass the entity to the voter:

```php
$this->denyAccessUnlessGranted(
    Permission::RECIPE_EDIT->value,
    $recipe
);
```

This allows the voter to evolve later if authorization rules become dependent on the specific resource.

---

## Administration Interface

The Super Admin has access to a dedicated permission-management interface.

The interface:

* lists administrators;
* allows filtering by email;
* prevents modification of Super Admin permissions;
* allows regular administrators to be assigned permissions;
* groups permissions by functional area;
* provides controls to select or deselect all permissions in a group;
* allows the administrator status to be managed from the listing.

The permission form is built dynamically from `Permission::cases()`.

Adding a new permission enum case therefore makes the permission available to the administration interface without duplicating the permission definition inside the form.

---

## Twig Visibility

The templates also hide actions that the current administrator cannot perform.

For example:

```twig
{% if app.user.isSuperAdmin() or is_granted('recipe.edit') %}
    <a href="{{ path('admin.recipe.edit', {id: recipe.id}) }}">
        Modifier
    </a>
{% endif %}
```

These checks are only a **user-interface convenience**.

They do not replace server-side authorization.

The controller remains protected by `#[IsGranted]` and the voter.

This distinction ensures that manually calling a protected URL or endpoint cannot bypass the permission system.

---

## Permission Architecture

The overall flow is:

```text
Permission enum
      │
      ├── value / label / group
      │
      ▼
AdminPermissionsType
      │
      ▼
Admin.permissions (JSON)
      │
      ▼
AdminVoter
      │
      ├── ROLE_SUPER_ADMIN → GRANT
      │
      └── hasPermission() → GRANT / DENY
      │
      ▼
#[IsGranted(...)] on controllers
      │
      ▼
Protected administration action
```

The permission system is therefore centralized around the `Permission` enum and `AdminVoter`, while controllers remain responsible only for declaring which permission is required.

---

## Symfony UX

### Turbo

[Hotwire Turbo](https://turbo.hotwired.dev/) is integrated via `symfony/ux-turbo`.

The application uses the following Turbo features:

* **Turbo Drive** — page navigation without full page reloads
* **Turbo Frames** — partial page updates, notably for edit modals
* **Turbo Streams** — dynamic DOM updates

### Stimulus

[Stimulus](https://stimulus.hotwired.dev/) is integrated into the application.

It is a lightweight JavaScript framework based on controllers attached to HTML elements using `data-controller`.

Stimulus is used for:

* Sidebar dropdown open/close behavior
* Image preview before upload (`thumbnail_preview_controller`)
* Dynamic form collections for ingredients and quantities (`form-collection_controller`)
* Password visibility toggle (`password-visibility_controller`)
* AJAX interactions
* Dynamic Bootstrap modal interactions
* Drag & drop interfaces

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

* Direct browser navigation
* Turbo Frame modal navigation

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

## AJAX Bootstrap Modal with Stimulus

The application also uses Bootstrap modals combined with Stimulus and AJAX for interactions that do not require Turbo Frames.

A concrete example is the **management of promoted recipes**.

The feature allows administrators to:

* Load recipes dynamically when the modal opens
* Search available recipes
* Drag recipes between two lists
* Reorder promoted recipes
* Limit the number of promoted recipes
* Reset the current selection
* Save the selected order through AJAX
* Warn the user when closing the modal with unsaved changes

The recipe lists are intentionally **not rendered in the index page**.

Only the modal structure is rendered initially. The recipe data is loaded from Symfony through an AJAX request when the modal is opened.

### Responsibilities

The architecture deliberately separates the responsibilities of each layer:

```text
Bootstrap
    │
    └── Modal lifecycle
         ├── Open
         ├── Close
         ├── Backdrop
         └── Body state

Stimulus
    │
    └── Client-side interaction
         ├── AJAX loading
         ├── Rendering
         ├── Search
         ├── Drag & drop
         ├── Reordering
         └── Unsaved changes detection

Symfony Controller
    │
    └── HTTP/API layer
         ├── Authorization
         ├── CSRF validation
         ├── JSON parsing
         └── JSON response

RecipeManager
    │
    └── Business rules
         ├── Maximum number of promoted recipes
         ├── Validation of recipe IDs
         ├── Promoted state
         └── Position management

RecipeRepository
    │
    └── Persistence queries

Doctrine ORM
    │
    ▼
MySQL
```

This separation is important because Bootstrap should remain responsible for the modal lifecycle, while Stimulus manages the interactive client-side behavior and Symfony remains authoritative for business rules.

### Modal Structure

The promoted recipes modal is attached to a dedicated Stimulus controller:

```twig
<div
    class="modal fade"
    id="modalPromotedRecipes"
    tabindex="-1"
    aria-labelledby="modalPromotedRecipesLabel"
    aria-hidden="true"
    data-controller="promoted-recipes"
    data-promoted-recipes-save-url-value="{{ path('admin.recipe.promoted_save') }}"
    data-promoted-recipes-load-url-value="{{ path('admin.recipe.promoted_load') }}"
    data-promoted-recipes-csrf-token-value="{{ csrf_token('promoted_recipes') }}"
    data-promoted-recipes-max-value="10"
    data-action="shown.bs.modal->promoted-recipes#open hide.bs.modal->promoted-recipes#beforeClose"
>
    ...
</div>
```

The modal uses Bootstrap's native events:

* `shown.bs.modal` — triggered once the modal is fully visible
* `hide.bs.modal` — triggered when Bootstrap is about to close the modal

Stimulus listens to these events to perform the required application logic.

### Loading Data Only When the Modal Opens

The index page does not render the recipe lists.

The containers initially remain empty:

```twig
<div
    class="recipe-list border rounded p-2"
    data-promoted-recipes-target="available"
></div>

<div
    class="recipe-list border rounded p-2"
    data-promoted-recipes-target="chosen"
></div>
```

When the modal is opened, Bootstrap emits `shown.bs.modal`.

Stimulus then calls the loading method:

```js
async open() {
    if (this.isLoading) {
        return;
    }

    this.hasChanges = false;
    this.isSaved = false;
    this.searchTarget.value = '';

    await this.loadRecipes();
}
```

The recipes are retrieved through an AJAX request:

```js
const response = await fetch(this.loadUrlValue, {
    method: 'GET',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
    credentials: 'same-origin',
});
```

This approach ensures that each opening of the modal retrieves the current database state.

The modal therefore does not rely on potentially outdated data that was rendered when the page was initially loaded.

### JSON Response

The Symfony controller returns the recipes required by the interface:

```php
return $this->json(
    array_map(
        static fn (Recipe $recipe): array => [
            'id' => $recipe->getId(),
            'title' => $recipe->getTitle(),
            'promoted' => $recipe->isPromoted(),
            'position' => $recipe->getPosition(),
        ],
        $recipes
    )
);
```

The frontend receives objects rather than only recipe IDs because it needs the recipe title and current promotion state to render both lists.

### JSON Response Validation

AJAX responses should not blindly assume that `response.json()` will succeed.

For example, Symfony can return an HTML response in case of:

* Authentication redirection
* Authorization failure
* Internal server error
* Incorrect route
* Unexpected framework error

The Stimulus controller therefore checks the response content type before decoding it:

```js
const contentType = response.headers.get('content-type') ?? '';

if (!response.ok) {
    const body = contentType.includes('application/json')
        ? await response.json().catch(() => null)
        : await response.text().catch(() => '');

    throw new Error(
        body?.message ?? `Erreur HTTP ${response.status}.`
    );
}

if (!contentType.includes('application/json')) {
    throw new Error(
        'Le serveur n’a pas retourné du JSON. Vérifiez les droits et la route de chargement.'
    );
}
```

This avoids hiding the actual server-side problem behind a generic JavaScript error such as:

```text
Unexpected token '<'
```

### Rendering the Two Lists

The received recipes are separated into two collections:

```text
Recipes
   │
   ├── promoted = true
   │       └── Promoted recipes
   │
   └── promoted = false
           └── Available recipes
```

Promoted recipes are ordered using their persisted `position`.

Available recipes are sorted alphabetically by title.

The DOM is then rebuilt from the JSON response.

The server remains the source of truth for the persisted state.

### SortableJS

Drag & drop is implemented with **SortableJS**.

Two sortable lists are created:

```text
Recettes disponibles
        ⇅
Recettes mises en avant
```

Both lists belong to the same SortableJS group:

```js
group: {
    name: 'promoted-recipes',
    pull: true,
    put: true,
},
```

This allows recipes to be moved between the two lists and reordered within the promoted list.

The drag handle is explicitly defined:

```js
handle: '.drag-handle',
```

This avoids making the entire recipe row draggable and leaves other interactions available on the row.

### SortableJS Lifecycle

SortableJS instances are created **after the recipe lists have been rendered**.

They are not initialized against empty containers during Stimulus `connect()`.

The controller keeps explicit references:

```js
this.availableSortable = null;
this.chosenSortable = null;
```

Before rebuilding the lists, existing instances are destroyed:

```js
destroySortable() {
    this.availableSortable?.destroy();
    this.chosenSortable?.destroy();

    this.availableSortable = null;
    this.chosenSortable = null;
}
```

This prevents multiple SortableJS instances from being attached to the same DOM elements after repeated modal openings.

### Maximum of 10 Promoted Recipes

The application limits the number of promoted recipes to **10**.

The limit is enforced on the client side to provide immediate feedback.

SortableJS can prevent an invalid drop before it happens:

```js
onMove: (event) => {
    if (event.to !== this.chosenTarget) {
        return true;
    }

    if (event.from === this.chosenTarget) {
        return true;
    }

    return this.chosenTarget.querySelectorAll('.recipe-item').length
        < this.maxValue;
},
```

This provides a better user experience than accepting an invalid drop and moving the element back afterward.

However, this client-side validation is **not considered a security or business rule**.

The backend independently enforces the maximum of 10 recipes.

This is important because client-side JavaScript can always be bypassed by sending a direct HTTP request.

### Reordering

Whenever the promoted list changes, the displayed positions are recalculated:

```js
updatePositions() {
    this.chosenTarget
        .querySelectorAll('.recipe-item')
        .forEach((recipe, index) => {
            const position =
                recipe.querySelector('.recipe-position');

            if (position) {
                position.textContent = index + 1;
            }
        });
}
```

The browser position is therefore always:

```text
1
2
3
...
10
```

The actual persisted positions are sent to the backend indirectly through the ordered recipe ID list.

For example:

```json
{
    "recipes": [
        "12",
        "7",
        "25",
        "4"
    ]
}
```

The backend interprets the array order as the desired promotion order.

### Save Button

The save button uses Bootstrap's native modal dismissal mechanism:

```twig
<button
    type="button"
    class="btn btn-primary"
    data-bs-dismiss="modal"
    data-promoted-recipes-target="saveButton"
    data-action="click->promoted-recipes#save"
>
    <i class="fas fa-save me-1"></i>
    Enregistrer
</button>
```

The important point is the presence of:

```text
data-bs-dismiss="modal"
```

The Stimulus controller performs the AJAX save, but it does **not** manually close the Bootstrap modal.

After a successful save:

```js
this.hasChanges = false;
this.isSaved = true;
```

The Bootstrap button then closes the modal itself.

This provides a clean separation:

```text
Stimulus
    │
    └── Save data

Bootstrap
    │
    └── Close modal
```

### Why the Modal Must Not Be Closed Manually

The Stimulus controller must not manually call:

```js
modal.hide();
```

after a successful save.

Bootstrap already manages:

* Modal visibility
* Backdrop creation
* Backdrop removal
* `modal-open` on `<body>`
* Body overflow
* Body padding compensation
* Modal transition lifecycle
* `shown.bs.modal`
* `hide.bs.modal`
* `hidden.bs.modal`

Manually reproducing this logic creates a risk of conflicting with Bootstrap's own lifecycle.

The controller must therefore not manually remove:

```text
.modal-backdrop
```

or:

```text
body.modal-open
```

and must not manually modify:

```text
body.style.overflow
body.style.paddingRight
```

Bootstrap owns these responsibilities.

### Unsaved Changes

The controller tracks whether the current modal contains unsaved changes:

```js
this.hasChanges = false;
this.isSaved = false;
```

Moving, reordering or resetting recipes changes the state:

```js
this.hasChanges = true;
this.isSaved = false;
```

Before the modal closes, Stimulus listens to:

```text
hide.bs.modal
```

and asks for confirmation if necessary:

```js
beforeClose(event) {
    if (!this.hasChanges || this.isSaved || this.isSaving) {
        return;
    }

    const confirmed = confirm(
        'Attention : les modifications effectuées ne vont pas être enregistrées.\n\nÊtes-vous sûr de vouloir quitter ?'
    );

    if (!confirmed) {
        event.preventDefault();
    }
}
```

The important rule is that `beforeClose()` does **not** perform an asynchronous reload.

It only decides whether Bootstrap is allowed to close the modal.

This avoids a race condition between the modal lifecycle and an asynchronous request.

The current database state is simply reloaded the next time the modal is opened.

### Reset

The reset action moves every currently promoted recipe back to the available list:

```js
reset() {
    if (!confirm(
        'Êtes-vous sûr de vouloir réinitialiser les recettes mises en avant ?'
    )) {
        return;
    }

    const recipes = [
        ...this.chosenTarget.querySelectorAll('.recipe-item'),
    ];

    recipes.forEach((recipe) => {
        this.availableTarget.appendChild(recipe);
    });

    this.hasChanges = true;
    this.isSaved = false;

    this.updatePositions();
}
```

The reset only modifies the client-side state.

The database is not modified until the user clicks **Enregistrer**.

### Search

The available recipe list can be filtered client-side.

The search is performed against the recipe title already loaded in the modal:

```js
filter() {
    const value =
        this.searchTarget.value.trim().toLowerCase();

    this.availableTarget
        .querySelectorAll('.recipe-item')
        .forEach((recipe) => {
            const name =
                recipe.dataset.name.toLowerCase();

            recipe.classList.toggle(
                'd-none',
                value !== '' && !name.includes(value)
            );
        });
}
```

No additional HTTP request is required for each search operation.

This keeps the interaction responsive while avoiding unnecessary server requests.

### Symfony Authorization

The AJAX endpoints are protected by the same permission system as the rest of the administration.

The controller uses:

```php
#[IsGranted(Permission::RECIPE_PROMOTE->value)]
```

This is applied to both:

* Loading promoted recipes
* Saving promoted recipes

The frontend therefore does not determine whether the user is authorized.

The permission is enforced server-side.

### Load Endpoint

The load endpoint is a `GET` request:

```php
#[IsGranted(Permission::RECIPE_PROMOTE->value)]
#[Route('/promoted/load', name: 'promoted_load', methods: ['GET'])]
public function promotedLoad(): JsonResponse
{
    $recipes = $this->recipeRepository->findAllForPromotion();

    return $this->json(
        array_map(
            static fn (Recipe $recipe): array => [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'promoted' => $recipe->isPromoted(),
                'position' => $recipe->getPosition(),
            ],
            $recipes
        )
    );
}
```

The endpoint only exposes the information required by the promotion interface.

It does not expose the complete recipe entity.

### Save Endpoint

The save endpoint uses `PATCH` because the operation modifies the current promoted state:

```php
#[IsGranted(Permission::RECIPE_PROMOTE->value)]
#[Route('/promoted', name: 'promoted_save', methods: ['PATCH'])]
public function promotedSave(Request $request): JsonResponse
{
    ...
}
```

The request body contains the ordered recipe IDs:

```json
{
    "recipes": [
        "12",
        "7",
        "25"
    ]
}
```

The controller is responsible for:

* CSRF validation
* JSON parsing
* Basic request structure validation
* Calling the manager
* Returning the appropriate HTTP status

It does not contain the promotion business rules.

### CSRF Protection

The promotion operation uses a dedicated CSRF token:

```twig
data-promoted-recipes-csrf-token-value="{{ csrf_token('promoted_recipes') }}"
```

The token is sent through the request header:

```js
'X-CSRF-TOKEN': this.csrfTokenValue,
```

The controller validates it:

```php
if (!$this->isCsrfTokenValid(
    'promoted_recipes',
    $request->headers->get('X-CSRF-TOKEN')
)) {
    return $this->json(
        ['message' => 'Token CSRF invalide.'],
        Response::HTTP_FORBIDDEN
    );
}
```

This protects the state-changing AJAX operation against CSRF attacks.

### RecipeManager

The promotion business rules are handled by `RecipeManager`.

The controller should remain thin and delegate the operation:

```php
$this->recipeManager->updatePromotedRecipes($recipeIds);
```

The manager is responsible for:

* Validating recipe IDs
* Removing duplicates
* Enforcing the maximum of 10 recipes
* Ensuring all requested recipes exist
* Resetting previous promoted recipes
* Assigning the new promoted state
* Assigning positions
* Flushing the changes

Example:

```php
final readonly class RecipeManager
{
    private const int MAX_PROMOTED_RECIPES = 10;

    public function __construct(
        private RecipeRepository $recipeRepository,
    ) {
    }

    /**
     * @param list<string> $recipeIds
     */
    public function updatePromotedRecipes(array $recipeIds): void
    {
        $ids = [];

        foreach ($recipeIds as $recipeId) {
            if (!ctype_digit($recipeId) || (int) $recipeId <= 0) {
                throw new \DomainException(
                    'Une ou plusieurs recettes sélectionnées sont invalides.'
                );
            }

            $ids[] = (int) $recipeId;
        }

        $ids = array_values(array_unique($ids));

        if (count($ids) > self::MAX_PROMOTED_RECIPES) {
            throw new \DomainException(
                sprintf(
                    'Impossible de mettre plus de %d recettes en avant.',
                    self::MAX_PROMOTED_RECIPES
                )
            );
        }

        $recipes = $this->recipeRepository->findByIds($ids);

        if (count($recipes) !== count($ids)) {
            throw new \DomainException(
                'Une ou plusieurs recettes sélectionnées sont introuvables.'
            );
        }

        $recipesById = [];

        foreach ($recipes as $recipe) {
            $recipesById[$recipe->getId()] = $recipe;
        }

        foreach ($this->recipeRepository->findPromotedRecipes() as $recipe) {
            $recipe->setPromoted(false);
            $recipe->setPosition(null);
        }

        foreach ($ids as $position => $id) {
            $recipe = $recipesById[$id];

            $recipe->setPromoted(true);
            $recipe->setPosition($position + 1);
        }

        $this->recipeRepository->flush();
    }
}
```

The manager therefore remains independent from the HTTP layer.

The same business operation could later be called from another interface without duplicating the rules.

### Repository

Persistence-specific queries remain inside `RecipeRepository`.

For example, promoted recipes can be retrieved with:

```php
/**
 * @return list<Recipe>
 */
public function findPromotedRecipes(): array
{
    return $this->createQueryBuilder('recipe')
        ->andWhere('recipe.promoted = :promoted')
        ->setParameter('promoted', true)
        ->orderBy('recipe.position', 'ASC')
        ->getQuery()
        ->getResult();
}
```

This allows the manager to work with actual Doctrine entities.

The manager then changes the entities through their domain methods:

```php
$recipe->setPromoted(false);
$recipe->setPosition(null);
```

and:

```php
$recipe->setPromoted(true);
$recipe->setPosition($position + 1);
```

This approach avoids performing bulk DQL updates that could bypass Doctrine's UnitOfWork and leave already-managed entities out of sync.

### Promotion Persistence Model

The recipe entity already contains:

```text
promoted
position
```

The values have the following meaning:

```text
promoted = false
position = null
```

means that the recipe is not promoted.

```text
promoted = true
position = 1..10
```

means that the recipe is promoted at the corresponding position.

The `Recipe` entity does not need to be modified specifically for the AJAX promotion interface.

The existing domain methods remain the single entry point for modifying these properties:

```php
$recipe->isPromoted();
$recipe->setPromoted(...);

$recipe->getPosition();
$recipe->setPosition(...);
```

### Important Modal Contract

The promoted recipes modal follows this rule:

```text
Bootstrap
    owns the modal lifecycle

Stimulus
    owns the client-side interaction

Symfony Controller
    owns HTTP validation and authorization

RecipeManager
    owns business rules

RecipeRepository
    owns persistence queries

Doctrine
    owns entity persistence
```

This separation should be preserved when modifying the feature.

In particular:

* Do not manually manipulate Bootstrap's backdrop.
* Do not manually add or remove `modal-open`.
* Do not manually modify body overflow or padding.
* Do not call `modal.hide()` from the Stimulus save method.
* Do not perform asynchronous reloads during `hide.bs.modal`.
* Do not move business rules into the JavaScript controller.
* Do not trust the client-side maximum of 10 as the only validation.
* Do not put repository queries inside the controller.
* Do not return the complete `Recipe` entity when a smaller JSON representation is sufficient.

The Bootstrap modal lifecycle should remain controlled by Bootstrap itself.

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
    lifetime: 1800 # 30 minutes
    throttle_limit: 3
```

### Security Note

The controller never reveals whether an email exists in the database.

In all cases the user is redirected to `/reset-password/check-email`.

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

### Shared Fixtures

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

### CSRF in Tests

CSRF is kept enabled in tests because:

* Tests reflect real-world production conditions.
* CSRF token handling is verified.
* Retrieving the token from HTML mirrors actual user behavior.

### Test Isolation

The project uses `dama/doctrine-test-bundle` to wrap each test in a transaction that is automatically rolled back.

### JSON Responses

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

* SMTP: `localhost:1025`
* Web interface: http://localhost:8025/

---

## Two-Factor Authentication (2FA)

Two-factor authentication is implemented using **scheb/2fa-bundle** with Google Authenticator (TOTP).

### Packages

```bash
composer require scheb/2fa-bundle scheb/2fa-google-authenticator
```

### How it works

1. The admin logs in with email and password.
2. If Google Authenticator is enabled on the account, the bundle intercepts the authentication and redirects to `/2fa`.
3. The user enters the 6-digit TOTP code from their authenticator app.
4. If the code is valid, the user is fully authenticated.

### Setup flow

The setup is handled by `TwoFactorController`:

1. A secret is generated via `GoogleAuthenticatorInterface::generateSecret()` and stored temporarily in the session.
2. The secret is temporarily set on the user entity to generate the QR code provisioning URI.
3. The secret is immediately cleared from the entity — it is **not** persisted yet.
4. A QR code is generated using **endroid/qr-code** and displayed to the user.
5. The user scans the QR code and enters the 6-digit code.
6. The code is verified using **spomky-labs/otphp** (`TOTP::verify()`).
7. If valid, the secret is persisted on the user entity and the session key is removed.

A 5-second leeway is applied during enrollment to tolerate clock drift at TOTP window boundaries.

### Configuration

```yaml
# config/packages/scheb_2fa.yaml
scheb_two_factor:
    security_tokens:
        - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
        - Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken
    google:
        enabled: true
        server_name: Recipes
        issuer: Recipes
        digits: 6
        leeway: 5
        template: security/2fa_form.html.twig
```

### Firewall configuration

```yaml
main:
    two_factor:
        auth_form_path: 2fa_login
        check_path: 2fa_login_check
```

### Access control

The 2FA form route must be declared first in `access_control`:

```yaml
access_control:
    - { path: ^/2fa, roles: IS_AUTHENTICATED_2FA_IN_PROGRESS }
    # ... other rules
```

### User entity

The `User` entity implements `TwoFactorInterface`:

```php
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleAuthenticatorSecret = null;

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
}
```

The `__serialize()` and `__unserialize()` methods explicitly include `googleAuthenticatorSecret` to ensure proper session handling.

### UserChecker

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

### Login success listener

A `LoginSuccessEventListener` updates `lastLoginAt` after each successful authentication:

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

### Templates

* `security/2fa_setup.html.twig` — QR code display and first code validation
* `security/2fa_form.html.twig` — 6-digit code entry on each login
