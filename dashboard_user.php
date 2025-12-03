<?php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");

$user_id = $_SESSION['user_id'];

// Récupérer tous les quiz actifs
$quizzes_res = $conn->query("SELECT id, title FROM quizzes WHERE is_active=1 ORDER BY created_at DESC");

// Récupérer les quiz déjà répondu par l'utilisateur
$answered = [];
$res = $conn->query("SELECT quiz_id FROM responses WHERE user_id=$user_id");
while($r = $res->fetch_assoc()){
    $answered[$r['quiz_id']] = true;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard Utilisateur</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Vos Quiz</h1>

<div class="quiz-grid">
<?php while($q = $quizzes_res->fetch_assoc()): ?>
<div class="quiz-card">
    <h3><?=htmlspecialchars($q['title'])?></h3>
    <?php if(isset($answered[$q['id']])): ?>
        <p><span class="correct">Déjà répondu</span></p>
    <?php else: ?>
        <a href="take_quiz.php?id=<?=$q['id']?>">Répondre au quiz</a>
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
