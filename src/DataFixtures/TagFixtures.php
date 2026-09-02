<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends Fixture
{
    public const array TAGS = [
        // By preparation time & Difficulty
        'rapide', 'facile', 'sans-cuisson', 'express', 'inratable',

        // By Budget & Savings
        'pas-cher', 'anti-gaspillage', 'petit-budget', 'avec-les-restes',

        // By Geographic & Cultural Origin
        'italien', 'asiatique', 'mexicain', 'traditionnel', 'exotique',

        // By Diet & Health
        'vegetarien', 'vegan', 'sans-gluten', 'healthy', 'sans-lactose',

        // By Occasion & Consumption Context
        'familial', 'plat-d-hiver', 'barbecue', 'aperitif-dinatoire', 'repas-de-fete',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::TAGS as $name) {
            $tag = (new Tag())->setName($name);
            $manager->persist($tag);

            $this->addReference('tag_'.$name, $tag);
        }

        $manager->flush();
    }
}
