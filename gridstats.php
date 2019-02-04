<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Grid Statistics</title>
	<meta http-equiv="refresh" content="30">
	<style type="text/css">
		body		{
					}
		table 		{
					margin: auto;
					border: 1px solid #000;
					}
		tr.divide	{
					border: 1px solid #000;
					}
		table td	{
					border: 1px solid #ddd;
					 padding: 2px 4px 2px 4px;
					 }
		td.name		{
					text-align: left;
					}
		td			{
					text-align: right; 
					background: #eeeeee;
					}
		tr.divide td {
					height: 1px; padding: 0px;
					border: 1px solid #000;
					}
		td.BAD		{
					background: Salmon;
					}
		td.GOOD		{
					background: LightGreen;
					}
		td.FLAG		{
					background: Thistle;
					}
		td.BOLD		{
					background: Wheat;
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
	// See example for content

	include "db_access.php";

	// Define this for Robust access
	// This is ROBUST's private port
	// It is used to query the regions

	$robustPrivUri = "http://192.168.0.5:8003";



	// Format tables
	// You may edit this to change units, scaling, thresholds and styles

	$units = [
		[ 'name'=>'FrameTime',	'unit'=>' ms'	],
		[ 'name'=>'Memory',		'unit'=>' Mb'	],
	];

	$formats = [
		[ 'name'=>'FPS',		'fmt'=>'%3.2f',	'scale'=>1	],
		[ 'name'=>'Second',		'fmt'=>'%3.2f',	'scale'=>1	],
		[ 'name'=>'FrameTime',	'fmt'=>'%3.2f',	'scale'=>1	],
		[ 'name'=>'Dilation',	'fmt'=>'%3.2f',	'scale'=>1	],
		[ 'name'=>'Memory',		'fmt'=>'%d',	'scale'=>1e-6 ],
	];		

	$colors = [
		[ 'name'=>'TotalFrameTime',	'thresh'=> 100,	'c1'=>'BAD',	'c2'=>'GOOD'	],
		[ 'name'=>'SpareFrameTime',	'thresh'=>  50,	'c1'=>'BAD',	'c2'=>'GOOD'	],
		[ 'name'=>'AgentCount',		'thresh'=>   0,	'c1'=>'FLAG',	'c2'=>'NOCOLOR'	],
		[ 'name'=>'ActiveScripts',	'thresh'=>   0,	'c1'=>'BOLD',	'c2'=>'BOLD'	],
		[ 'name'=>'ActiveObject',	'thresh'=>   0,	'c1'=>'BOLD',	'c2'=>'BOLD'	],
		[ 'name'=>'FrameTime',		'thresh'=>   3,	'c1'=>'BAD',	'c2'=>'GOOD'	],
		[ 'name'=>'Dilation',		'thresh'=>   1,	'c1'=>'BAD',	'c2'=>'GOOD'	],
		[ 'name'=>'Pending',		'thresh'=>   0,	'c1'=>'BAD',	'c2'=>'NOCOLOR'	],
		[ 'name'=>'FPS',			'thresh'=>  10,	'c1'=>'GOOD',	'c2'=>'BAD'		],
	];


	// Main program

	$time_start = microtime(true);
	$regns = GetRegionsFromRobust($robustPrivUri);
//	$regns = GetRegionsFromDatabase($robustUrl);
	$time_end = microtime(true);
	$time_robust = 1000 * ($time_end - $time_start);

	$time_start = microtime(true);
	$stats = GetStatisticsFromSimulator($regns);
	$time_end = microtime(true);
	$time_network = 1000 * ($time_end - $time_start);

	$time_start = microtime(true);
	PrintStatisticsFull($stats);
	$time_end = microtime(true);
	$time_edition = 1000 * ($time_end - $time_start);

	PrintStatisticsAvatars($stats);


 	printf ("<br>\n");
 	printf ("Database Time: %5.2f ms<br>\n",   $time_robust);
 	printf ("Network Time: %5.2f ms<br>\n",	 $time_network);
 	printf ("Edition Time: %5.2f ms<br>\n",	 $time_edition);

 	printf ("<b>Total Time: %5.2f ms</b><br>\n",
 		$time_database + $time_network + $time_edition);


	// End


	//
	// GetStatisticsFromSimulator
	// This function query all simulators at once for statistics
	// Using curl multi, it can query 25 simulator in less than 50mS
	// See my_curl_multi in utilities for details
	//

	function GetStatisticsFromSimulator($regions) {

		$arrayOfUri = array();

		foreach ($regions as $reg) {
			$srv_host = $reg['serverIP'];
			$srv_port = $reg['serverHttpPort'];
			$reg_uuid = $reg['uuid'];
	
			$staturi = "http://$srv_host:$srv_port/monitorstats/$reg_uuid/";
			array_push ($arrayOfUri, $staturi);
		}

		$xmlArray = my_curl_multi ($arrayOfUri);

		$result = array();

		foreach ($regions as $reg) {
			$region_data = array();

			$region_data['Name'] = $reg['regionName'];
			$region_data['ServerPort'] = $reg['serverHttpPort'];
			$region_data['RegionPort'] = $reg['serverPort'];

			$region_data['Scripts list'] =
				"<a href=scripts.php?".urlencode($reg['regionName']).">list</a>";

			// Parce que les rŽsultats sont dans
			// le mme ordre que les rŽgions

			$xml = array_shift ($xmlArray);
			$dat = (array)simplexml_load_string ($xml);

			foreach ($dat as $key => $val) {
				$key = shortenKey($key);
				$region_data[$key] = $val;
			}
			array_push ($result, $region_data); 
		}

		return $result;
	}


	//
	// Formatting functions
	//

	function shortenKey($key) {
		if (preg_match ("/(.*)Monitor/", $key, $matches))
			$key =  $matches[1];
		return $key;
	}

	function formatNum($key,$val) {
		global $formats;
		foreach ($formats as $e) {
			if (preg_match("/$e[name]/", $key))
				$val = sprintf ($e[fmt], $val*$e[scale]);
		}
		return $val;
	}

	function addUnits($key,$val) {
		global $units;
		foreach ($units as $e) {
			if (preg_match("/$e[name]/", $key))
				$val = $val.$e[unit];
		}
		return $val;
	}

	function colorize($key,$val) {
		global $colors;
		foreach ($colors as $e) {
			if (preg_match("/$e[name]/", $key))
				return ($val > $e[thresh]) ? $e[c1] : $e[c2];
		}
	}

	function divider($stats) {
		$ncols = 1 + count ($stats);
		printf ("<tr class=divide><td colspan=$ncols></tr>\n");
	}

	function PrintParam($stats,$key) {
		printf ("<tr>");
		printf ("<td class=name>%s</td>", $key);
		foreach ($stats as $column) {
			$val = $column[$key];
			$col = colorize  ($key,$val);
			$val = formatNum ($key,$val);
			$val = addUnits  ($key,$val);
			printf ("<td class=$col>%s</td>", $val);
		}
		printf ("</tr>\n");
	}


	function PrintStatisticsFull($stats) {
		print "<table>\n";
		PrintParam ($stats, 'Name');
		PrintParam ($stats, 'ServerPort');
		PrintParam ($stats, 'RegionPort');
		divider    ($stats);
		PrintParam ($stats, 'TotalFrameTime');
		PrintParam ($stats, 'SpareFrameTime');
		PrintParam ($stats, 'SimulationFrameTime');
		PrintParam ($stats, 'PhysicsFrameTime');
		PrintParam ($stats, 'NetFrameTime');
		PrintParam ($stats, 'ImagesFrameTime');
		PrintParam ($stats, 'AgentFrameTime');
		divider    ($stats);
		PrintParam ($stats, 'SimFPS');
		PrintParam ($stats, 'PhysicsFPS');
		PrintParam ($stats, 'TimeDilation');
		divider    ($stats);
		PrintParam ($stats, 'AgentCount');
		PrintParam ($stats, 'ChildAgentCount');
		PrintParam ($stats, 'ObjectCount');
		PrintParam ($stats, 'ActiveObjectCount');
		PrintParam ($stats, 'ActiveScripts');
		PrintParam ($stats, 'Scripts list');
		PrintParam ($stats, 'AgentUpdatesPerSecond');
		PrintParam ($stats, 'ScriptEventsPerSecond');
		divider    ($stats);
		PrintParam ($stats, 'InPacketsPerSecond');
		PrintParam ($stats, 'OutPacketsPerSecond');
		PrintParam ($stats, 'PendingDownloads');
		PrintParam ($stats, 'PendingUploads');
		PrintParam ($stats, 'UnackedBytes');
		divider    ($stats);
		PrintParam ($stats, 'EventFrame');
		PrintParam ($stats, 'LastReportedObjectUpdates');
		PrintParam ($stats, 'SlowFrames');
		PrintParam ($stats, 'PhysicsFrame');
	#	PrintParam ($stats, 'LastFrameTime');
		PrintParam ($stats, 'LandFrame');
		PrintParam ($stats, 'PhysicsUpdateFrame');
		PrintParam ($stats, 'TotalFrame');
		divider    ($stats);
		PrintParam ($stats, 'ThreadCount');
		PrintParam ($stats, 'GCMemory');
		PrintParam ($stats, 'PWSMemory');
		print "</table>\n";
	}

	function PrintStatisticsAvatars($stats) {
		print "<br><table>\n";
		print "<caption>Avatars</caption>\n";
		foreach ($stats as $row) {
			$rname = $row['Name'];
			$navat = $row['AgentCount'];
			if ($navat > 0) {
				printf ("<tr>");
				printf ("<td class='FLAG'>$navat</td>");
				printf ("<td class='FLAG name'>$rname</td>");
				printf ("</tr>\n");
			}
		}
		print "</table></br>\n";
	}


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
	
		$query = "SELECT * FROM regions";
	
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


	//
	// Multi curl function
	// 

	function my_curl_multi ($arrayOfUri) {

		//
		// Since requests for a same host are pipelined,
		// we will adjust this timeout according to the
		// maximum number of requests for a same host.
		// Requests on different hosts are parallelized.
		// (CURLOPT_TIMEOUT is a long. Any float < 1.0
		// will result in infinite (no) timeout.
		//

		$timeout = 1.0;		// Timeout in seconds
		$throttle = 1e-3;	// Loop throttle in seconds

		//
		// Count the number of requests per host
		//

		$requests_per_host = array();

		foreach ($arrayOfUri as $uri) {
			$host = parse_url($uri, PHP_URL_HOST);
			$port = parse_url($uri, PHP_URL_PORT);
			$requests_per_host["$host:$port"]++;
		}

		$timeout *= max ($requests_per_host);

		//
		// Build array of curl handles
		//

		$curl_array = array();

		foreach ($arrayOfUri as $uri) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_URL, $uri);
			array_push ($curl_array, $ch);
		}

		//
		// Add handles to multi curl
		//

		$curl_multi = curl_multi_init();
		foreach ($curl_array as $ch) {
			curl_multi_add_handle($curl_multi, $ch);
		}

		//
		// Exec multi curl
		//

		$active = null;	do {
			$mrc = curl_multi_exec($curl_multi, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {

			// Important : do not compare to -1 as seen in many PHP examples.
			// On some installations, curl_multi_exec will never return -1
			// causing an infinite loop. As an added security, a watchdog
			// aborts the loop after twice the timeout value.

			if (curl_multi_select($curl_multi) != 0)
				do {
					$mrc = curl_multi_exec($curl_multi, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);

			usleep ($throttle*1.e6); // Be nice with CPU

			if ( ($wait += $throttle) > 2*$timeout) {
				echo "<b>curl_multi aborted after $wait seconds</b><br>"; break;
			}

		}

		//
		// Build array of answers
		//

		$answ_array = array();

		foreach ($curl_array as $ch) {
			$answer = curl_multi_getcontent($ch);
			$headsz	= curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$error	= curl_error($ch);
			$head	= substr($answer, 0, $headsz);
			$body	= substr($answer, $headsz);
			array_push ($answ_array, $body);
		}

		curl_multi_close ($curl_multi);
		return $answ_array;
	}




?>