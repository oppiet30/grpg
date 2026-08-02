<?
include 'dbcon.php';
include 'classes.php';

$resultgrow = mysqli_query($conn, "SELECT * FROM `growing`");
while($line = mysqli_fetch_array($resultgrow, mysqli_ASSOC)) {
	$lost = floor(rand(0, $line['amount'] * 5));
	if ($lost != 0){
		$newamount = $line['cropamount'] - $lost;
		Send_Event($line['userid'], $lost." of your ".$line['croptype']." plants have died. Crop ID:".$line['id']);
	}
	
	$resultgrowupdate = mysqli_query($conn, "UPDATE `growing` SET `cropamount` = '".$newamount."' WHERE `id` = '".$line['id']."'");
}

//delete rows that are empty and give back land to owner
$resultgrow = mysqli_query($conn, "SELECT * FROM `growing`");
while($line = mysqli_fetch_array($resultgrow, mysqli_ASSOC)) {
	if ($line['cropamount'] == 0){
		Give_Land($line['cityid'], $line['userid'], $line['amount']);
		$result = mysqli_query($conn, "DELETE FROM `growing` WHERE `id`='".$line['id']."'");
	}
}


//$result2 = mysqli_query($conn, "DELETE FROM `spylog` WHERE `age` < ".time() - 172800);// clear out old spy log stuff

$deletechat = mysqli_query($conn, "DELETE FROM `message`");

// Lottery Stuff
$checklotto = mysqli_query($conn, "SELECT * FROM `lottery`");
$numlotto = mysqli_num_rows($checklotto);
$amountlotto = $numlotto * 750;

$offset_result = mysqli_query($conn,  " SELECT FLOOR(RAND() * COUNT(*)) AS `offset` FROM `lottery` ");
$offset_row = mysqli_fetch_object( $offset_result );
$offset = $offset_row->offset;
$result = mysqli_query($conn,  " SELECT * FROM `lottery` LIMIT $offset, 1 " );
$worked = mysqli_fetch_array($result);

$winner = $worked['userid'];

$lottery_user = new User($worked['userid']);
$newmoney = $lottery_user->money + $amountlotto;;
Send_Event($lottery_user->id, "You won the lottery! Congratulations, you won $".$amountlotto);
$result2 = mysqli_query($conn, "UPDATE `grpgusers` SET `money` = '".$newmoney."' WHERE `id` = '".$lottery_user->id."'");
$result2 = mysqli_query($conn, "DELETE FROM `lottery`");
// Lottery Stuff

$result = mysqli_query($conn, "SELECT * FROM `grpgusers`");
while($line = mysqli_fetch_assoc($result)) {
	$updates_user = new User($line['id']);
	$newmoney = $updates_user->money;
	$username = $updates_user->username;
	$newrmdays = $updates_user->rmdays - 1;
	$newrmdays = ($newrmdays < 0) ? 0 : $newrmdays;
	if($newrmdays > 1) {
		$interest = .04;
	} else {
		$interest = .02;
	}
	$newbank = ceil($updates_user->bank + ($updates_user->bank * $interest));
	if($updates_user->job != 0){
		$result_job = mysqli_query($conn, "SELECT * FROM `jobs` WHERE `id`='".$updates_user->job."'");
		$worked_job = mysqli_fetch_array($result_job);
		$newmoney = $newmoney + $worked_job['money'];
		Send_Event($updates_user->id, "You earned $".$worked_job['money']." from your job. You now have $".$newmoney);
	}

	// hooker stuff
	if($updates_user->hookers > 0){
		$newmoney = $newmoney + (300 * $updates_user->hookers);
		Send_Event($updates_user->id, "You earned $".($updates_user->hookers * 300)." from your hookers. You now have $".$newmoney);
	}
	//hooker stuff
	$result2 = mysqli_query($conn, "UPDATE `grpgusers` SET `money` = '".$newmoney."', `rmdays` = '".$newrmdays."', `bank` = '".$newbank."', `searchdowntown` = '100' WHERE `username` = '".$username."'");
}
?>