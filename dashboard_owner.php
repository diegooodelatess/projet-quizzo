<?php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");

$owner_id = $_SESSION['user_id'];
$quizzes = $conn->query("SELECT id,title,active FROM quizzes WHERE owner_id=$owner_id");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard École / Entreprise</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Vos Quiz</h1>
<a href="create_quiz.php">Créer un quiz</a>
<table border="1" width="100%">
<tr><th>Titre</th><th>Actif</th></tr>
<?php while($q=$quizzes->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($q['title'])?></td>
<td><?=$q['active']?"Oui":"Non"?></td>
</tr>
<?php endwhile; ?>
</table>
<p><a href="logout.php">Se déconnecter</a></p>
</div>
</body>
</html>
