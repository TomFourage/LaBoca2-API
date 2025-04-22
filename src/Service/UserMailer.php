<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class UserMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger
    ) {}

    public function sendResetPasswordEmail(User $user): void
    {
        $resetUrl = $_ENV['FRONT_URL'] . '/reset-password?token=' . $user->getResetToken();

        try {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'])
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html($this->twig->render('emails/password_reset.html.twig', [
                    'resetUrl' => $resetUrl,
                    'user' => $user,
                ]));

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur envoi email de reset password : ' . $e->getMessage());
            throw new \RuntimeException("Erreur lors de l’envoi de l’email.");
        }
    }
}
