<?php
session_start();
require_once 'db.php';

if (!in_array($_SESSION['role'], ['school', 'company'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("ID question manquant");
}

$question_id = intval($_GET['id']);

// --- Charger la question ---
$stmt = $conn->prepare("SELECT question_text, type FROM questions WHERE id=?");
$stmt->bind_param("i", $question_id);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();
$stmt->close();

if (!$question) {
    die("Question introuvable.");
}

// --- Charger les options si c'est un QCM ---
$options = [];
if ($question['type'] === "qcm") {
    $opt = $conn->prepare("SELECT id, option_text, is_correct FROM options WHERE question_id=?");
    $opt->bind_param("i", $question_id);
    $opt->execute();
    $options = $opt->get_result()->fetch_all(MYSQLI_ASSOC);
    $opt->close();
}

// --- Mise à jour ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $question_text = $_POST["question_text"];
    $type = $_POST["type"];

    // --- Mise à jour question ---
    $stmt = $conn->prepare("UPDATE questions SET question_text=?, type=? WHERE id=?");
    $stmt->bind_param("ssi", $question_text, $type, $question_id);
    $stmt->execute();
    $stmt->close();

    // --- Mise à jour des options si QCM ---
    if ($type === "qcm") {

        foreach ($_POST["option_text"] as $opt_id => $text) {

            $correct = isset($_POST["is_correct"][$opt_id]) ? 1 : 0;

            $upd = $conn->prepare("UPDATE options SET option_text=?, is_correct=? WHERE id=?");
            $upd->bind_param("sii", $text, $correct, $opt_id);
            $upd->execute();
            $upd->close();
        }
    }

    header("Location: edit_quiz.php?id=".$_GET["quiz_id"]);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modifier la question</title>
    <link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>

<body>
<div class="container">
    <h1>Modifier la question</h1>

    <form method="post">
        <label>Question :</label>
        <input type="text" name="question_text" value="<?= htmlspecialchars($question['question_text']) ?>" required>

        <label>Type :</label>
        <select name="type">
            <option value="yn" <?= $question['type']=="yn"?"selected":"" ?>>Oui/Non</option>
            <option value="qcm" <?= $question['type']=="qcm"?"selected":"" ?>>QCM</option>
        </select>

        <?php if ($question['type'] === "qcm"): ?>
            <h3>Options du QCM :</h3>

            <?php foreach ($options as $opt): ?>
                <div class="option-block">
                    <label>Option :</label>
                    <input type="text" name="option_text[<?= $opt['id'] ?>]" value="<?= htmlspecialchars($opt['option_text']) ?>">
                    
                    <label>
                        <input type="checkbox" name="is_correct[<?= $opt['id'] ?>]" <?= $opt['is_correct'] ? "checked" : "" ?>>
                        Correcte
                    </label>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <br>
        <button type="submit" class="btn">Enregistrer</button>
    </form>

</div>
</body>
</html>
