<?php
session_start();
require_once 'db.php';

// Vérification du rôle propriétaire (school ou company)
if (!in_array($_SESSION['role'], ['school', 'company'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['user_id'];

// Récupération des quiz du propriétaire
$quizzes_res = $conn->query("SELECT id, title, is_active FROM quizzes WHERE owner_id=$owner_id");
$quizzes = [];

while ($q = $quizzes_res->fetch_assoc()) {
    $quiz_id = $q['id'];

    // Récupération des réponses détaillées
    $resp_stmt = $conn->prepare("
        SELECT r.id AS response_id, u.email, r.question_id, r.answer, r.score,
               q.question_text, q.type
        FROM responses r
        JOIN users u ON r.user_id = u.id
        JOIN questions q ON r.question_id = q.id
        WHERE r.quiz_id = ?
        ORDER BY u.email, q.id
    ");
    $resp_stmt->bind_param("i", $quiz_id);
    $resp_stmt->execute();
    $resp_res = $resp_stmt->get_result();

    $user_responses = [];

    while ($row = $resp_res->fetch_assoc()) {
        $email = $row['email'];

        if (!isset($user_responses[$email])) {
            $user_responses[$email] = [];
        }

        $user_responses[$email][] = [
            'question' => $row['question_text'],
            'type'     => $row['type'],
            'answer'   => $row['answer'],
            'score'    => $row['score']
        ];
    }

    $resp_stmt->close();

    $q['responses'] = $user_responses;
    $quizzes[] = $q;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Propriétaire</title>
    <link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>

<body>
<div class="container">
    <img src="logo.png" class="logo">
    <h1>Vos Quiz</h1>

    <a class="btn" href="create_quiz.php" style="margin-bottom:20px; display:inline-block;">
        ➕ Créer un quiz
    </a>

    <?php foreach ($quizzes as $quiz): ?>
        <div class="owner-quiz-card">

            <!-- Titre du quiz + statut -->
            <h3>
                <?= htmlspecialchars($quiz['title']) ?>
                <span class="<?= $quiz['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $quiz['is_active'] ? "(Actif)" : "(Inactif)" ?>
                </span>
            </h3>

            <!-- Bouton modifier -->
            <a class="btn-edit" href="edit_quiz.php?id=<?= $quiz['id'] ?>">
                ✏️ Modifier le quiz
            </a>

            <hr>

            <!-- Affichage des réponses -->
            <?php if (!empty($quiz['responses'])): ?>
                <?php foreach ($quiz['responses'] as $email => $answers): ?>

                    <div class="user-block">
                        <h4>Utilisateur : <?= htmlspecialchars($email) ?></h4>
                        <ul>
                            <?php foreach ($answers as $a): ?>
                                <li>
                                    <strong><?= htmlspecialchars($a['question']) ?></strong><br>

                                    Réponse :
                                    <span class="answer"><?= htmlspecialchars($a['answer']) ?></span>

                                    <?php if ($a['score'] > 0): ?>
                                        <span class="correct">✔ Correct</span>
                                    <?php else: ?>
                                        <span class="wrong">✘ Incorrect</span>
                                    <?php endif; ?>

                                    – Points : <?= $a['score'] ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune réponse pour ce quiz.</p>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>

    <p style="text-align:center; margin-top:20px;">
        <a class="btn" href="logout.php">Se déconnecter</a>
    </p>
</div>
</body>
</html>
