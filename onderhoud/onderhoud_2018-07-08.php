<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE `$TableResultaat` ADD `$ResultaatNew` SET('0','1') NOT NULL DEFAULT '0' AFTER `$ResultaatOpenHuis`, ADD `$ResultaatPrijsMail` INT(8) NOT NULL AFTER `$ResultaatNew`";
mysql_query($sql);

$sql = "ALTER TABLE `$TableResultaat` CHANGE $ResultaatNew` $ResultaatNew` SET('0','1') NOT NULL DEFAULT '1'";
mysql_query($sql);

$sql = "UPDATE `$TableResultaat` SET `$ResultaatPrijsMail` = `$ResultaatPrijs`";
mysql_query($sql);

$sql = "CREATE TABLE `$TableStraten` (";
$sql .= "  `$StratenID` int(6) NOT NULL,";                                      
$sql .= "  `$StratenActive` set('0','1') NOT NULL DEFAULT '1',";
$sql .= "  `$StratenStrLeesbaar` text NOT NULL,";                      
$sql .= "  `$StratenStrFunda` text NOT NULL,";
$sql .= "  `$StratenStad` text NOT NULL,";
$sql .= "  `$StratenLastCheck` int(11) NOT NULL";
$sql .= ")";
mysql_query($sql);

?>