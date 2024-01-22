<?php
$dbhost = "localhost";
$dbname = "pan2";
$dbuser = "root";
$dbpass = "upCDqaQHCXK9F8HT";
$charset = "utf-8";
$FechaActual = date('Y-m-d');
$profiles = "profiles=\"amz\" ";
$key = "AH397ZG2051700";

if ($key <> "") {
	if (!isset($_GET["key"])) {
		mysql_close($conn);
		exit;
	}
	if ($_GET["key"] == "") {
		mysql_close($conn);
		exit;
	}
	if ($key <> $_GET["key"]) {
		mysql_close($conn);
		exit;
	}
}

$conn = @mysql_connect($dbhost,$dbuser,$dbpass) or die ("ERROR! No connection to database!");
mysql_select_db($dbname, $conn);
$usql = mysql_query("SELECT * FROM accounts");

header("Content-type: text/xml; charset=".$charset);
print("<xml-user-manager ver=\"1.0\">\n");
while($line=mysql_fetch_array($usql)) {
	if ($line["status"] == "0" or $line["expires"] < $FechaActual ) {
              $status = "0";
		$Enabled = "false";
	} else {
              $status = "1";
		$Enabled = "true";
	}
	if ($line["account"] == "") {
		$usuario = "";
	} else {
		$usuario = "name=\"".$line["account"]."\" ";
	}
	
	if ($line["password"] == "") {
		$contrasena = "";
	} else {
		$contrasena = "password=\"".$line["password"]."\" ";
	}
	
	if ($line["expires"] == "" ) {
		$enabled = "";
	} else {
		$enabled = "enabled=\"".$Enabled."\" ";
	}

	if ($line["maxlogin"] == "" ) {
		$max = "";
	} else {
		$max = "max-connections=\"".$line["maxlogin"]."\" ";
	}
	
	print("<user ".$usuario.$contrasena.$per.$enabled.$max."/>\n");
}
print("</xml-user-manager>");
mysql_close($conn);
?>


