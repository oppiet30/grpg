<?php
include 'header.php';
echo '<tr><td class="contenthead">Total Users</td></tr>';
echo '<tr><td class="contentcontent">';
$result = mysqli_query($conn, "SELECT * FROM `grpgusers` ORDER BY `id` ASC");

	while($line = mysqli_fetch_array($result, mysqli_ASSOC)) {
		$secondsago = time()-$line['lastactive'];
			$user_online = new User($line['id']);
			echo "<div>".$user_online->id.".)".$user_online->formattedname."</div>";
	}
echo "</td></tr>";
include 'footer.php'
?>