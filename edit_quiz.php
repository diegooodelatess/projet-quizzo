<?php
session_start();
require_once 'db.php';

if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");
if(!isset($_GET['id'])) header("Location: dashboard_owner.php");

$quiz_id = intval($_GET['id']);

// Récupération du quiz
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id=? AND owner_id=?");
$stmt->bind_param("ii", $quiz_id, $_SESSION['user_id']);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$quiz) header("Location: dashboard_owner.php");

// Mise à jour titre
if(isset($_POST['update_title'])){
    $new_title = $_POST['title'];
    
    $up = $conn->prepare("UPDATE quizzes SET title=? WHERE id=?");
    $up->bind_param("si", $new_title, $quiz_id);
    $up->execute();
    $up->close();

    header("Location: edit_quiz.php?id=$quiz_id");
    exit();
}

// Récupération des questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id=?");
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$questions = $q_stmt->get_result();
$q_stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="style.css?v=<?=time()?>">

<title>Modifier Quiz</title>
</head>
<body>
<div class="container">
<h1>Modifier le quiz</h1>

<form method="POST">
<label>Titre du quiz :</label>
<input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required>
<button type="submit" name="update_title">Mettre à jour</button>
</form>

<h2>Questions</h2>

<?php while($q = $questions->fetch_assoc()): ?>
<div class="quiz-card">
    <p><strong><?= htmlspecialchars($q['question_text']) ?></strong></p>
    <a class="btn-edit" href="edit_question.php?id=<?= $q['id'] ?>">Modifier la question</a>
</div>
<?php endwhile; ?>

<p><a href="dashboard_owner.php">Retour au dashboard</a></p>
</div>
</body>
</html>
