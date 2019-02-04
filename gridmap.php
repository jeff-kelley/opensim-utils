<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Carte de la grille</title>
	<style type="text/css">
		body		{
					background : #5B6970;
					font-family: sans-serif;
					color: white;
					}
		table		{
					border-collapse: collapse; 
					table-layout: fixed;
					margin : auto; 
					}
		td			{
					border: #999 thin solid;
					margin : 0; padding : 0;
					text-align: center;
					line-height:0; /* fix vertical expansion */
					}
		td.region	{
					width: 80px;
					height: 80px;
					background-color: #1e465f;
					}
		td.region div	{
					position: relative;
					}
		td.region img	{
					width: 100%; height: 100%;
					}
		td.region p {
					position: absolute;
					top: 20px;
					left: 0px; 
					width: 100%;
					line-height: 100%;
					font-weight: bold;
					font-size: 80%;
					color: #0f0;
					}
		td.vheader	{
					height: 30px;
					color: white;
					font-size: 80%;
					background-color: Maroon;

					}
		td.hheader	{
					width: 30px;
					color: white;
					font-size: 80%;
					background-color: Maroon;
					}
		td.hheader div	{
					transform: rotate(-90deg);
					-ms-transform: rotate(-90deg);
					-moz-transform: rotate(-90deg);
					-webkit-transform: rotate(-90deg);
					}
	</style>
</head>


<body>





<?php
	error_reporting(E_ALL ^ E_NOTICE);

	//
	// We offer two ways to query region data : Robust and database
	// Choose depending on your configuration and security issues
	// Quering ROBUST has shown to be faster than the database
	//

	// Include this for database access
	// See exemple for content

	include "db_access.php";

	// Define this for Robust access
	// This is ROBUST's private port
	// It is used to query the regions

	$robustPrivUri = "http://192.168.0.5:8003";

	// Define this in any case
	// This is ROBUST's public port
	// It is used to link the maptiles

	$robustPublUri = "http://grid.pescadoo.net:8002";

	// Choose one of both methods

	$regions = GetRegionsFromRobust($robustPrivUri);
//	$regions = GetRegionsFromDatabase();

	// No configuration needed beyound this point




	//
	// Build array regionsName[location]
	//

	$regionName = array();

	$xmin = 65536; $xmax = 0;
	$ymin = 65536; $ymax = 0;

	foreach ($regions as $reg) {
		$locx = $reg['locX'] / 256;
		$locy = $reg['locY'] / 256;

		$loc = "$locx-$locy";
		if ($locx < $xmin) $xmin = $locx;
		if ($locy < $ymin) $ymin = $locy;

		if ($locx > $xmax) $xmax = $locx;
		if ($locy > $ymax) $ymax = $locy;

		$regionName[$loc] = $reg['regionName'];
	}

	//
	// Draw the map
	//

	echo "<table>\n";

	// Horizontal header
	echo "<tr>\n";
	echo "<td class=vheader></td>\n";
	for ($locx=$xmin; $locx<=$xmax; $locx++)
		echo "<td class=vheader><div>$locx</div></td>\n";
	echo "</tr>\n";

	for ($locy=$ymax; $locy>=$ymin; $locy--) {
		echo "<tr>\n";
		echo "<td class=hheader><div>$locy</div></td>\n";
		for ($locx=$xmin; $locx<=$xmax; $locx++) {
			$loc = "$locx-$locy";
			$html = '';

			if (isset($regionName[$loc])) {
				$maptileUri = $robustPublUri."/map-1-".$loc."-objects.jpg";
				$nam = $regionName[$loc];
				$html = "<div><img src=$maptileUri><p>$nam</p></div>";
			}

			echo "<td class=region>$html</td>\n";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";



	///////////////////////////////////////////////////////////
	//
	//
	// 		Utilities
	//
	//
	///////////////////////////////////////////////////////////


	//
	//	Getting region data with a database query
	//	Define mysql credentials in db_access.php
	//
	//	We use php MySQL native driver
	//	(apt-get install php5-mysqlnd)
	//

	function GetRegionsFromDatabase() {
		global $sqlUser, $sqlPass, $sqlHost, $simBase, $robBase;

		$query  = "SELECT * FROM regions";

		$link = new mysqli($sqlHost,$sqlUser,$sqlPass,$robBase);
		if ($link->connect_errno)
			die('Connect Error: ' . $link->connect_errno . ' (' . $link->connect_error . ')');

		$answ = $link->query($query);
		if ($link->errno)
			die('Select Error: ' . $link->errno . '(' . $link->error . ')');

		$result = $answ->fetch_all(MYSQLI_ASSOC);

		$link->close();
		return $result;
	}


	//
	//	Getting region data with a ROBUST query
	//


	function GetRegionsFromRobust($gridUri) {
		$service = "$gridUri/grid";
		$xml_elt = GetRegionRange ($service,0,0,2147483647,2147483647);
		$xml_arr = json_decode(json_encode((array)$xml_elt), TRUE);
		usort ($xml_arr, "sortOrder");
		return $xml_arr;
	}

	function sortOrder($a, $b) {
 		$serverPortDiff = $a['serverHttpPort'] - $b['serverHttpPort'];
		$regionPortDiff = $a['serverPort'] - $b['serverPort'];
		if ($serverPortDiff) return $serverPortDiff; 
    	else return $regionPortDiff;
	}


	//
	// ROBUST grid service query
	// Subset of justincc opensimulator tools to make this code stand-alone
	// https://github.com/justincc/opensimulator-tools/tree/master/integration/php
	//

	function PostToService($uri, $postFields, $debug = FALSE)
	{
		if ($debug)
			echo "postFields:$postFields\n";
		
		$ch = curl_init($uri);
		
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		
		if ($debug)
			PrintReturnDebugInfo($result, $info);   
	
		if (!empty($result)) // JYB 06-FEB-2015
			return new SimpleXmlElement($result);    
	}

	function GetRegionRange($serviceUri, $minX, $minY, $maxX, $maxY, $debug = FALSE)
	{
		$params 
			= array(
				'SCOPEID' => UUID_ZERO,
				'XMIN' => $minX,
				'YMIN' => $minY,
				'XMAX' => $maxX,
				'YMAX' => $maxY,
				'METHOD' => "get_region_range");
				
		return PostToService($serviceUri, http_build_query($params), $debug);    
	}

?>