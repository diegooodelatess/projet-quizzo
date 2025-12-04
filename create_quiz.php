<?php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");

$error = '';

function generateKey($length = 10){
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $key = '';
    for ($i=0; $i<$length; $i++) $key .= $chars[random_int(0, strlen($chars)-1)];
    return $key;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title'] ?? '');
    $questions = $_POST['questions'] ?? [];

    if($title=='') $error="Le titre est obligatoire.";

    if(!$error){
        $access_key = generateKey(10);
        $stmt = $conn->prepare("INSERT INTO quizzes (title, owner_id, is_active, access_key, created_at) VALUES (?, ?, 1, ?, NOW())");
        $stmt->bind_param("sis", $title, $_SESSION['user_id'], $access_key);
        $stmt->execute();
        $quiz_id = $stmt->insert_id;
        $stmt->close();

        foreach($questions as $q){
            $text = trim($q['text'] ?? '');
            $type = $q['type'] ?? 'qcm';
            $points = intval($q['points'] ?? 1);
            if($text=='') continue;

            $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, type, points, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("issi",$quiz_id, $text, $type, $points);
            $stmt->execute();
            $question_id = $stmt->insert_id;
            $stmt->close();

            if($type==='yn'){
                $correct = $q['correct'] ?? 'Oui';
                foreach(['Oui','Non'] as $optText){
                    $val = ($correct === $optText) ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO options (question_id,text,is_correct) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $question_id, $optText, $val);
                    $stmt->execute();
                    $stmt->close();
                }
            } elseif($type==='qcm'){
                foreach($q['options'] ?? [] as $opt){
                    $opt_text = trim($opt['text'] ?? '');
                    if($opt_text=='') continue;
                    $is_correct = isset($opt['correct']) ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO options (question_id,text,is_correct) VALUES (?, ?, ?)");
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
<meta charset="utf-8">
<title>Créer un quiz</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>"/>
</head>
<body>
<div class="container">
<h1>Créer un quiz</h1>
<?php if($error) echo "<p class='error'>".htmlspecialchars($error)."</p>"; ?>
<form method="POST" id="quizForm">
<label>Titre :</label>
<input type="text" name="title" required>

<div id="questions"></div>

<button type="button" onclick="addQuestion()">Ajouter une question</button>
<button type="submit">Créer le quiz</button>
</form>
</div>

<script>
let qCount = 0;

function addQuestion(){
    qCount++;
    const container = document.getElementById('questions');
    const div = document.createElement('div');
    div.className = 'question-block';
    div.innerHTML = `
        <h4>Question ${qCount}</h4>
        <label>Texte :</label>
        <input type="text" name="questions[${qCount}][text]" required>
        <label>Points :</label>
        <input type="number" name="questions[${qCount}][points]" value="1" min="1">
        <label>Type :</label>
        <select name="questions[${qCount}][type]" onchange="updateType(this, ${qCount})">
            <option value="qcm">QCM</option>
            <option value="yn">Oui/Non</option>
            <option value="text">Réponse libre</option>
        </select>

        <div id="options-${qCount}" class="options-container">
            <label>Options (QCM, 2 à 4) :</label><br>
            <div class="option-pair">
                <input type="text" name="questions[${qCount}][options][0][text]" placeholder="Option 1">
                <label><input type="checkbox" name="questions[${qCount}][options][0][correct]"> Correct</label>
                <button type="button" onclick="removeOption(this)">-</button>
            </div>
            <div class="option-pair">
                <input type="text" name="questions[${qCount}][options][1][text]" placeholder="Option 2">
                <label><input type="checkbox" name="questions[${qCount}][options][1][correct]"> Correct</label>
                <button type="button" onclick="removeOption(this)">-</button>
            </div>
            <button type="button" onclick="addOption(${qCount})">Ajouter option</button>
        </div>

        <div id="yn-${qCount}" style="display:none">
            <label>Réponse correcte :</label>
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
    document.getElementById('options-'+qNum).style.display = (select.value==='qcm') ? 'block' : 'none';
    document.getElementById('yn-'+qNum).style.display = (select.value==='yn') ? 'block' : 'none';
}

// Ajouter une option QCM
function addOption(qNum){
    const optionsDiv = document.getElementById('options-'+qNum);
    const currentOptions = optionsDiv.querySelectorAll('.option-pair');
    if(currentOptions.length >= 4) return; // max 4 options

    const idx = currentOptions.length;
    const div = document.createElement('div');
    div.className = 'option-pair';
    div.innerHTML = `
        <input type="text" name="questions[${qNum}][options][${idx}][text]" placeholder="Option ${idx+1}">
        <label><input type="checkbox" name="questions[${qNum}][options][${idx}][correct]"> Correct</label>
        <button type="button" onclick="removeOption(this)">-</button>
    `;
    optionsDiv.insertBefore(div, optionsDiv.querySelector('button[onclick^="addOption"]'));
}

// Supprimer une option
function removeOption(btn){
    const div = btn.parentNode;
    div.remove();
}
</script>
</body>
</html>
