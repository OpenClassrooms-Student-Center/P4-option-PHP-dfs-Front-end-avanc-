<?php

declare(strict_types=1);

/*
 * Peuple la base avec un jeu de données de test reproductible (identique à la variante JS).
 * Usage : composer seed   (⚠️ vide la base avant de la remplir)
 */

use App\Support\Clock;
use App\Support\Id;

[$config, $connect] = require dirname(__DIR__) . '/config/bootstrap.php';
$db = $connect();

const PASSWORD = 'P@ssword123';

$users = [
    ['alice@example.com', 'Alice Martin'],
    ['bob@example.com', 'Bob Dupont'],
    ['caroline@example.com', 'Caroline Leroy'],
    ['david@example.com', 'David Moreau'],
    ['emma@example.com', 'Emma Rousseau'],
    ['francois@example.com', 'François Dubois'],
    ['gabrielle@example.com', 'Gabrielle Simon'],
    ['henri@example.com', 'Henri Laurent'],
    ['isabelle@example.com', 'Isabelle Petit'],
    ['jacques@example.com', 'Jacques Durand'],
];

$projects = [
    [
        'name' => 'Application E-commerce',
        'description' => 'Plateforme de vente en ligne avec paiement sécurisé et gestion des stocks.',
        'owner' => 'alice@example.com',
        'members' => ['bob@example.com' => 'ADMIN', 'caroline@example.com' => 'CONTRIBUTOR', 'david@example.com' => 'CONTRIBUTOR'],
    ],
    [
        'name' => 'Système de Gestion RH',
        'description' => 'Gestion des ressources humaines : congés, évaluations, planning.',
        'owner' => 'emma@example.com',
        'members' => ['francois@example.com' => 'CONTRIBUTOR', 'gabrielle@example.com' => 'CONTRIBUTOR'],
    ],
    [
        'name' => 'Application Mobile Fitness',
        'description' => "Suivi d'entraînement, nutrition et objectifs personnalisés.",
        'owner' => 'henri@example.com',
        'members' => ['isabelle@example.com' => 'CONTRIBUTOR'],
    ],
    [
        'name' => 'Plateforme de Formation',
        'description' => 'Cours en ligne avec vidéos, quiz et suivi des progrès.',
        'owner' => 'jacques@example.com',
        'members' => ['alice@example.com' => 'CONTRIBUTOR'],
    ],
    [
        'name' => 'Dashboard Analytics',
        'description' => 'Visualisation de données avec graphiques interactifs et rapports automatisés.',
        'owner' => 'bob@example.com',
        'members' => ['emma@example.com' => 'ADMIN', 'henri@example.com' => 'CONTRIBUTOR'],
    ],
];

// [projet, titre, description, statut, priorité, échéance en jours par rapport à aujourd'hui, assignés]
$tasks = [
    ['Application E-commerce', 'Conception de la base de données', 'Schéma des produits, utilisateurs, commandes et paiements.', 'DONE', 'HIGH', -20, ['bob@example.com', 'caroline@example.com']],
    ['Application E-commerce', "Développement de l'API REST", 'Endpoints produits, panier et commandes.', 'IN_PROGRESS', 'URGENT', 3, ['david@example.com']],
    ['Application E-commerce', 'Interface utilisateur responsive', 'Composants React : liste des produits, panier et paiement.', 'TODO', 'MEDIUM', 12, ['alice@example.com', 'caroline@example.com']],
    ['Application E-commerce', 'Intégration du paiement', 'Intégrer un prestataire de paiement sécurisé.', 'TODO', 'HIGH', 20, ['bob@example.com']],
    ['Application E-commerce', 'Tests automatisés', "Tests unitaires et d'intégration de l'API et de l'interface.", 'TODO', 'LOW', 30, ['david@example.com', 'caroline@example.com']],
    ['Système de Gestion RH', 'Module de gestion des congés', "Demande et validation des congés avec circuit d'approbation.", 'IN_PROGRESS', 'HIGH', -2, ['emma@example.com', 'francois@example.com']],
    ['Système de Gestion RH', "Formulaires d'évaluation", "Formulaires d'évaluation et système de notation.", 'TODO', 'MEDIUM', 10, ['gabrielle@example.com']],
    ['Système de Gestion RH', 'Tableau de bord RH', 'Statistiques sur les effectifs, congés et performances.', 'TODO', 'LOW', 25, ['emma@example.com']],
    ['Application Mobile Fitness', "Maquettes de l'application", "Maquettes et prototype de l'application mobile.", 'DONE', 'HIGH', -15, ['henri@example.com']],
    ['Application Mobile Fitness', 'Écrans principaux', "Accueil, profil et suivi d'entraînement.", 'IN_PROGRESS', 'URGENT', 1, ['isabelle@example.com', 'henri@example.com']],
    ['Application Mobile Fitness', 'Intégration API nutrition', 'Connexion à une API de données nutritionnelles.', 'TODO', 'MEDIUM', 14, ['henri@example.com']],
    ['Plateforme de Formation', 'Gestion des cours', "Interface d'administration pour organiser les cours.", 'DONE', 'HIGH', -10, ['jacques@example.com']],
    ['Plateforme de Formation', 'Lecteur vidéo', 'Lecteur avec suivi de progression et prise de notes.', 'IN_PROGRESS', 'HIGH', 5, ['alice@example.com', 'jacques@example.com']],
    ['Plateforme de Formation', 'Quiz interactifs', 'Questions à choix multiples avec correction automatique.', 'TODO', 'MEDIUM', 18, ['alice@example.com']],
    ['Dashboard Analytics', 'Architecture des données', 'Collecte et stockage des données analytiques.', 'DONE', 'HIGH', -12, ['bob@example.com']],
    ['Dashboard Analytics', 'Développement des graphiques', 'Composants de visualisation interactifs.', 'IN_PROGRESS', 'HIGH', 7, ['emma@example.com', 'henri@example.com']],
    ['Dashboard Analytics', "Système d'alertes", 'Notifications sur seuils et anomalies.', 'TODO', 'MEDIUM', 21, ['bob@example.com']],
];

