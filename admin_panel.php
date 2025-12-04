<?php
// admin_panel.php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='admin') header("Location: dashboard_user.php");

// Activation / Désactivation comptes
if(isset($_GET['user_id'],$_GET['action'])){
    $id = intval($_GET['user_id']);
    $active = $_GET['action']==='activate'?1:0;
    $stmt=$conn->prepare("UPDATE users SET is_active=? WHERE id=?");
    $stmt->bind_param("ii",$active,$id);
    $stmt->execute();
    $stmt->close();
}
// Activation / Désactivation quiz
if(isset($_GET['quiz_id'],$_GET['action'])){
    $id = intval($_GET['quiz_id']);
    $active = $_GET['action']==='activate'?1:0;
    $stmt=$conn->prepare("UPDATE quizzes SET is_active=? WHERE id=?");
    $stmt->bind_param("ii",$active,$id);
    $stmt->execute();
    $stmt->close();
}

// Liste utilisateurs et quiz
$users = $conn->query("SELECT id,email,role,is_active FROM users");
$quizzes = $conn->query("SELECT id,title,owner_id,is_active FROM quizzes");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Panneau Admin</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
<img src="logo.png" class="logo" alt="Logo">
<h1>Panneau Admin</h1>

<h2>Utilisateurs</h2>
<table>
<tr><th>Email</th><th>Rôle</th><th>Actif</th><th>Actions</th></tr>
<?php while($u=$users->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($u['email'])?></td>
<td><?=htmlspecialchars($u['role'])?></td>
<td><?= $u['is_active'] ? "Oui":"Non"?></td>
<td>
<?php if($u['role']!=='admin'): ?>
<a class="small" href="?user_id=<?=$u['id']?>&action=<?=$u['is_active']?'deactivate':'activate'?>"><?=$u['is_active']?'Désactiver':'Activer'?></a>
<?php else: ?>
— 
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>

<h2>Quiz</h2>
<table>
<tr><th>Titre</th><th>Propriétaire</th><th>Actif</th><th>Actions</th></tr>
<?php while($q=$quizzes->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($q['title'])?></td>
<td><?=htmlspecialchars($q['owner_id'])?></td>
<td><?=$q['is_active']?"Oui":"Non"?></td>
<td>
<a class="small" href="?quiz_id=<?=$q['id']?>&action=<?=$q['is_active']?'deactivate':'activate'?>"><?=$q['is_active']?'Désactiver':'Activer'?></a>
</td>
</tr>
<?php endwhile; ?>
</table>

<p><a class="btn" href="logout.php">Se déconnecter</a></p>
</div>
</body>
</html>
