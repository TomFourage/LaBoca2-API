<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenService
{
    private int $ttl; // en secondes

    public function __construct(
        private EntityManagerInterface $em,
        int $ttl = 3600 // durée de vie du refresh token (1h ici)
    ) {
        $this->ttl = $ttl;
    }

    public function createRefreshToken(User $user): RefreshToken
    {
        // Supprime les anciens refresh tokens (optionnel mais propre)
        $this->removeRefreshTokens($user);

        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken(bin2hex(random_bytes(32)));
        $refreshToken->setExpiresAt(new \DateTimeImmutable("+{$this->ttl} seconds"));

        $this->em->persist($refreshToken);
        $this->em->flush();

        return $refreshToken;
    }

    public function getValidToken(string $token): ?RefreshToken
    {
        $repo = $this->em->getRepository(RefreshToken::class);
        $refreshToken = $repo->findOneBy(['token' => $token]);

        if (!$refreshToken || $refreshToken->isExpired()) {
            return null;
        }

        return $refreshToken;
    }

    public function removeRefreshTokens(User $user): void
    {
        $repo = $this->em->getRepository(RefreshToken::class);
        $tokens = $repo->findBy(['user' => $user]);

        foreach ($tokens as $token) {
            $this->em->remove($token);
        }

        $this->em->flush();
    }
}
