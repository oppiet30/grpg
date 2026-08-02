<?php
include 'header.php';
echo '<tr><td class="contenthead">Total Users</td></tr>';
echo '<tr><td class="contentcontent">';
$result = mysqli_query($conn, "SELECT * FROM `grpgusers` ORDER BY `ip` ASC");

	while($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$result1 = mysqli_query($conn, "SELECT * FROM `grpgusers` WHERE `ip`='".$line['ip']."'");
			if (mysqli_num_rows($result1) > 1){
				$user_online = new User($line['id']);
				echo "<div>".$user_online->ip.".)".$user_online->formattedname."</div>";
			}
	}
echo "</td></tr>";
include 'footer.php'
?>