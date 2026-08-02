<?
include 'dbcon.php';
include 'classes.php';

$user_voted = new User($_GET['id']);
$points = $user_voted->points + 10;
$result = mysqli_query($conn, "UPDATE `grpgusers` SET `points` = '".$points."' WHERE `id`='".$user_voted->id."'");
?>