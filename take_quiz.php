<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) header("Location: login.php");
$user_id = $_SESSION['user_id'];

$quiz_id = intval($_GET['quiz_id'] ?? 0);
if (!$quiz_id) die("Quiz non spécifié.");

// Vérifier si déjà répondu
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM responses WHERE user_id=? AND quiz_id=?");
$stmt->bind_param("ii", $user_id, $quiz_id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

if ($count > 0) header("Location: dashboard_user.php");

// Récup quiz
$stmt = $conn->prepare("SELECT title, access_key, is_active FROM quizzes WHERE id=? AND is_active=1");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quiz) die("Quiz introuvable.");

// Vérification de la clé
$error = '';
if (isset($_POST['access_key'])) {
    if ($_POST['access_key'] !== $quiz['access_key']) {
        $error = "Clé d'accès incorrecte.";
    } else {
        $_SESSION['quiz_access'][$quiz_id] = true;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?></title>

    <!-- 🔥 TON STYLE.CSS ICI 🔥 -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Formulaire de clé d'accès
if (empty($_SESSION['quiz_access'][$quiz_id])) {
?>
    <div class="container">
    <form method="POST">
        <h2><?= htmlspecialchars($quiz['title']) ?></h2>
        <?php if ($error) echo "<p class='error'>$error</p>"; ?>
        <label>Clé d'accès :</label>
        <input type="text" name="access_key" required>
        <button type="submit" class="btn">Valider</button>
    </form>
    </div>
</body>
</html>
<?php 
    exit();
}

// Envoi des réponses
if (isset($_POST['submit_answers'])) {

    foreach ($_POST as $key => $val) {
        if (strpos($key, "question_") === 0) {

            $q_id = intval(str_replace("question_", "", $key));
            $answer = $val;

            // Récupération du type
            $stmt = $conn->prepare("SELECT type FROM questions WHERE id=?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $type = $stmt->get_result()->fetch_assoc()['type'];
            $stmt->close();

            // ===== QCM =====
            if ($type === 'qcm') {

                if (!is_array($answer)) $answer = [$answer];

                // bonnes réponses
                $opt_stmt = $conn->prepare("SELECT id FROM options WHERE question_id=? AND is_correct=1");
                $opt_stmt->bind_param("i", $q_id);
                $opt_stmt->execute();
                $opt_res = $opt_stmt->get_result();

                $correct = [];
                while ($o = $opt_res->fetch_assoc()) $correct[] = $o['id'];
                $opt_stmt->close();

                foreach ($answer as $a) {
                    $a = intval($a);
                    $score = in_array($a, $correct) ? 1 : 0;

                    $stmt = $conn->prepare("
                        INSERT INTO responses (quiz_id, question_id, user_id, answer, score)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiiis", $quiz_id, $q_id, $user_id, $a, $score);
                    $stmt->execute();
                    $stmt->close();
                }

            }
            // ===== YES/NO =====
            elseif ($type === 'yn') {

                $stmt = $conn->prepare("SELECT text FROM options WHERE question_id=? AND is_correct=1 LIMIT 1");
                $stmt->bind_param("i", $q_id);
                $stmt->execute();
                $correct_answer = $stmt->get_result()->fetch_assoc()['text'] ?? '';
                $stmt->close();

                $score = ($answer === $correct_answer) ? 1 : 0;

                $stmt = $conn->prepare("
                    INSERT INTO responses (quiz_id, question_id, user_id, answer, score)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiisi", $quiz_id, $q_id, $user_id, $answer, $score);
                $stmt->execute();
                $stmt->close();
            }
            // ===== REP LIBRE =====
            else {

                $score = 0;

                $stmt = $conn->prepare("
                    INSERT INTO responses (quiz_id, question_id, user_id, answer, score)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiisi", $quiz_id, $q_id, $user_id, $answer, $score);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: dashboard_user.php");
    exit();
}

// Récup questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$q_res = $stmt->get_result();

$questions = [];
while ($q = $q_res->fetch_assoc()) {

    if ($q['type'] === 'qcm' || $q['type'] === 'yn') {
        $o_stmt = $conn->prepare("SELECT id, text FROM options WHERE question_id=?");
        $o_stmt->bind_param("i", $q['id']);
        $o_stmt->execute();
        $opts = $o_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $o_stmt->close();
        $q['options'] = $opts;
    }

    $questions[] = $q;
}
$stmt->close();
?>

<div class="container">
<form method="POST">
    <h2><?= htmlspecialchars($quiz['title']) ?></h2>

    <?php foreach ($questions as $q): ?>
        <div class="question-block">
            <p class="question-text"><?= htmlspecialchars($q['question_text']) ?></p>

            <?php if ($q['type'] === 'qcm'): ?>
                <?php foreach ($q['options'] as $opt): ?>
                    <label class="option">
                        <span><?= htmlspecialchars($opt['text']) ?></span>
                        <input type="checkbox" name="question_<?= $q['id'] ?>[]" value="<?= $opt['id'] ?>">
                    </label>
                <?php endforeach; ?>

            <?php elseif ($q['type'] === 'yn'): ?>
                <?php foreach ($q['options'] as $opt): ?>
                    <label class="yn-option">
                        <span><?= htmlspecialchars($opt['text']) ?></span>
                        <input type="radio" name="question_<?= $q['id'] ?>" value="<?= $opt['text'] ?>">
                    </label>
                <?php endforeach; ?>

            <?php else: ?>
                <textarea name="question_<?= $q['id'] ?>" rows="3"></textarea>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" name="submit_answers" class="btn">Envoyer mes réponses</button>
</form>
</div>

</body>
</html>
