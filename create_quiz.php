<?php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");

$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title']);
    $questions = $_POST['questions'] ?? [];

    if($title=='') $error="Le titre est obligatoire.";

    if(!$error){
        // Création du quiz
        $stmt=$conn->prepare("INSERT INTO quizzes (title, owner_id, is_active, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("si", $title, $_SESSION['user_id']);
        $stmt->execute();
        $quiz_id = $stmt->insert_id;
        $stmt->close();

        foreach($questions as $q){
            $text = trim($q['text']);
            $type = $q['type'];
            if($text=='') continue;

            // Insérer question
            $stmt=$conn->prepare("INSERT INTO questions (quiz_id, question_text, type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $quiz_id, $text, $type);
            $stmt->execute();
            $question_id = $stmt->insert_id;
            $stmt->close();

            // Options
            if($type=='yn'){
                $correct = $q['correct']; // "Oui" ou "Non"
                $stmt=$conn->prepare("INSERT INTO options (question_id,text,is_correct) VALUES (?, ?, ?)");
                $val = ($correct==='Oui')?1:0;
                $stmt->bind_param("isi", $question_id, $qtext, $val);

                $qtext = "Oui";
                $stmt->execute();
                $qtext = "Non";
                $val = ($correct==='Non')?1:0;
                $stmt->execute();
                $stmt->close();
            } elseif($type=='qcm'){
                foreach($q['options'] as $opt){
                    $opt_text = trim($opt['text']);
                    if($opt_text=='') continue;
                    $is_correct = isset($opt['correct']) ? 1 : 0;
                    $stmt=$conn->prepare("INSERT INTO options (question_id,text,is_correct) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $question_id, $opt_text, $is_correct);
                    $stmt->execute();
                    $stmt->close();
                }
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
<link rel="stylesheet" href="style.css?v=<?=time()?>">

<script>
let qCount=0;

function addQuestion(){
    qCount++;
    const container = document.getElementById('questions');
    const div = document.createElement('div');
    div.classList.add('question-block');
    div.innerHTML = `
    <h4>Question ${qCount}</h4>
    <label>Texte :</label><br>
    <input type="text" name="questions[${qCount}][text]" required><br>
    <label>Type :</label><br>
    <select name="questions[${qCount}][type]" onchange="updateType(this, ${qCount})">
        <option value="yn">Oui/Non</option>
        <option value="qcm">QCM</option>
    </select>
    <div id="options-${qCount}" class="options-container">
        <label>Réponse correcte :</label><br>
        <select name="questions[${qCount}][correct]">
            <option value="Oui">Oui</option>
            <option value="Non">Non</option>
        </select>
    </div>
    <hr>
    `;
    container.appendChild(div);
}

function updateType(select, qNum){
    const optDiv = document.getElementById('options-'+qNum);
    if(select.value==='yn'){
        optDiv.innerHTML = `
        <label>Réponse correcte :</label><br>
        <select name="questions[${qNum}][correct]">
            <option value="Oui">Oui</option>
            <option value="Non">Non</option>
        </select>
        `;
    } else {
        optDiv.innerHTML = `
        <label>Options (max 4) :</label><br>
        <input type="text" name="questions[${qNum}][options][0][text]" placeholder="Option 1">
        <input type="checkbox" name="questions[${qNum}][options][0][correct]"> Correct<br>
        <input type="text" name="questions[${qNum}][options][1][text]" placeholder="Option 2">
        <input type="checkbox" name="questions[${qNum}][options][1][correct]"> Correct<br>
        <input type="text" name="questions[${qNum}][options][2][text]" placeholder="Option 3">
        <input type="checkbox" name="questions[${qNum}][options][2][correct]"> Correct<br>
        <input type="text" name="questions[${qNum}][options][3][text]" placeholder="Option 4">
        <input type="checkbox" name="questions[${qNum}][options][3][correct]"> Correct<br>
        `;
    }
}
</script>
</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Créer un quiz</h1>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
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
