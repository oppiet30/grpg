<?php
include 'header.php';
echo '<tr><td class="contenthead">Users Online In The Last 24 Hours</td></tr>';
$result = mysqli_query($conn, "SELECT * FROM `grpgusers` ORDER BY `lastactive` DESC");
echo '<tr><td class="contentcontent">';
	while($line = mysqli_fetch_array($result, mysqli_ASSOC)) {
		$secondsago = time()-$line['lastactive'];
		if ($secondsago<=86400) {
			$user_online = new User($line['id']);
			echo "<div>".$user_online->formattedname . " ". howlongago($user_online->lastactive)."</div>";
		}
	}
echo '</td></tr>';

include 'footer.php'
?>