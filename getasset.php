<?php
	error_reporting(E_ALL);
	include "db_access.php";

	// Get a raw asset by UUID
	// Usage : getasset?uuid
	// Jeff Kelley, 2015


	// Get uuid from query string
	$uuid = $_SERVER['QUERY_STRING'];

	$hex = '[0-9,a-f]';
	$uid = "$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}";
	
	$match = preg_match ("/$uid/", $uuid);
	if (!$match) die ($uuid.' is not an UUID');

	// Connect to the database engine
	$link = new mysqli($sqlHost,$sqlUser,$sqlPass,$simBase);
	if ($link->connect_errno)
	    die('Connect Error: ' . $link->connect_errno);

	// Get asset data
	$query  = "SELECT data FROM $robBase.assets WHERE id='$uuid'";
	$answer = $link->query($query);
	if (!$answer->num_rows) die ("Asset $uuid not found\n");

	$asset = $answer->fetch_assoc();
	$data  = $asset['data'];

	echo '<XMP>';
	echo $data;

?>