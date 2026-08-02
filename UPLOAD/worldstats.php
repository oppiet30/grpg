<?
include 'header.php';
$result = mysqli_query($conn, "SELECT * FROM `grpgusers` ORDER BY `id` ASC");
$totalmobsters = mysqli_num_rows($result);
$result2 = mysqli_query($conn, "SELECT * FROM `grpgusers` WHERE `rmdays`!='0'");
$totalrm = mysqli_num_rows($result2);
?>
<tr><td class="contenthead">World Stats (more will be added soon)</td></tr>
<tr><td class="contentcontent">

<table width='100%' cellpadding='4' cellspacing='0'>
<tr>
	<td class='textl' width='15%'>Mobsters:</td>
	<td class='textr' width='35%'><?= $totalmobsters ?></td>
	<td class='textl'>Respected Mobsters:</td>
	<td class='textr'><?= $totalrm ?></td>
</tr>
</table>
</td></tr>
<?
include 'footer.php';
?>