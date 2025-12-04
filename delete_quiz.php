<?php
// delete_quiz.php
session_start();
require_once 'db.php';

if(!in_array($_SESSION['role'], ['school','company'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['id'] ?? 0);

// Vérifier que le quiz appartient au propriétaire
$stmt = $conn->prepare("SELECT id FROM quizzes WHERE id=? AND owner_id=?");
$stmt->bind_param("ii", $quiz_id, $owner_id);
$stmt->execute();
$res = $stmt->get_result();
$quiz = $res->fetch_assoc();
$stmt->close();

if(!$quiz) die("Accès refusé : ce quiz ne vous appartient pas.");

// Supprimer les réponses liées
$stmt = $conn->prepare("DELETE FROM responses WHERE quiz_id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$stmt->close();

// Supprimer les options des questions
$stmt = $conn->prepare("DELETE o FROM options o JOIN questions q ON o.question_id=q.id WHERE q.quiz_id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$stmt->close();

// Supprimer les questions
$stmt = $conn->prepare("DELETE FROM questions WHERE quiz_id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$stmt->close();

// Supprimer le quiz
$stmt = $conn->prepare("DELETE FROM quizzes WHERE id=? AND owner_id=?");
$stmt->bind_param("ii", $quiz_id, $owner_id);
$stmt->execute();
$stmt->close();

header("Location: dashboard_owner.php");
exit();
