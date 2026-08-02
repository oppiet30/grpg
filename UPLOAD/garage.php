<?php
include 'header.php';
?>
<tr><td class="contenthead">Your Garage</td></tr>
<tr><td class="contentcontent">Here is where you keep all of your sweet rides.</td></tr>
<?php
$result = mysqli_query($conn, "SELECT * FROM `cars` WHERE `userid` = '".$user_class->id."' ORDER BY `userid` DESC");

	while($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
	$result2 = mysqli_query($conn, "SELECT * FROM `carlot` WHERE `id`='".$line['carid']."'");
    $worked2 = mysqli_fetch_array($result2);
		$cars .= "

		<td width='25%' align='center'>

		<img src='". $worked2['image']."' width='100' height='100' style='border: 1px solid #333333'><br>
		". car_popup($worked2['name'], $line['carid']) ."
		</td>
		";
	}
	

if ($cars != ""){
 ?>
<tr><td class="contenthead">Your Garage</td></tr>
<tr><td class="contentcontent">
<table width='100%'>
				<tr>
				<?php echo $cars; ?>
				</tr>
			</table>
</td></tr>
<?php
}
include 'footer.php'
?>