<?php
	require 'steamauth/steamauth.php';
	header('Content-Type: application/json');
	
	$vanityName = array_key_exists("profilename", $_GET) ? $_GET["profilename"] : null;
	if (!isset($vanityName) || !is_string($vanityName) || strlen($vanityName) <= 1) {
		header("HTTP/1.0 400 Bad Request");
		echo json_encode(array("success" => false, "error" => true, "code" => 400, "message" => "No data submitted."));
		return;
	} else if (strlen($vanityName) > 150) {
		echo json_encode(array("success" => false));
		return;
	}
	
	include ('steamauth/SteamConfig.php');
	
	require("SteamService.php");
	$service = new SteamService();
	$service->setApiKey($steamauth['apikey']);
	
	$id = $service->getUserIdFromVanityName($vanityName);
	
	if (isset($id)) {
		$info = $service->getUser(array($id));
		if (count($info) > 0) {
			echo json_encode(array("success" => true, "id" => $info[0]["steamid"], "avatar" => $info[0]["avatarmedium"], "username" => $info[0]["personaname"], "url" => $info[0]["profileurl"]));
			return;
		}
	}
	echo json_encode(array("success" => false));
?>