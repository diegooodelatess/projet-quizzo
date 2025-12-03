<?php
session_start();
require_once 'db.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id,password,role,active FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $user = $res->fetch_assoc();
        if(!$user['active']){
            $error = "Votre compte est désactivé.";
        } elseif(password_verify($password,$user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] === 'admin') header("Location: admin_panel.php");
            elseif($user['role'] === 'user') header("Location: dashboard_user.php");
            else header("Location: dashboard_owner.php");
            exit();
        } else $error = "Mot de passe incorrect.";
    } else $error = "Utilisateur inconnu.";
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Connexion</h1>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
<label>Email :</label>
<input type="email" name="email" required>
<label>Mot de passe :</label>
<input type="password" name="password" required>
<button type="submit">Se connecter</button>
</form>
<p><a href="register.php">Créer un compte</a></p>
</div>
</body>
</html>
