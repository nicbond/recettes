<?php

namespace App\DataFixtures;

use App\Entity\Ingredient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class IngredientFixtures extends Fixture
{
    public const array INGREDIENTS = [
        'Farine', 'Sucre', 'Oeufs', 'Beurre', 'Lait', 'Levure chimique', 'Sel',
        'Chocolat noir', 'Pépites de chocolat', 'Fruits secs (amandes, noix, etc.)',
        'Vanille', 'Cannelle', 'Fraise', 'Banane', 'Pomme', 'Carotte', 'Oignon',
        'Ail', 'Echalote', 'Herbes fraîches (ciboulette, persil, etc.)',
    ];

    public const array UNITS = [
        'g', 'kg', 'l', 'ml', 'cl', 'dl', 'cuillère à soupe', 'cuillère à café', 'pincée', 'verre',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::INGREDIENTS as $name) {
            $ingredient = (new Ingredient())->setName($name);
            $manager->persist($ingredient);

            // Create a unique reference for each ingredient (e.g., "ingredient_Farine")
            $this->addReference('ingredient_'.$name, $ingredient);
        }

        $manager->flush();
    }
}
