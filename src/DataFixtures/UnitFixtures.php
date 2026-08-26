<?php

namespace App\DataFixtures;

use App\Entity\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UnitFixtures extends Fixture
{
    public const array UNITS = [
        'g', 'kg', 'L', 'mL', 'cL', 'dL', 'Cuillère à soupe', 'Cuillère à café', 'Pincée', 'Verre', 'Pièce',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::UNITS as $name) {
            $unit = (new Unit())->setLabel($name);
            $manager->persist($unit);

            // Create a unique reference for each unit
            $this->addReference('unit_'.$name, $unit);
        }

        $manager->flush();
    }
}
