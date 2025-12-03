<?php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title']);
    if($title != ''){
        $stmt=$conn->prepare("INSERT INTO quizzes (title, owner_id, active, finished, created_at) VALUES (?,?,1,0,NOW())");
        $stmt->bind_param("si",$title,$_SESSION['user_id']);
        $stmt->execute();
        $quiz_id = $stmt->insert_id;
        $stmt->close();

        foreach($_POST['questions'] as $q){
            $qt = trim($q['question_text']);
            $points = intval($q['points']);
            $correct = trim($q['correct']);
            if($qt!=''){
                $stmt=$conn->prepare("INSERT INTO questions (quiz_id, question_text, points, correct) VALUES (?,?,?,?)");
                $stmt->bind_param("isis",$quiz_id,$qt,$points,$correct);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: dashboard_owner.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer un quiz</title>
<link rel="stylesheet" href="style.css">
<script>
let qCount=0;
function addQuestion(){
    qCount++;
    const c=document.getElementById('questions');
    const d=document.createElement('div');
    d.innerHTML=`<h4>Question ${qCount}</h4>
    <label>Texte :</label><br><input type="text" name="questions[${qCount}][question_text]" required><br>
    <label>Points :</label><br><input type="number" name="questions[${qCount}][points]" value="1" min="1" required><br>
    <label>Réponse correcte :</label><br><input type="text" name="questions[${qCount}][correct]" required><br><br>`;
    c.appendChild(d);
}
</script>
</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Créer un quiz</h1>
<form method="POST">
<label>Titre :</label><input type="text" name="title" required><br><br>
<div id="questions"></div>
<button type="button" onclick="addQuestion()">Ajouter une question</button><br><br>
<button type="submit">Créer le quiz</button>
</form>
<p><a href="dashboard_owner.php">Retour au dashboard</a></p>
</div>
</body>
</html>
