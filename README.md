# Abricot.co — API back-end (PHP)

API REST de l'application **Abricot.co**, SaaS de gestion de projets et de tâches.
Ce dépôt est le **starter back-end de l'option PHP** : une partie de l'API est déjà opérationnelle,
une pull request d'un collègue est en attente de revue, et certaines fonctionnalités restent à développer.

> Stack : PHP 8.2+ natif (sans framework), PDO / SQLite, JWT (firebase/php-jwt), Composer, OpenAPI 3.
>
> L'API est **séparée du front-end React** : les deux applications communiquent uniquement en HTTP/JSON.

---

## 🚀 Installation et démarrage

Prérequis : PHP 8.2 ou plus avec les extensions `pdo_sqlite`, `mbstring` et `openssl`, et [Composer](https://getcomposer.org/).
Vérifiez avec `php -v` et `php -m`.

```bash
# 1. Variables d'environnement : copiez le fichier d'exemple puis changez JWT_SECRET
cp .env.example .env

# 2. Dépendances
composer install

# 3. Base de données : création du schéma et jeu de données de test
composer setup

# 4. Serveur de développement (serveur intégré de PHP)
composer start
```

- API : http://localhost:8000
- Documentation interactive (Swagger) : http://localhost:8000/api-docs
- Santé de l'API : http://localhost:8000/health

| Script | Rôle |
|---|---|
| `composer start` | Démarre l'API sur http://localhost:8000 |
| `composer setup` | Recrée la base (`database/schema.sql`) et la peuple |
| `composer db:init` | Recrée la base vide (⚠️ supprime les données) |
| `composer seed` | Réinitialise les données de test (⚠️ vide la base) |
| `composer lint` | Vérifie la syntaxe de tous les fichiers PHP |

Pour explorer la base SQLite (`database/abricot.sqlite`), utilisez par exemple l'extension SQLite de votre IDE ou [DB Browser for SQLite](https://sqlitebrowser.org/).

### Comptes de test

Tous les comptes ont le mot de passe **`P@ssword123`**.

| Utilisateur | Email | Rôle notable |
|---|---|---|
| Alice Martin | alice@example.com | Propriétaire du projet « Application E-commerce » |
| Bob Dupont | bob@example.com | Administrateur du projet « Application E-commerce » |
| Caroline Leroy | caroline@example.com | Contributrice du projet « Application E-commerce » |
| Emma Rousseau | emma@example.com | Propriétaire du projet « Système de Gestion RH » |

Les autres comptes (david, francois, gabrielle, henri, isabelle, jacques @example.com) sont membres d'autres projets.

### CORS

Le front-end React et l'API PHP tournent sur des ports différents : le navigateur applique donc les règles CORS.
Seules les origines listées dans `CORS_ORIGINS` (fichier `.env`) peuvent appeler l'API.
Par défaut : `http://localhost:3000` (Next.js) et `http://localhost:5173` (Vite). La gestion CORS se trouve dans `public/index.php`.

---

## 🗂️ Structure du projet

```
public/index.php       Point d'entrée unique : CORS, en-têtes de sécurité, routage, gestion des erreurs
config/bootstrap.php   Chargement du .env et connexion à la base
config/routes.php      Table des routes et des middlewares
database/              Schéma SQL, création de la base et jeu de données de test
docs/openapi.yaml      Documentation OpenAPI (affichée par /api-docs)
src/
  Core/                Routeur, requête, réponse JSON, exceptions HTTP, base de données
  Middleware/          Authentification JWT, contrôle d'accès aux projets
  Controllers/         Logique des endpoints
  Repositories/        Requêtes SQL (PDO, requêtes préparées)
  Services/            Validation, JWT, droits d'accès
  Support/             Identifiants, dates, tri des tâches
```

---

## 🔐 Droits d'accès

Tous les utilisateurs ont le même compte ; leurs droits dépendent de leur rôle **dans chaque projet**.

| Action | Propriétaire | Administrateur | Contributeur | Non-membre |
|---|:-:|:-:|:-:|:-:|
| Créer un projet (on en devient propriétaire) | ✅ | ✅ | ✅ | ✅ |
| Consulter le projet et ses tâches | ✅ | ✅ | ✅ | ❌ 403 |
| Modifier le projet | ✅ | ✅ | ❌ 403 | ❌ 403 |
| Ajouter / retirer des contributeurs | ✅ | ✅ | ❌ 403 | ❌ 403 |
| Supprimer le projet | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| Créer / modifier / supprimer une tâche | ✅ | ✅ | ✅ | ❌ 403 |
| Commenter une tâche | ✅ | ✅ | ✅ | ❌ 403 |
| Modifier un commentaire | auteur uniquement | auteur uniquement | auteur uniquement | ❌ |
| Supprimer un commentaire | ✅ | ✅ | auteur uniquement | ❌ |

Le propriétaire est exposé avec le rôle `ADMIN` dans le champ `userRole` des projets.

---

## 📡 Endpoints

Toutes les routes, sauf `/auth/register`, `/auth/login` et `/health`, nécessitent le header
`Authorization: Bearer <token>`.

| | Méthode | Route | Description |
|---|---|---|---|
| ✅ | POST | `/auth/register` | Inscription |
| ✅ | POST | `/auth/login` | Connexion |
| ✅ | GET / PUT | `/auth/profile` | Lire / modifier son profil |
| ✅ | PUT | `/auth/password` | Modifier son mot de passe |
| ✅ | GET | `/users/search?query=` | Rechercher des utilisateurs (autocomplete) |
| ✅ | GET / POST | `/projects` | Lister ses projets / créer un projet |
| ✅ | GET / PUT / DELETE | `/projects/:id` | Détail / modification / suppression d'un projet |
| ✅ | POST | `/projects/:id/contributors` | Ajouter un contributeur |
| ✅ | DELETE | `/projects/:id/contributors/:userId` | Retirer un contributeur |
| ✅ | GET | `/dashboard/assigned-tasks` | Tâches assignées (les plus urgentes d'abord) |
| ✅ | GET | `/dashboard/projects-with-tasks` | Projets où l'on a des tâches assignées |
| ✅ | GET | `/dashboard/stats` | Statistiques personnelles |
| 🔍 | GET | `/projects/:id/tasks` | Tâches d'un projet — **PR `feature/tasks-read`** |
| 🔍 | GET | `/projects/:id/tasks/:taskId` | Détail d'une tâche — **PR `feature/tasks-read`** |
| 🛠️ | POST | `/projects/:id/tasks` | Créer une tâche — **à implémenter** |
| 🛠️ | PUT / DELETE | `/projects/:id/tasks/:taskId` | Modifier / supprimer une tâche — **à implémenter** |
| 🛠️ | GET / POST | `/projects/:id/tasks/:taskId/comments` | Lister / ajouter des commentaires — **à implémenter** |
| 🛠️ | GET / PUT / DELETE | `/projects/:id/tasks/:taskId/comments/:commentId` | Détail / modification / suppression d'un commentaire — **à implémenter** |

Les routes 🛠️ sont déjà déclarées dans `config/routes.php` mais répondent `501 NOT_IMPLEMENTED`.

### Format des réponses

```jsonc
// Succès
{ "success": true, "message": "Projet créé avec succès", "data": { "project": { ... } } }

// Erreur
{ "success": false, "message": "Accès refusé au projet", "error": "FORBIDDEN" }

// Erreur de validation (400)
{ "success": false, "message": "Données invalides", "error": "VALIDATION_ERROR",
  "data": { "errors": [{ "field": "title", "message": "Le titre de la tâche est requis" }] } }
```

| Code HTTP | Codes `error` utilisés |
|---|---|
| 400 | `VALIDATION_ERROR`, `INVALID_JSON`, `INVALID_QUERY`, `INVALID_ASSIGNEES` |
| 401 | `UNAUTHORIZED`, `INVALID_CREDENTIALS`, `INVALID_CURRENT_PASSWORD` |
| 403 | `FORBIDDEN` |
| 404 | `NOT_FOUND`, `PROJECT_NOT_FOUND`, `TASK_NOT_FOUND`, `COMMENT_NOT_FOUND`, `USER_NOT_FOUND`, `MEMBER_NOT_FOUND` |
| 409 | `EMAIL_ALREADY_EXISTS`, `USER_ALREADY_MEMBER` |
| 500 | `INTERNAL_SERVER_ERROR` |

---

## 🧭 Travail en cours sur ce dépôt

### 1. Pull request à reviewer : `feature/tasks-read`

Sami, développeur de l'équipe, a commencé la gestion des tâches sur la branche `feature/tasks-read`.
Ouvrez une pull request `feature/tasks-read` → `main` sur votre dépôt GitHub en reprenant sa description ci-dessous,
puis **reviewez-la avant toute fusion**.

> **feat(tasks) : lecture des tâches d'un projet**
>
> Ajoute les deux routes de lecture des tâches dont le front-end a besoin pour la page projet :
> - `GET /projects/:id/tasks` : liste des tâches du projet, les plus urgentes en premier ;
> - `GET /projects/:id/tasks/:taskId` : détail d'une tâche.
>
> Chaque tâche est renvoyée avec son créateur, ses assignations et ses commentaires.
> Les deux routes sont protégées par l'authentification et le contrôle d'accès au projet.
>
> Testé avec Postman sur le jeu de données du seed.

Votre revue doit laisser une trace exploitable (commentaires de PR ou note de revue) et distinguer
ce qui est bloquant, ce qui est à améliorer et les simples questions.

### 2. Fonctionnalités à développer

Les endpoints 🛠️ du tableau ci-dessus : création, modification et suppression des tâches (avec leurs
assignations), et gestion complète des commentaires. Les règles à respecter sont décrites dans la section
« Droits d'accès » et dans les commentaires `TODO` des contrôleurs et des repositories.

### 3. Documentation

Chaque route ajoutée ou modifiée doit être documentée dans `docs/openapi.yaml` (visualisation sur http://localhost:8000/api-docs).

---

## 🤝 Contribution

- Une branche par fonctionnalité ou correction (`feature/...`, `fix/...`), fusionnée dans `main` via une pull request relue.
- Messages de commit explicites, au format [Conventional Commits](https://www.conventionalcommits.org/fr/) (`feat:`, `fix:`, `docs:`...).
- Ne versionnez jamais le fichier `.env`, le dossier `vendor/` ni la base de données locale.
