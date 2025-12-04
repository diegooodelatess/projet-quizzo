<?php
session_start();
require_once 'db.php';

// Vérification du rôle utilisateur
if($_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer tous les quiz actifs
$quizzes = $conn->query("SELECT id, title, is_active FROM quizzes ORDER BY created_at DESC");

// Quiz déjà répondu
$answered = [];
$res = $conn->prepare("SELECT quiz_id FROM responses WHERE user_id=?");
$res->bind_param("i", $user_id);
$res->execute();
$rres = $res->get_result();
while($r = $rres->fetch_assoc()){
    $answered[$r['quiz_id']] = true;
}
$res->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard Utilisateur</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
    <img src="logo.png" class="logo" alt="Logo">
    <h1>Vos Quiz</h1>

    <div class="quiz-grid">
        <?php while($q = $quizzes->fetch_assoc()): ?>
        <div class="quiz-card">
            <h3><?= htmlspecialchars($q['title']) ?></h3>

            <?php if(isset($answered[$q['id']])): ?>
                <p><span class="correct">Déjà répondu</span></p>
            <?php else: ?>
                <?php if(!$q['is_active']): ?>
                    <p class="hint">Ce quiz n'est pas disponible pour le moment.</p>
                <?php else: ?>
                    <!-- Lien vers take_quiz.php directement -->
                    <a class="btn-ghost" href="take_quiz.php?quiz_id=<?= $q['id'] ?>">Accéder (clé requise)</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>

    <div style="text-align:center; margin-top:20px;">
        <a class="btn" href="profile.php">Modifier mon profil</a>
        <a class="btn" href="logout.php">Se déconnecter</a>
    </div>
</div>
</body>
</html>
