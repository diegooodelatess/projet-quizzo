<?php
// dashboard_owner.php
session_start();
require_once 'db.php';

// Vérifier rôle propriétaire
if(!in_array($_SESSION['role'], ['school','company'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['user_id'];

// Récupérer uniquement les quiz du propriétaire connecté
$stmt = $conn->prepare("SELECT id, title, is_active, access_key FROM quizzes WHERE owner_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$quizzes_res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard Propriétaire</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo" alt="Logo">
    <h1>Vos Quiz</h1>
    <a class="btn" href="create_quiz.php">➕ Créer un quiz</a>

    <div class="quiz-grid" style="margin-top:18px;">
        <?php while($q = $quizzes_res->fetch_assoc()): ?>
            <div class="owner-quiz-card">
                <h3><?=htmlspecialchars($q['title'])?> 
                    <span class="<?= $q['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $q['is_active'] ? "(Actif)" : "(Inactif)" ?>
                    </span>
                </h3>
                <p class="hint">Clé d'accès : <strong><?=htmlspecialchars($q['access_key'])?></strong></p>
                <div class="edit-controls">
                    <a class="btn-edit" href="edit_quiz.php?id=<?= $q['id'] ?>">Modifier le quiz</a>
                    <a class="btn-ghost" href="view_responses.php?quiz_id=<?= $q['id'] ?>">Voir réponses</a>
                    <a class="btn-delete" href="delete_quiz.php?id=<?= $q['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce quiz ?')">Supprimer</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <p style="text-align:center; margin-top:20px;">
        <a class="btn" href="logout.php">Se déconnecter</a>
    </p>
</div>
</body>
</html>
