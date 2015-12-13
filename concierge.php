<?php

	require_once '../lib/class.growl.php';


	$log = 'concierge.log';
	$msg = file_get_contents('php://input');
	file_put_contents($log, "$msg\n", FILE_APPEND);





	$xml=simplexml_load_string($msg) or die("Error: Cannot create xml");

	$region_name  = (string) $xml['region_name'];
	$region_uuid  = (string) $xml['region_uuid'];
	$timestamp    = (string) $xml['timestamp'];
	$avatars_list  = array();

	foreach ($xml as $av) {
		$avatar_name = (string) $av['name'];
		$avatar_uuid = (string) $av['uuid'];
		array_push ($avatars_list, $avatar_name);
	}

	$avatars = implode (",",$avatars_list);
	if ($avatars === "") $avatars = '<empty>';




	$connection = array('address' => '192.168.0.21', 'password' => 'tagada');
	$growl = new Growl();
	
	$growl->addNotification('Concierge');
	$growl->register($connection);

	$priority = 2; #GROWL_PRIORITY_EMERGENCY
	$sticky   = true;
	$growl->notify($connection, 'Concierge', 
			$region_name.' '.$timestamp, 
			$avatars, $priority, $sticky);

?>
