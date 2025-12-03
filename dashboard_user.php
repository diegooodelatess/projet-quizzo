<?php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");

$user_id = $_SESSION['user_id'];

// Lister tous les quiz actifs
$quizzes = $conn->query("SELECT q.id, q.title FROM quizzes q WHERE q.active=1 ORDER BY q.created_at DESC");

// Optionnel : récupérer les quiz auxquels l'utilisateur a déjà répondu
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
<ul>
<?php while($q = $quizzes->fetch_assoc()): ?>
<li>
<?=htmlspecialchars($q['title'])?>
<?php if(isset($answered[$q['id']])): ?>
- <strong>Déjà répondu</strong>
<?php else: ?>
- <a href="take_quiz.php?id=<?=$q['id']?>">Répondre</a>
<?php endif; ?>
</li>
<?php endwhile; ?>
</ul>
<p><a href="profile.php">Modifier mon profil</a> | <a href="logout.php">Se déconnecter</a></p>
</div>
</body>
</html>