$commentTemplates = [
    "C'est en bonne voie, je fais un point demain.",
    "Attention aux droits d'accès sur cette partie.",
    "J'ai poussé une première version, vos retours sont les bienvenus.",
    "Bloqué en attente d'une précision fonctionnelle.",
    'Testé en local, tout fonctionne de mon côté.',
];

$insert = static function (PDO $db, string $table, array $row): string {
    $row = ['id' => Id::generate()] + $row;
    $columns = implode(', ', array_keys($row));
    $placeholders = implode(', ', array_fill(0, count($row), '?'));
    $db->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)")->execute(array_values($row));

    return $row['id'];
};

echo "🧹 Nettoyage de la base de données...\n";
foreach (['comments', 'task_assignees', 'tasks', 'project_members', 'projects', 'users'] as $table) {
    $db->exec("DELETE FROM $table");
}

// Une transaction rend l'insertion beaucoup plus rapide avec SQLite et garantit un seed complet ou rien
$db->beginTransaction();

echo "👥 Création des utilisateurs...\n";
$hash = password_hash(PASSWORD, PASSWORD_BCRYPT);
$userIds = [];
foreach ($users as [$email, $name]) {
    $now = Clock::now();
    $userIds[$email] = $insert($db, 'users', [
        'email' => $email, 'password' => $hash, 'name' => $name, 'createdAt' => $now, 'updatedAt' => $now,
    ]);
}

echo "📁 Création des projets et des membres...\n";
$projectIds = [];
$projectOwners = [];
foreach ($projects as $project) {
    $now = Clock::now();
    $projectId = $insert($db, 'projects', [
        'name' => $project['name'], 'description' => $project['description'],
        'ownerId' => $userIds[$project['owner']], 'createdAt' => $now, 'updatedAt' => $now,
    ]);
    foreach ($project['members'] as $email => $role) {
        $insert($db, 'project_members', [
            'role' => $role, 'joinedAt' => $now, 'userId' => $userIds[$email], 'projectId' => $projectId,
        ]);
    }
    $projectIds[$project['name']] = $projectId;
    $projectOwners[$project['name']] = $project['owner'];
}

echo "📋 Création des tâches, assignations et commentaires...\n";
$commentIndex = 0;
foreach ($tasks as [$projectName, $title, $description, $status, $priority, $dueInDays, $assignees]) {
    $owner = $projectOwners[$projectName];
    $now = Clock::now();
    $taskId = $insert($db, 'tasks', [
        'title' => $title, 'description' => $description, 'status' => $status, 'priority' => $priority,
        'dueDate' => Clock::daysFromNow($dueInDays), 'createdAt' => $now, 'updatedAt' => $now,
        'projectId' => $projectIds[$projectName], 'creatorId' => $userIds[$owner],
    ]);

    foreach ($assignees as $email) {
        $insert($db, 'task_assignees', ['assignedAt' => $now, 'taskId' => $taskId, 'userId' => $userIds[$email]]);
    }

    foreach (array_slice([$owner, ...$assignees], 0, 2) as $authorEmail) {
        $insert($db, 'comments', [
            'content' => $commentTemplates[$commentIndex++ % count($commentTemplates)],
            'createdAt' => Clock::now(), 'updatedAt' => Clock::now(),
            'taskId' => $taskId, 'authorId' => $userIds[$authorEmail],
        ]);
    }
}

$db->commit();

echo "🎉 Seed terminé :\n";
echo '   ' . count($users) . ' utilisateurs (mot de passe : ' . PASSWORD . ")\n";
echo '   ' . count($projects) . ' projets, ' . count($tasks) . " tâches, $commentIndex commentaires\n";
