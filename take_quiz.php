<?php
session_start();
require_once 'db.php';

// Vérification de connexion
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer le quiz_id depuis GET
if(!isset($_GET['quiz_id'])) {
    die("Quiz non spécifié.");
}
$quiz_id = intval($_GET['quiz_id']);

// Vérifier si l'utilisateur a déjà répondu à ce quiz
$check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM responses WHERE user_id=? AND quiz_id=?");
$check_stmt->bind_param("ii", $user_id, $quiz_id);
$check_stmt->execute();
$check_res = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if($check_res['count'] > 0) {
    // Déjà répondu -> redirection
    header("Location: dashboard_user.php");
    exit();
}

// Récupérer le quiz et sa clé d'accès
$stmt = $conn->prepare("SELECT title, access_key, is_active FROM quizzes WHERE id=? AND is_active=1");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz_res = $stmt->get_result();
if($quiz_res->num_rows === 0) {
    die("Quiz introuvable ou inactif.");
}
$quiz = $quiz_res->fetch_assoc();

// Vérification clé d'accès
$error = "";
if(isset($_POST['access_key'])) {
    if($_POST['access_key'] !== $quiz['access_key']) {
        $error = "Clé d'accès incorrecte.";
    } else {
        $_SESSION['quiz_access'][$quiz_id] = true; // clé validée
    }
}

// Si clé non validée, afficher formulaire
if(empty($_SESSION['quiz_access'][$quiz_id])): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Entrer la clé du quiz</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>"/>
</head>
<body>
<div class="container">
    <h2>Quiz : <?= htmlspecialchars($quiz['title']) ?></h2>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <label>Clé d'accès :</label>
        <input type="text" name="access_key" required>
        <button type="submit">Valider</button>
    </form>
</div>
</body>
</html>
<?php
exit();
endif;

// Si les réponses sont soumises
if(isset($_POST['submit_answers'])) {
    foreach($_POST as $key => $val) {
        if(strpos($key, "question_") === 0) {
            $question_id = intval(str_replace("question_", "", $key));
            $answer = $val;

            // Récupérer la question pour déterminer le type et les bonnes réponses
            $qstmt = $conn->prepare("SELECT type, correct_answer FROM questions WHERE id=?");
            $qstmt->bind_param("i", $question_id);
            $qstmt->execute();
            $q_type_res = $qstmt->get_result()->fetch_assoc();
            $qstmt->close();

            if($q_type_res['type'] === 'qcm') {
                // Pour QCM, $val peut être un tableau si plusieurs réponses
                if(!is_array($answer)) $answer = [$answer];

                $correct_answers = $q_type_res['correct_answer'] ? explode(',', $q_type_res['correct_answer']) : [];

                foreach($answer as $a) {
                    $a = intval($a);
                    $score = in_array($a, $correct_answers) ? 1 : 0;

                    $stmt = $conn->prepare("INSERT INTO responses (quiz_id, user_id, question_id, answer, score) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiii", $quiz_id, $user_id, $question_id, $a, $score);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                // Pour réponse libre
                $stmt = $conn->prepare("INSERT INTO responses (quiz_id, user_id, question_id, answer, score) VALUES (?, ?, ?, ?, ?)");
                $score = 0;
                $stmt->bind_param("iiisi", $quiz_id, $user_id, $question_id, $answer, $score);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    // Redirection vers dashboard après soumission
    header("Location: dashboard_user.php");
    exit();
}

// Récupérer les questions et options
$qstmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id=?");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$q_res = $qstmt->get_result();
$questions = [];
while($q = $q_res->fetch_assoc()) {
    // Récup options si QCM
    $opts = [];
    if($q['type'] === 'qcm') {
        $ostmt = $conn->prepare("SELECT id, text FROM options WHERE question_id=?");
        $ostmt->bind_param("i", $q['id']);
        $ostmt->execute();
        $o_res = $ostmt->get_result();
        while($o = $o_res->fetch_assoc()) {
            $opts[] = $o;
        }
        $ostmt->close();
    }
    $q['options'] = $opts;
    $questions[] = $q;
}
$qstmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Quiz : <?= htmlspecialchars($quiz['title']) ?></title>
<link rel="stylesheet" href="style.css?v=<?=time()?>"/>
</head>
<body>
<div class="container">
<h2>Quiz : <?= htmlspecialchars($quiz['title']) ?></h2>
<form method="POST">
<?php foreach($questions as $q): ?>
    <div class="question-block">
        <p><strong><?= htmlspecialchars($q['question_text']) ?></strong></p>

        <?php if($q['type'] === 'qcm'): ?>
            <?php foreach($q['options'] as $opt): ?>
                <label>
                    <input type="checkbox" name="question_<?= $q['id'] ?>[]" value="<?= $opt['id'] ?>" required>
                    <?= htmlspecialchars($opt['text']) ?>
                </label><br>
            <?php endforeach; ?>
        <?php else: ?>
            <textarea name="question_<?= $q['id'] ?>" rows="3" required></textarea>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<button type="submit" name="submit_answers">Envoyer mes réponses</button>
</form>
</div>
</body>
</html>
