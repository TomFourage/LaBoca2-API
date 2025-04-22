<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\SubCategory;
use App\Entity\Dishe;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Admin user
        $admin = new User();
        $admin->setEmail('admin@resto.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        $categoriesWithSubcategories = [
            'Empanadas' => ['Traditionnelles', 'Spéciales'],
            'Plats' => ['Plats chauds', 'Végétariens'],
            'Desserts' => ['Desserts maison'],
            'Vins' => ['Rouge', 'Blanc'],
            'Soft' => ['Sodas', 'Jus de fruits']
        ];

        $catOrder = 1;
        foreach ($categoriesWithSubcategories as $catName => $subNames) {
            $category = new Category();
            $category->setName($catName);
            $category->setDisplayOrder($catOrder++);
            $category->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($category);

            $subOrder = 1;
            foreach ($subNames as $subName) {
                $sub = new SubCategory();
                $sub->setName($subName);
                $sub->setDisplayOrder($subOrder++);
                $sub->setCreatedAt(new \DateTimeImmutable());
                $sub->setCategory($category);
                $manager->persist($sub);

                $dishOrder = 1;
                $dishCount = match ($catName) {
                    'Empanadas' => $subName === 'Traditionnelles' ? 5 : 5,
                    'Plats' => $subName === 'Plats chauds' ? 3 : 2,
                    'Desserts' => 3,
                    'Vins' => $subName === 'Rouge' ? 3 : 2,
                    'Soft' => $subName === 'Sodas' ? 3 : 2,
                    default => 2
                };

                for ($i = 0; $i < $dishCount; $i++) {
                    $dish = new Dishe();
                    $dish->setName($faker->words(2, true));
                    $dish->setPrice($faker->randomFloat(2, 3, 15));
                    $dish->setDescription($faker->sentence(10));
                    $dish->setCreatedAt(new \DateTimeImmutable());
                    $dish->setCategory($category);
                    $dish->setSubCategory($sub);
                    $dish->setDisplayOrder($dishOrder++);
                    $manager->persist($dish);
                }
            }
        }

        $manager->flush();
    }
}
