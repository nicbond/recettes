<?php

declare(strict_types=1);

namespace App\Enum;

enum Permission: string
{
    // Recipes
    case RECIPE_MENU = 'recipe.menu';

    case RECIPE_CREATE = 'recipe.create';

    case RECIPE_EDIT = 'recipe.edit';

    case RECIPE_DELETE = 'recipe.delete';

    // Categories
    case CATEGORY_CREATE = 'category.create';

    case CATEGORY_EDIT = 'category.edit';

    case CATEGORY_DELETE = 'category.delete';

    // Tags
    case TAG_CREATE = 'tag.create';

    case TAG_EDIT = 'tag.edit';

    case TAG_DELETE = 'tag.delete';

    // Ingredients
    case INGREDIENT_CREATE = 'ingredient.create';

    // Users (comming soon)
    case USER_CREATE = 'user.create';

    case USER_EDIT = 'user.edit';

    case USER_DELETE = 'user.delete';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::RECIPE_MENU => 'Menu Recettes',
            self::RECIPE_CREATE => 'Créer une recette',
            self::RECIPE_EDIT => 'Modifier une recette',
            self::RECIPE_DELETE => 'Supprimer une recette',
            self::CATEGORY_CREATE => 'Créer une catégorie',
            self::CATEGORY_EDIT => 'Modifier une catégorie',
            self::CATEGORY_DELETE => 'Supprimer une catégorie',
            self::TAG_CREATE => 'Créer un tag',
            self::TAG_EDIT => 'Modifier un tag',
            self::TAG_DELETE => 'Supprimer un tag',
            self::INGREDIENT_CREATE => 'Créer un ingrédient',
            self::USER_CREATE => 'Créer un utilisateur',
            self::USER_EDIT => 'Modifier un utilisateur',
            self::USER_DELETE => 'Supprimer un utilisateur',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::RECIPE_MENU,self::RECIPE_CREATE, self::RECIPE_EDIT, self::RECIPE_DELETE => 'Recettes',
            self::CATEGORY_CREATE, self::CATEGORY_EDIT, self::CATEGORY_DELETE => 'Catégories',
            self::TAG_CREATE, self::TAG_EDIT, self::TAG_DELETE => 'Tags',
            self::INGREDIENT_CREATE => 'Ingrédients',
            self::USER_CREATE, self::USER_EDIT, self::USER_DELETE => 'Utilisateurs',
        };
    }
}
