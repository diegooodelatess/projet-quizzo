<?php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");

if(!isset($_GET['quiz_id'])) header("Location: dashboard_owner.php");

$quiz_id = intval($_GET['quiz_id']);
$owner_id = $_SESSION['user_id'];

// Vérifier que le quiz appartient bien au propriétaire
$stmt = $conn->prepare("SELECT title FROM quizzes WHERE id=? AND owner_id=?");
$stmt->bind_param("ii",$quiz_id,$owner_id);
$stmt->execute();
$res=$stmt->get_result();
if($res->num_rows==0){
    header("Location: dashboard_owner.php");
    exit();
}
$quiz=$res->fetch_assoc();
$stmt->close();

// Récupérer les questions
$stmt = $conn->prepare("SELECT id,question_text FROM questions WHERE quiz_id=?");
$stmt->bind_param("i",$quiz_id);
$stmt->execute();
$questions_res=$stmt->get_result();
$questions=[];
while($q=$questions_res->fetch_assoc()){
    $questions[$q['id']]=$q['question_text'];
}
$stmt->close();

// Récupérer les réponses des utilisateurs
$res = $conn->query("SELECT r.user_id,u.email,r.question_id,r.answer,r.score 
                     FROM responses r 
                     JOIN users u ON r.user_id=u.id 
                     WHERE r.quiz_id=$quiz_id 
                     ORDER BY u.email,r.question_id");

$users=[]; // tableau utilisateurs => [question_id => score, ...]
while($r=$res->fetch_assoc()){
    $uid = $r['user_id'];
    if(!isset($users[$uid])) $users[$uid] = ['email'=>$r['email'],'scores'=>[]];
    $users[$uid]['scores'][$r['question_id']] = ['answer'=>$r['answer'],'score'=>$r['score']];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Réponses Quiz - <?=htmlspecialchars($quiz['title'])?></title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Réponses - <?=htmlspecialchars($quiz['title'])?></h1>

<?php if(empty($users)): ?>
<p>Aucune réponse pour ce quiz.</p>
<?php else: ?>
<table border="1" width="100%">
<tr>
<th>Utilisateur</th>
<?php foreach($questions as $qid=>$qtext): ?>
<th><?=htmlspecialchars($qtext)?></th>
<?php endforeach; ?>
<th>Total</th>
</tr>
<?php foreach($users as $u): ?>
<tr>
<td><?=htmlspecialchars($u['email'])?></td>
<?php $total=0; foreach($questions as $qid=>$qtext): 
$score = $u['scores'][$qid]['score'] ?? 0;
$total += $score;
$answer = $u['scores'][$qid]['answer'] ?? '-';
?>
<td><?=htmlspecialchars($answer)?> (<?=$score?>)</td>
<?php endforeach; ?>
<td><?=$total?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p><a href="dashboard_owner.php">Retour au dashboard</a></p>
</div>
</body>
</html>
