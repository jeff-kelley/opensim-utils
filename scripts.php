<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Region Scripts</title>
	<style type="text/css">
		body		{
					font-family: 'Monaco';
					font-size: 12px; 
					}
		table 		{
					margin: auto; 
					border-collapse: collapse;
					border: 1px solid black;
					}
		td			{
					border-right: 1px solid #aaa;
					padding: 2px 4px 2px 4px; 
					text-align: left;
					}
		tr.root		{
					background: #ccd;
					}
		tr.prim		{
					background: #eee;
					}
		tr.root,
		th			{
					border-top: 1pt solid black;
					}
		th 			{
					font-weight: bold;
					text-align: center;
					}
	</style>
</head>


<body>


		


<?php

	// List all scripts in a region
	// Usage : script.php?region_name
	// Jeff Kelley, 2015

	error_reporting(E_ALL ^ E_NOTICE);

	// Include this for database access
	// See exemple for content

	include "db_access.php";


	// By default, all regions share the same database.
	// If you use different dabatases for different regions,
	// you should modify this function using any of the fields
	// available in $region (serverPort, serverIP, ...)

	function DatabaseForRegion($region) {
//		$serverPort = $region['serverHttpPort'];
//		return sprintf ("sim%02d", $serverPort-9000);
		return $simBase;	// Defined in db_access.php
	}


	// Nothing to change below //


	// Connect to the database engine
	$link = new mysqli($sqlHost,$sqlUser,$sqlPass,$simBase);
	if ($link->connect_errno)
		die('Connect Error: ' . $link->connect_errno . ' (' . $link->connect_error . ')');

	// Get region name from query string
	$region = urldecode ($_SERVER['QUERY_STRING']);
	if ($region=='') die ("You must specify a region");
	$region = $link->real_escape_string($region);

	// Get region data
	$query  = "SELECT * FROM $robBase.regions WHERE regionName='$region';";
	$answer = $link->query($query);
	if (!$answer->num_rows) die ("Region $region not found\n");

	// Extract region UUID
	$region = $answer->fetch_assoc();
	$regionUuid = $region['uuid'];
	$regionDB = DatabaseForRegion ($region);

	// Get scripts inside region

	$query = "SELECT
			primitems.name AS itemName, primitems.primID,primitems.assetID,
			prims.Name AS primName, prims.LinkNumber, prims.SceneGroupID,
			prims.GroupPositionX, prims.GroupPositionY, prims.GroupPositionZ
			FROM $regionDB.primitems
			JOIN $regionDB.prims ON primitems.primID = prims.UUID
			WHERE prims.RegionUUID = '$regionUuid'
			AND   primitems.assetType=10
			ORDER BY prims.SceneGroupID, prims.LinkNumber;";

	$answer = $link->query($query);
	$prims  = $answer->fetch_all(MYSQLI_ASSOC);

	// Loop on prims

	echo "<table>\n";

	echo "<thead>\n";
	echo "<tr><th>Loc</th><th>Link</th><th>Prim</th><th>Script</th><th>UUID</th></tr>\n";
	echo "<thead>\n";

	$counter = 0;
	$currentGroup = '';

	foreach ($prims as $prim) {
		$counter++;

		$primloc = sprintf ("%3d %3d %3d", 
				$prim['GroupPositionX'],
				$prim['GroupPositionY'],
				$prim['GroupPositionZ']
				);

 		$groupID = $prim['SceneGroupID'];
		$assetID = $prim['assetID'];

		// If group has changed, find root

		if ($currentGroup != $groupID) {
			$currentGroup = $groupID;

			$query = "SELECT name,
					LinkNumber,
					SceneGroupID,
					GroupPositionX,
					GroupPositionY,
					GroupPositionZ
					FROM $regionDB.prims
					WHERE SceneGroupID = '$groupID'
					AND   LinkNumber IN (0,1);";
	
			$answer = $link->query($query);
			$root   = $answer->fetch_assoc();

			$rootloc = sprintf ("%3d %3d %3d", 
					$root['GroupPositionX'],
					$root['GroupPositionY'],
					$root['GroupPositionZ']
					);

			row ('root',
				$primloc,
				$root['LinkNumber'],
				$root['name'],
				'',
				'');
		}

		if ($primloc != $rootloc)
			echo "Internal error, primloc != rootloc<br>";
	
		row ('prim',
			'',
			$prim['LinkNumber'],
			$prim['primName'],
			$prim['itemName'],
			"<a href=getasset.php?$assetID>$assetID</a>");
	}

	echo "<tfoot>\n";
	echo "<tr><th colspan=5>Total scripts : $counter</th></tr>\n";
	echo "</tfoot>\n";

	echo "</table>\n";


	function row($class) {
		echo "<tr class=$class>";
	 	$nargs = func_num_args();
	 	for ($i=1; $i<$nargs; $i++) {
		 	$arg = func_get_arg($i);
		 	echo "<td>$arg</td>";
		 }
		echo "</tr>\n";
 	}

?>