<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Recipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use FakerRestaurant\Provider\fr_FR\Restaurant;

class RecipeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->addProvider(new Restaurant($faker));

        $categories = ['Plat chaud', 'Dessert', 'Entrée', 'Goûter'];
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

            $manager->persist($recipe);
        }

        $manager->flush();
    }
}
