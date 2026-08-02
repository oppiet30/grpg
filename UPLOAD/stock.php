<?
include 'dbcon.php';

$result = mysqli_query($conn, "SELECT * FROM `stocks`");
while($line = mysqli_fetch_assoc($result)) {
	$amount = rand (strlen($line['cost']) * -1, strlen($line['cost']));
	$newamount = $line['cost'] + $amount;
	if ($newamount < 1){
		$newamount = 1;
	}
	$result2 = mysqli_query($conn, "UPDATE `stocks` SET `cost`='".$newamount."' WHERE `id`='".$line['id']."'");
}

?>