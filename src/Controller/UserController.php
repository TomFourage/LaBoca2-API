<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/users', name: 'api_user_')]
final class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        return $this->json($userRepository->findAll(), 200, [], ['groups' => 'user:read']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, 200, [], ['groups' => 'user:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, UserManager $userManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $user = $userManager->createUser(
                $data['email'] ?? '',
                $data['plainPassword'] ?? '',
                ['ROLE_ADMIN'] // ou autre selon ton besoin
            );
        } catch (\Throwable $e) {
            return $this->json(['errors' => json_decode($e->getMessage())], 400);
        }

        return $this->json($user, 201, [], ['groups' => 'user:read']);
    }


    #[Route('/me/password', name: 'me_update_password', methods: ['PATCH'])]
    public function updateMyPassword(Request $request, UserManager $userManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $userManager->updatePassword($user, $data['oldPassword'] ?? '', $data['newPassword'] ?? '');
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    #[Route('/me/email', name: 'me_update_email', methods: ['PATCH'])]
    public function updateMyEmail(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['password']) || empty($data['newEmail'])) {
            return $this->json(['error' => 'Tous les champs sont requis.'], 400);
        }

        if (!$hasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Mot de passe incorrect.'], 403);
        }

        $user->setEmail($data['newEmail']);

        $em->flush();

        return $this->json(['message' => 'Adresse email mise à jour avec succès.']);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        UserManager $userManager,
        UserMailer $userMailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $userManager->generateResetToken($user);

            try {
                $userMailer->sendResetPasswordEmail($user);
            } catch (\Throwable $e) {
                return $this->json(['error' => $e->getMessage()], 500);
            }
        }

        return $this->json([
            'message' => 'Si un compte existe, un e-mail a été envoyé.'
        ]);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, UserManager $userManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $userManager->resetPassword($data['token'] ?? '', $data['password'] ?? '');
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
