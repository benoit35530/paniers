<html>
<head>
<title>Administration des Paniers d'Eden</title>
<?php
if($export == "excel") {
    echo "<meta http-equiv=\"Content-Type\" content=\"application/vnd.ms-excel; charset=utf-8\" />";
}
?>
<style>
body {
	background-color: #FFFFFF;
	color: #000000;
	font-size: 10pt;
	font-family: "Arial";
	margin: 5;
}

table {
	border-collapse: collapse;
}

h2 {
	font-size: 12pt;
	color: #000000;
	margin: 10px;
}

.tdliste {
	border: solid 1px #000000;
	font-size: 10pt;
	color: #000000;
	background-color: #FFFFFF;
}

.tdlisteinv {
	border: solid 1px #000000;
	font-size: 10pt;
	color: #000000;
	background-color: #EEEEEE;
}

.thliste {
	border: solid 1px #000000;
	font-size: 10pt;
	color: #000000;
	background-color: #CCCCCC;
	font-weight: bold;
}

.thlistenormal {
	border: solid 1px #000000;
	font-size: 10pt;
	color: #000000;
	background-color: #CCCCCC;
}

.titrenormal {
	font-size: 10pt;
	color: #000000;
	font-weight: bold;
	margin: 4pt;
	text-align: center;
}
</style>
</head>
<body>