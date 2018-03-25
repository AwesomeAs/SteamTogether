<?php
	require 'steamauth/steamauth.php';
	header('Content-Type: application/json');
	if(!isset($_SESSION['steamid'])) {
		header("HTTP/1.0 401 Unauthorized");
		echo json_encode(array("error" => true, "message" => "You are not signed in on Steam."));
	} else {
		include ('steamauth/SteamConfig.php');
		
		require("SteamService.php");
		$service = new SteamService();
		$service->setApiKey($steamauth['apikey']);
		
		echo json_encode($service->getFriendsList($_SESSION['steamid']));
	}
?>