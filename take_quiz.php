<?php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");
if(!isset($_GET['id'])) header("Location: dashboard_user.php");

$quiz_id = intval($_GET['id']);
$stmt=$conn->prepare("SELECT * FROM questions WHERE quiz_id=?");
$stmt->bind_param("i",$quiz_id);
$stmt->execute();
$questions = $stmt->get_result();
$stmt->close();

if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach($questions as $q){
        $answer = $_POST['answer_'.$q['id']] ?? '';
        $stmt = $conn->prepare("INSERT INTO responses (user_id,quiz_id,question_id,answer,score,created_at) VALUES (?,?,?,?,?,NOW())");
        $score = ($answer==$q['correct']?$q['points']:0);
        $stmt->bind_param("iiisi",$_SESSION['user_id'],$quiz_id,$q['id'],$answer,$score);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard_user.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Répondre au quiz</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Quiz</h1>
<form method="POST">
<?php foreach($questions as $q): ?>
<div>
<label><?=htmlspecialchars($q['question_text'])?> (<?=$q['points']?> points)</label><br>
<input type="text" name="answer_<?=$q['id']?>" required>
</div>
<?php endforeach; ?>
<button type="submit">Soumettre</button>
</form>
<p><a href="dashboard_user.php">Retour au dashboard</a></p>
</div>
</body>
</html>
