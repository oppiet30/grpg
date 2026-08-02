<?php
include 'dbcon.php';
if ($_POST['submit']){
	$result = mysqli_query($conn, "UPDATE `styles` SET `value`='".$_POST['newcolor']."' WHERE `style`='2' AND `colornum`='".$_POST['colornum']."'");
}
// get style info
$cresult = mysqli_query($conn, "SELECT * FROM `styles` WHERE `style`='2'");
$i = 0;
echo "<table>";
while($line = mysqli_fetch_array($cresult, mysqli_ASSOC)) {
	$color[$i] = $line['value'];
	echo "<tr>";
	echo '<td style="background-color:'.$color[$i].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>'.$color[$i].'</td><td><form method="post"><input type="text" name="newcolor"><input type="hidden" name="colornum" value="'.$line['colornum'].'"><input type="submit" name = "submit" value = "Change"></form>';
	$i++;
}
//get style info
?>

