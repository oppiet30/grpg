<?php
include 'dbcon.php';

$result = mysqli_query($conn, "SELECT * FROM `inventory` ORDER BY `userid` DESC");
$howmanytotal = mysqli_num_rows($result);

while($line = mysqli_fetch_array($result, mysqli_ASSOC)) {

	$result2 = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `userid` = '".$line['userid']."' AND `itemid` = '".$line['itemid']."'");
	$howmanyrows = mysqli_num_rows($result2);
	$worked2 = mysqli_fetch_array($result2);
	if ($howmanyrows>0) {
		$result3= mysqli_query($conn, "INSERT INTO `newinventory` (userid, itemid, quantity)"."VALUES ('".$line['userid']."', '".$line['itemid']."', '".$howmanyrows."')");
		$result4 = mysqli_query($conn, "DELETE FROM `inventory` WHERE `userid` = '".$line['userid']."' AND `itemid` = '".$line['itemid']."'");
		
	}

}

?>