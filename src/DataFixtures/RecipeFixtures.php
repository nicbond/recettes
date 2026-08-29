<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Quantity;
use App\Entity\Recipe;
use App\Entity\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use FakerRestaurant\Provider\fr_FR\Restaurant;

class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->addProvider(new Restaurant($faker));

        $categories = ['Plat chaud', 'Dessert', 'Entrée', 'Goûter', 'Accompagnements', 'Brunch', 'Boissons et Cocktails'];
        foreach ($categories as $c) {
            $category = (new Category())
                ->setName($c)
                ->setCreatedAt($faker->dateTime())
                ->setUpdatedAt($faker->dateTime());

            $manager->persist($category);
            $this->addReference($c, $category);
        }

        for ($i = 1; $i <= 10; ++$i) {
            $categoryName = $faker->randomElement($categories);
            assert(is_string($categoryName));

            $recipe = (new Recipe())
                ->setTitle($faker->unique()->foodName())
                ->setCreatedAt($faker->dateTime())
                ->setUpdatedAt($faker->dateTime())
                ->setContent(implode("\n", (array) $faker->paragraphs(10)))
                ->setCategory($this->getReference($categoryName, Category::class))
                ->setDuration($faker->numberBetween(2, 60));

            // Randomly select ingredient names from the IngredientFixtures constants
            $randomIngredientNames = $faker->randomElements(
                IngredientFixtures::INGREDIENTS,
                $faker->numberBetween(2, 5)
            );

            foreach ($randomIngredientNames as $name) {
                // Retrieve a random unit entity from the reference
                $randomUnitLabel = $faker->randomElement(UnitFixtures::UNITS);
                assert(is_string($randomUnitLabel));
                $unit = $this->getReference('unit_'.$randomUnitLabel, Unit::class);

                // Retrieve the ingredient using the reference saved earlier
                $ingredient = $this->getReference('ingredient_'.$name, Ingredient::class);

                $recipe->addQuantity((new Quantity())
                    ->setQuantity((float) $faker->numberBetween(2, 250))
                    ->setUnit($unit)
                    ->setIngredient($ingredient)
                );
            }

            $manager->persist($recipe);
        }

        $manager->flush();
    }

    /**
     * This method tells Doctrine to load IngredientFixtures first !
     *
     * @return array<int, class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            IngredientFixtures::class,
            UnitFixtures::class,
        ];
    }
}
