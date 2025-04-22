<?php

namespace App\DataFixtures;

use App\Entity\Icon;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class IconFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $icons = [
            'fa-solid fa-hotdog' => 'Hotdog',
            'fa-solid fa-mug-hot' => 'Mug Hot',
            'fa-solid fa-beer-mug-empty' => 'Beer Mug',
            'fa-solid fa-whiskey-glass' => 'Whiskey Glass',
            'fa-solid fa-martini-glass' => 'Martini Glass',
            'fa-solid fa-glass-water' => 'Glass Water',
            'fa-solid fa-wine-glass' => 'Wine Glass',
        ];

        foreach ($icons as $class => $name) {
            $icon = new Icon();
            $icon->setClass($class);
            $icon->setName($name);
            $manager->persist($icon);
        }

        $manager->flush();
    }
}
