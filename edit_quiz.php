<?php
// edit_quiz.php
session_start();
require_once 'db.php';

if(!in_array($_SESSION['role'], ['school','company'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['id'] ?? 0);

// Vérifier que le quiz appartient au propriétaire
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id=? AND owner_id=?");
$stmt->bind_param("ii", $quiz_id, $owner_id);
$stmt->execute();
$res = $stmt->get_result();
$quiz = $res->fetch_assoc();
$stmt->close();

if(!$quiz) die("Accès refusé : ce quiz ne vous appartient pas.");

// Traitement POST pour modifier le quiz
if(isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $stmt = $conn->prepare("UPDATE quizzes SET title=? WHERE id=? AND owner_id=?");
    $stmt->bind_param("sii", $title, $quiz_id, $owner_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard_owner.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Quiz</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
<h2>Modifier Quiz : <?= htmlspecialchars($quiz['title']) ?></h2>
<form method="POST">
    <label>Titre :</label>
    <input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required>
    <button type="submit">Enregistrer</button>
</form>
<p><a href="dashboard_owner.php">⬅ Retour au dashboard</a></p>
</div>
</body>
</html>
