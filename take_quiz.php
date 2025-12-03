<?php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");
if(!isset($_GET['id'])) header("Location: dashboard_user.php");

$quiz_id = intval($_GET['id']);

// Récupérer les questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id=?");
$stmt->bind_param("i",$quiz_id);
$stmt->execute();
$questions = $stmt->get_result();
$stmt->close();

// Vérifier si l'utilisateur a déjà répondu
$res = $conn->prepare("SELECT id FROM responses WHERE user_id=? AND quiz_id=?");
$res->bind_param("ii", $_SESSION['user_id'], $quiz_id);
$res->execute();
$res->store_result();
if($res->num_rows > 0){
    $res->close();
    header("Location: dashboard_user.php");
    exit();
}
$res->close();

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    foreach($questions as $q){
        $q_id = $q['id'];
        $answer = '';
        $score = 0;

        if($q['type'] == 'yn'){
            $answer = $_POST['answer_'.$q_id] ?? '';
            $opt = $conn->prepare("SELECT text FROM options WHERE question_id=? AND is_correct=1 LIMIT 1");
            $opt->bind_param("i", $q_id);
            $opt->execute();
            $resOpt = $opt->get_result()->fetch_assoc();
            $opt->close();
            if($answer == $resOpt['text']) $score = 1;

        } elseif($q['type'] == 'qcm'){
            $selected = $_POST['answer_'.$q_id] ?? [];
            if(!is_array($selected)) $selected = [$selected];

            $opt = $conn->prepare("SELECT text FROM options WHERE question_id=? AND is_correct=1");
            $opt->bind_param("i",$q_id);
            $opt->execute();
            $resOpt = $opt->get_result();
            $correct = [];
            while($o = $resOpt->fetch_assoc()) $correct[] = $o['text'];
            $opt->close();

            sort($selected); sort($correct);
            if($selected == $correct) $score = 1;
            $answer = implode(",", $selected);
        }

        $stmt = $conn->prepare("INSERT INTO responses (user_id,quiz_id,question_id,answer,score,created_at) VALUES (?,?,?,?,?,NOW())");
        $stmt->bind_param("iiisi", $_SESSION['user_id'], $quiz_id, $q_id, $answer, $score);
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
<link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Quiz</h1>
<form method="POST">
<?php foreach($questions as $q): ?>
<div class="question-block">
    <label class="question-text"><?=htmlspecialchars($q['question_text'])?></label><br>
    <?php
    if($q['type']=='yn'):
        echo '<div class="option yn-option"><span>Oui</span> <input type="radio" name="answer_'.$q['id'].'" value="Oui" required></div>';
        echo '<div class="option yn-option"><span>Non</span> <input type="radio" name="answer_'.$q['id'].'" value="Non"></div>';
    elseif($q['type']=='qcm'):
        $opt_stmt = $conn->prepare("SELECT * FROM options WHERE question_id=?");
        $opt_stmt->bind_param("i",$q['id']);
        $opt_stmt->execute();
        $opts = $opt_stmt->get_result();
        while($o = $opts->fetch_assoc()){
            echo '<div class="option"><span>'.htmlspecialchars($o['text']).'</span> <input type="checkbox" name="answer_'.$q['id'].'[]" value="'.htmlspecialchars($o['text']).'"></div>';
        }
        $opt_stmt->close();
    endif;
    ?>
</div><br>
<?php endforeach; ?>
<button type="submit" class="btn">Soumettre</button>
</form>
<p><a href="dashboard_user.php">Retour au dashboard</a></p>
</div>
</body>
</html>
