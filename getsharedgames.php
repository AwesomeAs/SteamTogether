<?php
	require 'steamauth/steamauth.php';
	header('Content-Type: application/json');
	
	$pageIndex = 0;
	$pageSize = 100;
	
	if (array_key_exists("count", $_GET) && is_numeric($_GET["count"])) {
		$pageSize = max(1, (int)$_GET["count"]);
	}
	
	if (array_key_exists("page", $_GET) && is_numeric($_GET["page"])) {
		$pageIndex = ((int)$_GET["page"]) * $pageSize;
	}
	
	$dataStr = array_key_exists("ids", $_GET) ? $_GET["ids"] : null;
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
	
	if(isset($_SESSION['steamid']) && !in_array($_SESSION['steamid'], $data)) {
		array_push($data, $_SESSION['steamid']);
	}
	
	include ('steamauth/SteamConfig.php');
	
	require("SteamService.php");
	$service = new SteamService();
	$service->setApiKey($steamauth['apikey']);
	
	$log = array();
	$result = array();
	
	$service->connectDatabase();
	for ($i = 0; $i < count($data); $i++) {
		$games = $service->getGames($data[$i], true);
		if (array_key_exists("games", $games)) {
			$gamesList = $games["games"];
			for ($j = 0; $j < count($gamesList); $j++) {
				$game = $gamesList[$j];
				if (!array_key_exists($game["appid"], $log)) {
					$log[$game["appid"]] = array("appid" => $game["appid"], "people" => array());
				}
				array_push($log[$game["appid"]]["people"], array("steamid" => $data[$i], "total_playtime" => $game["playtime_forever"]));
			}
		}
	}
	$service->closeDatabase();
	
	function sort_games($a, $b) {
		if (count($a["people"]) == count($b["people"])) {
			$at = 0;
			$bt = 0;
			for ($i = 0; $i < count($a["people"]); $i++) {
				$at += $a["people"][$i]["total_playtime"];
				$bt += $b["people"][$i]["total_playtime"];
			}
			if ($at == $bt) {
				return 0;
			}
			return ($at > $bt) ? -1 : 1;
		}
		return (count($a["people"]) > count($b["people"])) ? -1 : 1;
	}
	
	foreach ($log as $key => $val) {
		array_push($result, $val);
	}
	usort($result, sort_games);
	
	$result = array_slice($result, max(0, $pageIndex), min(500, $pageSize));
	
	echo json_encode($result);//json_encode($service->getFriendsList($_SESSION['steamid']));
?>