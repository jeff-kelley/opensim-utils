<?php
	error_reporting(E_ALL);
	include "db_access.php";

	// Appel : gettexture?uuid
	//
	// A folder named 'tmp' should exist 
	// and be writable by the web server

	// 
	// For some reason, imagick has lost J2K support in 
	// Debian/Ubuntu. An alternative is to use gmagick.
	//

	$usingGmagick = TRUE;

	// Test if a graphic library is installed
	$imagickInstalled = extension_loaded ('imagick');
	$gmagickInstalled = extension_loaded ('gmagick');

	if (! ($imagickInstalled || $gmagickInstalled) )
		die ('ImageMagick or GraphicsMagick not installed');
	

	// Get uuid from query string
	if (isset($_SERVER['QUERY_STRING']))
		$uuid = $_SERVER['QUERY_STRING'];
	else
		$uuid = "00000000-0000-2222-3333-100000001001";

	$hex = '[0-9,a-f]';
	$uid = "$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}";
	
	$match = preg_match ("/$uid/", $uuid);
	if (!$match) die ($uuid.' is not an UUID');

	// Does the file exist in cache?

	if (file_exists("tmp/$uuid.jpg"))
		goto success;

	$query = "SELECT data FROM assets WHERE id='$uuid'";

	$link = new mysqli($sqlHost,$sqlUser,$sqlPass,$robBase);
	if ($link->connect_errno)
	    die('Connect Error: ' . $link->connect_errno);

	$answ = $link->query($query);
	if ($link->errno)
		die('Select Error: ' . $link->errno);

	$row = $answ->fetch_row();
	$dat = $row[0]; // Blob
	
	if ($usingGmagick) {

		// Convert with gmagick

		try {
			$im = $image = new Gmagick();
			$im->readImageBlob ($dat);
			$im->writeImage("tmp/$uuid.jpg");
		} catch (Exception $e) {
			die($e->getMessage());
		}

	} else {

		// Convert with imagick
	
	 	try {
	 		$im = $image = new Imagick();
	 		$im->readImageBlob ($dat);
	 		$im->writeImage("tmp/$uuid.jpg");
	 	} catch (Exception $e) {
	     	die($e->getMessage());
	 	}

	}




success:


	echo "tmp/$uuid.jpg";

	// Scan temp folder

	$cache = glob("tmp/*");
	usort($cache, create_function
	('$a,$b', 'return fileatime($b) - fileatime($a);'));

	// Keep N more recents

	$cache = array_slice($cache,100);
	array_map ('unlink', $cache);

?>