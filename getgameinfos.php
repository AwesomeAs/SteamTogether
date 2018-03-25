<?php
	require 'steamauth/steamauth.php';
	header('Content-Type: application/json');
	
	$dataStr = array_key_exists("appids", $_GET) ? $_GET["appids"] : null;
	if (!isset($dataStr) || !is_string($dataStr) || strlen($dataStr) == 0) {
		header("HTTP/1.0 400 Bad Request");
		echo json_encode(array("error" => true, "code" => 400, "message" => "No data submitted."));
		return;
	}
	
	$data = explode(",", $dataStr);
	for ($i = 0; $i < count($data); $i++) {
		if (!ctype_digit($data[$i])) {
			header("HTTP/1.0 400 Bad Request");
			echo json_encode(array("error" => true, "code" => 400, "message" => "Invalid id '" . $data[$i] . "'."));
			return;
		}
	}
	
	include ('steamauth/SteamConfig.php');
	
	require("SteamService.php");
	$service = new SteamService();
	$service->setApiKey($steamauth['apikey']);
	
	$result = array();
	
	$service->connectDatabase();
	for ($i = 0; $i < count($data); $i++) {
		$game = $service->getGameInfo($data[$i], true);
		if (array_key_exists("name", $game)) {
			if ($game["type"] == "game") {
				$result[$data[$i]] = array(
					"title" => $game["name"],
					"is_free" => $game["is_free"],
					"controller_support" => $game["controller_support"],
					"image" => "http://cdn.akamai.steamstatic.com/steam/apps/" . $data[$i] . "/capsule_231x87.jpg",
					"platforms" => $game["platforms"],
					"categories" => $game["categories"],
					"url" => "http://store.steampowered.com/app/" . $data[$i] . "/"
				);
			}
		} else {
			$result[$data[$i]] = array("error" => true, "data" => $game);
		}
	}
	$service->closeDatabase();
	
	echo json_encode($result);
?>