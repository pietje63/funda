<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
connect_db();

echo $HTMLHeader;

if(isset($_POST['add'])) {
	foreach($_POST['huis'] as $huis) {
		$sql_check = "SELECT * FROM $TableListResult WHERE $ListResultList like ". $_POST['lijst'] ." AND $ListResultHuis like '$huis'";
		$result	= mysql_query($sql_check);
		if(mysql_num_rows($result) == 0) {
			$sql_insert = "INSERT INTO $TableListResult ($ListResultList, $ListResultHuis) VALUES (". $_POST['lijst'] .", $huis)";
			if(!mysql_query($sql_insert)) {
				echo '<b>'. $huis .' niet toegevoegd</b><br>';
			} else {
				echo $huis .' toegevoegd<br>';
			}
		} else {
			echo $huis .' bestaat al<br>';
		}			
	}
} elseif(isset($_REQUEST['selectie'])) {
	$groep	= substr($_REQUEST['selectie'], 0, 1);
	$id			= substr($_REQUEST['selectie'], 1);
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$from					= "$TableResultaat, $TableHuizen";
		$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $id";
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$from					= "$TableListResult, $TableHuizen";
		$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $id";
	}
		
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
	}
	
	$sql		= "SELECT min($TableHuizen.$HuizenStart) FROM $from WHERE $where";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$start_tijd = $row[0];
	
	$sql		= "SELECT max($TableHuizen.$HuizenEind) FROM $from WHERE $where";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$eind_tijd = $row[0];
	
	$sql		= "SELECT $TableHuizen.$HuizenOffline, $TableHuizen.$HuizenVerkocht, $TableHuizen.$HuizenStart, $TableHuizen.$HuizenEind, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, ($TableHuizen.$HuizenEind - $TableHuizen.$HuizenStart) as tijdsduur FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result); 
	
	$fullWidth = $eind_tijd - $start_tijd;
	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center'><h1>Tijdslijn '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr><td>\n";
	echo "	<table width='100%' border=0><tr>\n";
	echo "	<td width='25%'>&nbsp;</td>\n";
	echo "	<td width='35%' align='left'>". date("d M y", $start_tijd) ."</td>\n";
	echo "	<td width='35%' align='right'>". date("d M y", $eind_tijd) ."</td>\n";
	echo "	<td width='5%' align='right'>&nbsp;</td>\n";
	echo "	</tr></table>\n";
	echo "</td></tr>\n";
	
	
	do {
		$breedte_1	= round(70*($row[$HuizenStart] - $start_tijd)/$fullWidth);
		$breedte_2	= round(70*($row[$HuizenEind] - $row[$HuizenStart])/$fullWidth);
		//$breedte_3	= round(70*($eind_tijd - $row[$HuizenEind])/$fullWidth);
		$breedte_3	= 70 - $breedte_1 - $breedte_2;;
		$adres			= convertToReadable(urldecode($row[$HuizenAdres]));
		
		$prijzen	= getPriceHistory($row[$HuizenID]);
		$laatste	= current($prijzen);
		$eerste		= end($prijzen);
				
		if(max($prijzen) > 0) {
			$percentageAll	= 100*($eerste - $laatste)/$eerste;
		} else {
			$percentageAll = 0;
		}		
		
		if($row[$HuizenOffline] == '1') {
			if($row[$HuizenVerkocht] != '1') {
				$class = 'offline';
			} else {
				$class = 'offlineVerkocht';
			}			
		} elseif($row[$HuizenVerkocht] == '1') {
			$class = 'onlineVerkocht';
		} else {
			$class = 'online';
		}
		
		echo "<tr><td>\n";
		echo "	<table width='100%' border=0><tr>\n";
		echo "		<td width='25%'>";
		if($showListAdd)	echo "	<input type='checkbox' name='huis[]' value='". $row[$HuizenID] ."'>";
		echo "<a id='". $row[$HuizenID] ."'><a href='admin/HouseDetails.php?selectie=". $_REQUEST['selectie'] ."&id=". $row[$HuizenID] ."'><img src='http://www.vvaltena.nl/styles/img/details/report.png'></a> <a href='http://www.funda.nl". urldecode($row[$HuizenURL]) ."' target='_blank' class='$class'>$adres</a></td>\n";
		if($breedte_1 != 0) { echo "		<td width='". $breedte_1 ."%'>&nbsp;</td>\n"; }
		echo "		<td width='". $breedte_2 ."%' bgcolor='#FF6D6D' title='In de verkoop van ". date("d-m", $row[$HuizenStart]) .' t/m '. date("d-m", $row[$HuizenEind]) ."'>". getDoorloptijd($row[$HuizenID]) ."</td>\n";
		if($breedte_3 != 0) { echo "		<td width='". $breedte_3 ."%'>&nbsp;</td>\n"; }
		echo "		<td width='5%' align='right'><a href='PrijsDaling.php?selectie=". $_REQUEST['selectie'] ."#". $row[$HuizenID] ."'>". number_format($percentageAll, 0) ."%</a></td>\n";			
		echo "	</tr></table>\n";
		echo "</td></tr>\n";
	} while($row = mysql_fetch_array($result));
	
	if($showListAdd) {
		echo "<tr>\n";
		echo "	<td>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td>";
		echo "	<select name='lijst'>";
		
		$Lijsten = getLijsten(1);					
		foreach($Lijsten as $LijstID) {
			$LijstData = getLijstData($LijstID);
			echo "	<option value='$LijstID' ". ($_POST['chosenList'] == $LijstID ? ' selected' : '') .">". $LijstData['naam'] ."</option>";		
		}
		
		echo "	</select>";
		echo "	<input type='submit' name='add' value='Voeg toe'>";
		echo "	</td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
	echo "</form>\n";
} else {
	$Opdrachten = getZoekOpdrachten(1);
	$Lijsten		= getLijsten(1);
	
	// Als er geen lijsten zijn of als er huizen aan een lijst worden toegevoegd
	// (het is zinloos om dan lijsten te laten zien) de lijsten disablen
	if(count($Lijsten) == 0 || isset($_REQUEST['addHouses'])) {
		$showList = false;
	} else {
		$showList = true;
	}
	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	echo "<input type='hidden' name='addHouses' value='". (isset($_REQUEST['addHouses']) ? '1' : '0') ."'>\n";
	echo "<input type='hidden' name='chosenList' value='". $_REQUEST['chosenList'] ."'>\n";
	echo "<table>\n";
	echo "<tr>\n";
	echo "	<td>Selectie</td>\n";	
	echo "	<td>&nbsp;</td>\n";
	echo "	<td><select name='selectie'>\n";
	echo "	<optgroup label='Zoekopdrachten'>\n";
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		echo "	<option value='Z$OpdrachtID'>". $OpdrachtData['naam'] ."</option>\n";
	}
	
	echo "	</optgroup>\n";
	echo "	<optgroup label='Lijsten'". ($showList ? '' : ' disabled') .">\n";
	
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		echo "	<option value='L$LijstID'>". $LijstData['naam'] ."</option>\n";
	}
	
	echo "	</optgroup>\n";
	echo "	</select>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='3' align='center'><input type='submit' name='submit' value='Weergeven'></td>\n";
	echo "</tr>\n";
	echo "<table>\n";
	echo "</form>\n";
}

echo $HTMLFooter;
?>