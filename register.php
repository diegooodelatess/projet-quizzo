<?php
session_start();
require_once 'db.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if(empty($email) || empty($password)){
        $error = "Email et mot de passe obligatoires.";
    } else {
        // Vérifier si l'admin existe déjà
        if($role === 'admin'){
            $checkAdmin = $conn->query("SELECT id FROM users WHERE role='admin'");
            if($checkAdmin->num_rows > 0){
                $error = "Un administrateur existe déjà.";
            }
        }

        // Vérifier email unique
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0){
            $error = "Email déjà utilisé.";
        }
        $stmt->close();

        if(!$error){
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email,password,role,active,created_at) VALUES (?,?,?,1,NOW())");
            $stmt->bind_param("sss",$email,$hash,$role);
            $stmt->execute();
            $stmt->close();
            header("Location: login.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer un compte</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Créer un compte</h1>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
<label>Email :</label>
<input type="email" name="email" required>
<label>Mot de passe :</label>
<input type="password" name="password" required>
<label>Rôle :</label>
<select name="role" required>
    <option value="user">Utilisateur</option>
    <option value="school">École</option>
    <option value="company">Entreprise</option>
    <option value="admin">Administrateur</option>
</select>
<button type="submit">S’inscrire</button>
</form>
<p><a href="login.php">Connexion</a></p>
</div>
</body>
</html>
