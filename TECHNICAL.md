# TECHNICAL.md

## Stack

- On the file README

---

## Symfony UX

### Turbo

[Hotwire Turbo](https://turbo.hotwired.dev/) is integrated via `symfony/ux-turbo`.

- **Turbo Drive** — page navigation without full reload
- **Turbo Frames** — partial page updates (used for the edit modal)
- **Turbo Streams** — real-time DOM updates

### Stimulus

[Stimulus](https://stimulus.hotwired.dev/) is integrated via `@hotwired/stimulus`.

A lightweight JavaScript framework based on **controllers** attached to HTML elements via `data-controller="my-controller"`.

Used for:
- Sidebar dropdown open/close
- Image preview before upload (`thumbnail_preview_controller`)
- Dynamic form collection for ingredients/quantities (`form-collection_controller`)

---

## Asset Management

The project uses **Symfony AssetMapper** (no Node.js / Webpack required).

Dependencies are declared in `importmap.php` and served via CDN (jsDelivr).

```php
// importmap.php
'bootstrap' => ['version' => '5.3.8'],
'@hotwired/turbo' => ['version' => '7.3.0'],
'@hotwired/stimulus' => ['version' => '3.2.2'],
```

---

## Turbo Frame Modal (Edit without page reload)

When clicking the **Edit** button or the **recipe image**, a Bootstrap modal opens without a full page reload.

### How it works

1. The link has `data-turbo-frame="modal"` attribute
2. Turbo intercepts the click and sends a request with the `Turbo-Frame: modal` header
3. Symfony detects this header in the template and returns only the `<turbo-frame id="modal">` content
4. The JS in `turbo-modal.js` detects the frame load and triggers `modal.show()`

### Index page

```twig
{# The empty Bootstrap modal waiting to be filled by Turbo #}
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

The form action must be explicitly set to avoid URL context issues with Turbo:

```php
$form = $this->createForm(RecipeType::class, $recipe, [
    'action' => $this->generateUrl('admin.recipe.edit', ['id' => $recipe->getId()]),
]);
```

The controller returns a `422` status code when the form is submitted with invalid data so Turbo keeps the modal open and displays validation errors:

```php
$status = $form->isSubmitted() && !$form->isValid() ? 422 : 200;
return $this->render('admin/recipe/edit.html.twig', [...], new Response(status: $status));
```

---

## Pagination

Pagination is handled by **KnpPaginatorBundle**.

```php
$pagination = $this->paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    $this->numberPerPageRecipe
);
```

Sortable columns are rendered with `knp_pagination_sortable()`.

---

## Image Upload

Images are managed by **VichUploaderBundle**.

- Accepted formats: `image/jpeg`, `image/png`, `image/webp`
- Max size: `7000k`
- Max dimensions: `1080x1080`
- Images are automatically converted to **WebP** format on upload via `ImageConvertSubscriber`

Two dedicated forms handle image uploads:
- `RecipeType` — full recipe form including image
- `RecipeThumbnailType` — image-only form (used when clicking the card image)

---

## Forms

### RecipeType

Full recipe form with:
- Title, slug (auto-generated if empty), category (with autocomplete), content, duration, online status
- Image upload via `RecipeThumbnailType` embedded with `inherit_data: true`
- Dynamic ingredients/quantities collection managed by a Stimulus controller

### RecipeThumbnailType

Dedicated form for image-only updates. Used when clicking the recipe card image.

---

## Tom Select

[Tom Select](https://tom-select.js.org/) is used to enhance `<select>` fields.

Features used in this project:
- **Real-time search** in the options list
- **Create on the fly** — when typing an ingredient that doesn't exist, Tom Select displays "Add Nuggets..." and sends a `POST` request to `/ingredient/create-ajax` to create it in the database
- **Multi-select** with tags

```javascript
// Example configuration
new TomSelect('#my-select', {
    create: true, // enables create on the fly
    onItemAdd: function(value) {
        // triggered when a new item is selected or created
    }
});
```
It replaces classic `<select>` elements for a better user experience, especially for the **ingredient selection** in the recipe form.

## Event-Driven Architecture

### Contact form

The contact form uses an **event-driven architecture** with Symfony EventDispatcher.

```
Controller → dispatches ContactRequestEvent
MailingSubscriber → sends the email (logs errors)
LoggerSubscriber → logs successful contact requests
```

This allows adding new behaviors (SMS, Slack notifications...) without modifying existing code.

---

## Testing

Tests are written with **PHPUnit** via `symfony/test-pack`.

```bash
php bin/phpunit
```

### Shared fixtures

A `FixturesTrait` is used across test classes to avoid code duplication:

```php
// tests/Traits/FixturesTrait.php
trait FixturesTrait
{
    private function createCategory(EntityManagerInterface $em, string $name = 'Apéritif'): Category { ... }
    private function createRecipe(EntityManagerInterface $em, string $title = 'Recette'): Recipe { ... }
}
```

---

## Database Fixtures

Fixtures are loaded with **DoctrineFixturesBundle**.

- `IngredientFixtures` — pre-fills the `ingredient` table
- `UnitFixtures` — pre-fills the `unit` table
- `RecipeFixtures` — generates fake recipes with random ingredients/quantities (depends on the two above)

```bash
php bin/console doctrine:fixtures:load
```

---

## Code Quality

| Tool | Purpose |
|------|---------|
| **PHPStan** | Static analysis |
| **PHP-CS-Fixer** | Code style |
| **GrumPHP** | Pre-commit hooks |

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix
```
