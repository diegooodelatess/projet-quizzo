<?php
// edit_question.php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");
if(!isset($_GET['id'])) header("Location: dashboard_owner.php");

$question_id = intval($_GET['id']);

// Charger question and quiz ownership check
$stmt = $conn->prepare("SELECT q.*, z.owner_id FROM questions q JOIN quizzes z ON q.quiz_id=z.id WHERE q.id=?");
$stmt->bind_param("i",$question_id);
$stmt->execute();
$res = $stmt->get_result();
$question = $res->fetch_assoc();
$stmt->close();

if(!$question || $question['owner_id'] != $_SESSION['user_id']) {
    header("Location: dashboard_owner.php");
    exit();
}
$quiz_id = $question['quiz_id'];

// Charger options if qcm
$options = [];
if($question['type'] === 'qcm'){
    $opt = $conn->prepare("SELECT id, text, is_correct FROM options WHERE question_id=?");
    $opt->bind_param("i",$question_id);
    $opt->execute();
    $options = $opt->get_result()->fetch_all(MYSQLI_ASSOC);
    $opt->close();
}

// Traitement POST
if($_SERVER['REQUEST_METHOD']==='POST'){
    $text = trim($_POST['question_text'] ?? '');
    $type = $_POST['type'] ?? $question['type'];
    $points = intval($_POST['points'] ?? $question['points'] ?? 1);

    $up = $conn->prepare("UPDATE questions SET question_text=?, type=?, points=? WHERE id=?");
    $up->bind_param("ssii",$text,$type,$points,$question_id);
    $up->execute();
    $up->close();

    // Options handling
    if($type === 'qcm'){
        // update existing options text & is_correct
        foreach($_POST['option_text'] as $opt_id => $opt_text){
            $opt_text = trim($opt_text);
            $is_corr = isset($_POST['is_correct'][$opt_id]) ? 1 : 0;
            $upd = $conn->prepare("UPDATE options SET text=?, is_correct=? WHERE id=?");
            $upd->bind_param("sii",$opt_text,$is_corr,$opt_id);
            $upd->execute();
            $upd->close();
        }
        // add new option if provided
        if(!empty(trim($_POST['new_option'] ?? ''))){
            $new = trim($_POST['new_option']);
            $is_corr_new = isset($_POST['new_is_correct']) ? 1 : 0;
            $ins = $conn->prepare("INSERT INTO options (question_id,text,is_correct) VALUES (?,?,?)");
            $ins->bind_param("isi",$question_id,$new,$is_corr_new);
            $ins->execute();
            $ins->close();
        }
    } else {
        // si non qcm, supprimer options (or keep for history)
        $conn->query("DELETE FROM options WHERE question_id=$question_id");
    }

    header("Location: edit_quiz.php?id=".$quiz_id);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Modifier Question</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
<h1>Modifier la question</h1>
<form method="POST">
<label>Texte :</label>
<input type="text" name="question_text" value="<?=htmlspecialchars($question['question_text'])?>" required>
<label>Type :</label>
<select name="type" id="typeSelect">
    <option value="yn" <?= $question['type']=='yn'?'selected':'' ?>>Oui/Non</option>
    <option value="qcm" <?= $question['type']=='qcm'?'selected':'' ?>>QCM</option>
    <option value="text" <?= $question['type']=='text'?'selected':'' ?>>Réponse libre</option>
</select>
<label>Points :</label>
<input type="number" name="points" value="<?=intval($question['points'] ?? 1)?>" min="1">

<div id="qcmBlock" style="display: <?= $question['type']=='qcm'?'block':'none' ?>">
    <h3>Options existantes</h3>
    <?php if(!empty($options)): foreach($options as $o): ?>
    <div>
        <input type="text" name="option_text[<?= $o['id'] ?>]" value="<?=htmlspecialchars($o['text'])?>">
        <label><input type="checkbox" name="is_correct[<?= $o['id'] ?>]" <?= $o['is_correct']?'checked':'' ?>> Correct</label>
    </div>
    <?php endforeach; else: ?>
    <p class="hint">Aucune option pour l'instant.</p>
    <?php endif; ?>

    <h4>Ajouter une option</h4>
    <input type="text" name="new_option" placeholder="Nouvelle option">
    <label><input type="checkbox" name="new_is_correct"> Correct</label>
</div>

<button type="submit" class="btn">Enregistrer</button>
</form>
<p><a class="btn-ghost" href="edit_quiz.php?id=<?= $quiz_id ?>">Retour</a></p>
</div>
<script>
document.getElementById('typeSelect').addEventListener('change', function(){
    document.getElementById('qcmBlock').style.display = this.value === 'qcm' ? 'block' : 'none';
});
</script>
</body>
</html>
