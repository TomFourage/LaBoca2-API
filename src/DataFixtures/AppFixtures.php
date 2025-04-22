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

        // 👤 Admin user
        $admin = new User();
        $admin->setEmail('admin@resto.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // 🔖 Catégories fixes avec leurs sous-catégories définies
        $categoriesWithSubcategories = [
            'Bières' => ['IPA', 'Lager', 'Ambrée'],
            'Vins' => ['Rouge', 'Blanc', 'Rosé'],
            'Cocktails' => ['Classiques', 'Créations maison'],
            'Soft' => ['Sodas', 'Jus de fruits'],
            'Boissons chaudes' => ['Café', 'Thé', 'Chocolat'],
            'Spiritueux' => ['Whisky', 'Rhum', 'Vodka'],
            'À manger' => ['Snacks', 'Plats chauds', 'Végétarien'],
        ];

        foreach ($categoriesWithSubcategories as $catName => $subNames) {
            $category = new Category();
            $category->setName($catName);
            $category->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($category);

            foreach ($subNames as $subName) {
                $sub = new SubCategory();
                $sub->setName($subName);
                $sub->setCreatedAt(new \DateTimeImmutable());
                $sub->setCategory($category);
                $manager->persist($sub);

                // 🍽️ Création de plats pour chaque sous-catégorie
                for ($j = 0; $j < 3; $j++) {
                    $dish = new Dishe();
                    $dish->setCreatedAt(new \DateTimeImmutable());
                    $dish->setPrice($faker->randomFloat(2, 2, 12));
                    $dish->setDescription($faker->sentence(10));
                    $dish->setSubCategory($sub);
                    $dish->setCategory($category);

                    $dish->setName(match ($catName) {
                        'Bières' => $faker->randomElement(['IPA artisanale', 'Blonde légère', 'Ambrée de saison']),
                        'Vins' => $faker->randomElement(['Rouge fruité', 'Chardonnay', 'Rosé sec']),
                        'Cocktails' => $faker->randomElement(['Mojito', 'Spritz', 'Margarita']),
                        'Soft' => $faker->randomElement(['Coca-Cola', 'Jus d’orange frais', 'Limonade artisanale']),
                        'Boissons chaudes' => $faker->randomElement(['Café allongé', 'Thé vert menthe', 'Chocolat chaud']),
                        'Spiritueux' => $faker->randomElement(['Whisky tourbé', 'Rhum ambré', 'Vodka premium']),
                        'À manger' => $faker->randomElement(['Croque Monsieur', 'Burger maison', 'Planche mixte']),
                        default => ucfirst($faker->words(2, true)),
                    });

                    $manager->persist($dish);
                }
            }
        }

        $manager->flush();
    }
}
