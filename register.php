<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit();
}

$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name  = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email_raw  = isset($_POST['email']) ? trim($_POST['email']) : '';
$email      = mb_strtolower($email_raw, 'UTF-8');
$username   = isset($_POST['username']) ? trim($_POST['username']) : '';
$password   = isset($_POST['password']) ? $_POST['password'] : '';
$role       = isset($_POST['role']) ? $_POST['role'] : 'user';
$captcha    = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

$errors = [];
if ($first_name === '' || $last_name === '') $errors[] = "Prénom et nom requis.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
if ($username === '' || mb_strlen($username) < 3) $errors[] = "Identifiant invalide (min 3).";
if (mb_strlen($password) < 6) $errors[] = "Mot de passe trop court (min 6).";
$allowed_roles = ['user','school','company','admin'];
if (!in_array($role, $allowed_roles, true)) $errors[] = "Rôle invalide.";
if ($captcha !== '7') $errors[] = "CAPTCHA incorrect.";

if (!empty($errors)) {
    die(htmlspecialchars($errors[0]));
}

$sql_check = "SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1";
$stmt = $conn->prepare($sql_check);
if (!$stmt) die("Erreur prepare: " . $conn->error);
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (mb_strtolower($row['username'], 'UTF-8') === mb_strtolower($username, 'UTF-8')) {
        $stmt->close();
        die("Ce nom d'utilisateur existe déjà.");
    }
    if (mb_strtolower($row['email'], 'UTF-8') === $email) {
        $stmt->close();
        die("Cet email est déjà utilisé.");
    }
}
$stmt->close();

$hashed = password_hash($password, PASSWORD_DEFAULT);
$sql_insert = "INSERT INTO users (username, password, role, first_name, last_name, email, active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
$stmt2 = $conn->prepare($sql_insert);
if (!$stmt2) die("Erreur prepare insert: " . $conn->error);
$stmt2->bind_param("ssssss", $username, $hashed, $role, $first_name, $last_name, $email);

if ($stmt2->execute()) {
    $stmt2->close();
    $conn->close();
    header("Location: login.html?registered=1");
    exit();
} else {
    $err = $stmt2->error;
    $stmt2->close();
    $conn->close();
    die("Erreur lors de l'inscription: " . htmlspecialchars($err));
}
