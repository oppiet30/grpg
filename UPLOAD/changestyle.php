<?php
include 'header.php';
if ($_GET['change'] == "yes"){
	echo Message("Your color scheme has been changed.");
}
if (isset($_GET['style'])) {
    $result= mysqli_query($conn, "UPDATE `grpgusers` SET `style`='".$_GET['style']."' WHERE `id`='".$user_class->id."'");
	echo Message("Please wait while your changes are being made...");
    mrefresh("changestyle.php?change=yes", 0);
}
?>
<tr><td class="contenthead">
Change Color Scheme
</td></tr>
<tr><td class="contentcontent">
Current Scheme: <?= $user_class->style; ?>
<?php
$cresult = mysqli_query($conn, "SELECT DISTINCT `style` FROM `styles`");
while($line = mysqli_fetch_array($cresult, mysqli_ASSOC)) {
	echo "<div><a href='changestyle.php?style=".$line['style']."'>Switch to theme #".$line['style']."</a></div>";
		// get style info
		$result = mysqli_query($conn, "SELECT * FROM `styles` WHERE `style`='".$line['style']."'");
		$i = 0;
		echo "<table><tr>";
		while($line2 = mysqli_fetch_array($result, mysqli_ASSOC)) {
			$color[$i] = $line2['value'];
			echo '<td style="background-color:'.$color[$i].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			$i++;
		}
		echo "</tr></table>";
		//get style info
}
?>

</td></tr>
<?php
include 'footer.php';
?>