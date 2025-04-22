<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface $validator
    ) {}

    public function createUser(string $email, string $plainPassword, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPlainPassword($plainPassword);

        $this->validate($user, ['user:create']);

        $hashed = $this->hasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);
        $user->eraseCredentials();

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updatePassword(User $user, string $oldPassword, string $newPassword): void
    {
        if (!$this->hasher->isPasswordValid($user, $oldPassword)) {
            throw new BadRequestHttpException("Ancien mot de passe incorrect.");
        }

        $user->setPlainPassword($newPassword);
        $this->validate($user);

        $hashed = $this->hasher->hashPassword($user, $newPassword);
        $user->setPassword($hashed);
        $user->eraseCredentials();

        $this->em->flush();
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            throw new BadRequestHttpException("Lien expiré ou invalide.");
        }

        $user->setPlainPassword($newPassword);
        $this->validate($user, ['user:create']);

        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $user->eraseCredentials();
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->em->flush();
    }

    private function validate(User $user, array $groups = ['Default']): void
    {
        $errors = $this->validator->validate($user, null, $groups);

        if (count($errors) > 0) {
            $message = [];
            foreach ($errors as $error) {
                $message[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new BadRequestHttpException(json_encode($message));
        }
    }

    public function generateResetToken(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTime('+30 minutes'));

        $this->em->flush();
    }
}
