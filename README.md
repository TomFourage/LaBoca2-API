# 🧑‍🍳 Saint Antoine API

API REST Symfony pour la gestion du restaurant *Saint Antoine* : catégories, sous-catégories, plats, utilisateurs (admin uniquement).

---

## ⚙️ Installation & Lancement

```bash
# Cloner le projet
git clone https://github.com/votre-utilisateur/saintAntoine-API.git
cd saintAntoine-API

# Installer les dépendances
composer install

# Copier la config d'env
cp .env .env.local

# Créer la base de données
php bin/console doctrine:database:create

# Lancer les migrations
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger des données de test
php bin/console doctrine:fixtures:load

# Démarrer le serveur
php -S localhost:8000 -t public
```

## 📬 Endpoints API

| Méthode | Route                          | Description                              | Auth |
|---------|--------------------------------|------------------------------------------|------|
| POST    | `/api/login`                  | Authentification (JWT)                   | ❌   |
| GET     | `/api/categories`             | Lister les catégories                    | ✅   |
| GET     | `/api/categories/{id}`        | Détail d'une catégorie                   | ✅   |
| GET     | `/api/users`                  | Lister les utilisateurs                  | ✅   |
| GET     | `/api/users/me`               | Infos de l'utilisateur connecté          | ✅   |
| PATCH   | `/api/users/me/password`      | Modifier son mot de passe                | ✅   |
| POST    | `/api/users`                  | Créer un nouvel utilisateur (admin)      | ✅   |

> ⚠️ Toutes les routes `/api` sauf `/api/login` nécessitent un token JWT valide.

