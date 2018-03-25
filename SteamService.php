<?php
	ini_set("display_errors", 1);
	require("Config.php");
	
	class SteamService {
		
		private $sessionid;
		
		private $apikey;
		
		private $db = null;
		public $expireDuration = (60 * 60 * 24 * 14);
		
		public function setApiKey($key) {
			$this->apikey = $key;
		}
		
		private function doError($msg) {
			header("HTTP/1.0 500 Internal Server Error");
			die($msg);
		}
		
		public function connectDatabase() {
			if (!isset($this->db)) {
				global $dbconfig;
				$this->db = new mysqli($dbconfig["host"], $dbconfig["username"], $dbconfig["password"], $dbconfig["database"], $dbconfig["port"]);
				if ($this->db->connect_errno) {
					$this->db = null;
					$this->doError("Could not connect to database");
				}
			}
		}
		
		public function closeDatabase() {
			if ($this->db != null) {
				$this->db->close();
				$this->db = null;
			}
		}
		
		private function getRequest($url, $return = true, $timeout = 10, $followredir = false) {
			if (!function_exists("curl_init")) {
				return null;
			}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followredir);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		}
		
		private function postRequest($url, $param = array(), $return = true, $timeout = 10, $followredir = false) {
			if (!function_exists("curl_init")) {
				return null;
			}
			$ch = curl_init();
			$post = "";
			foreach ($param as $key => $val) {
				$post .= "$key=$val&";
			}
			rtrim($post, "&");
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POST, count($post));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followredir);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		}
		
		private function getRequestHeader($url, $return = true, $timeout = 10, $followredir = false) {
			if (!function_exists("curl_init")) {
				return null;
			}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followredir);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			$result = curl_exec($ch);
			
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($result, 0, $header_size);
			$body = substr($result, $header_size);
			
			curl_close($ch);
			return $header;
		}
		
		private function postRequestWithHeaders($url, $headers = array(), $param = array(), $return = true, $timeout = 10, $followredir = false) {
			if (!function_exists("curl_init")) {
				return null;
			}
			$ch = curl_init();
			$post = "";
			foreach ($param as $key => $val) {
				$post .= "$key=$val&";
			}
			rtrim($post, "&");
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, count($post));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followredir);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		}
		
		private function performApiThrottleTask($url, $tableName, $insertBindFormat, $lookupKey, $lookupValue, $validatorFunc, $expireDuration, $errorMessage) {
			$result = $this->db->query("SELECT * FROM [$tableName] WHERE [$lookupKey] = $lookupValue");
			$rowExists = false;
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$updated = strtotime($row["updated"]);
				$rowExists = true;
				if (time() - $updated < $expireDuration) {
					$data = json_decode($row["data"], true);
					return $data;
				}
			}
			$obj = json_decode($this->getRequest($url), true);
			$isValid = false;
			$obj_entry = $validatorFunc($obj, $lookupValue);
			if ($obj_entry != null) {
				$isValid = true;
				if (!($statement = $this->db->prepare($rowExists ? "UPDATE $tableName SET data=? WHERE $lookupKey=?" : "INSERT INTO $tableName ($lookupKey, data) VALUES (?, ?)"))) {
					$this->doError($errorMessage);
				}
				$int_id = (int)$lookupValue;
				$obj_data = json_encode($obj_entry);
				if ($rowExists) {
					$statement->bind_param("s" . $insertBindFormat, $obj_data, ($insertBindFormat == "i" ? $int_id : $lookupValue));
				} else {
					$statement->bind_param($insertBindFormat . "s", ($insertBindFormat == "i" ? $int_id : $lookupValue), $obj_data);
				}
				$statement->execute();
				$statement->close();
			}
			return ($isValid ? $obj_entry : $obj);
		}
		
		// {response: {game_count: int, games: [{appid: 240, playtime_forever: int}, {appid: 730, playtime_forever: int}, ...]}}
		public function getGames($id, $noconnect = false) {
			$id = isset($id) ? (int)$id : $this->userid;
			$id = max(0, $id);
			
			if (!$noconnect) {
				$this->connectDatabase();
			}
			
			$ownedgames = $this->performApiThrottleTask("https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=" . $this->apikey . "&steamid=$id&include_played_free_games=1", "Steam_OwnedGames", "s", "userid", $id,
				'getValidOwnedGames', 60 * 15, "Error logging owned games");
			
			if (!$noconnect) {
				$this->closeDatabase();
			}
			return $ownedgames;
		}
		
		public function getFriendsList($id) {
			$list = json_decode($this->getRequest("http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=" . $this->apikey . "&steamid=$id&relationship=friend"), true)["friendslist"]["friends"];
			$url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $this->apikey . "&steamids=";
			for ($i = 0; $i < min(100, count($list)); $i++) {
				$url .= $list[$i]["steamid"] . ",";
			}
			$resp = $this->getRequest($url);
			$list = json_decode($resp, true)["response"]["players"];
			if (isset($list)) {
				usort($list, sort_friends);
			} else {
				echo array("error" => $resp);
			}
			return $list;
		}
		
		public function getUser($ids) {
			return json_decode($this->getRequest("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $this->apikey . "&steamids=" . implode(",", $ids)), true)["response"]["players"];
		}
		
		public function getGameInfo($appid, $noconnect = false) {
			if (!ctype_digit($appid) || $appid < 1 || $appid > 16000000) {
				return array();
			}
			if (!$noconnect) {
				$this->connectDatabase();
			}
			if (!isset($this->db)) {
				die("Couldn't connect to database");
			}
			$obj = $this->performApiThrottleTask("http://store.steampowered.com/api/appdetails?appids=$appid", "Steam_Games", "i", "appid", $appid, 'getValidGameInfo', 60 * 60 * 24 * 14, "Error logging game info");
			
			if (!$noconnect) {
				$this->closeDatabase();
			}
			return $obj;//($isValid ? $obj[$appid]["data"] : $obj);
		}
		
		public function getUserIdFromVanityName($vanityName) {
			preg_match("/^[a-zA-Z0-9_\-]+$/", $vanityName, $vanityNameValid);
			if (isset($vanityNameValid) && count($vanityNameValid) > 0) {
				$content = file_get_contents("https://steamcommunity.com/id/$vanityName");
				preg_match("/\"steamid\":\"(\d+)\"/", $content, $steamId);
				if (isset($steamId) && count($steamId) >= 2) {
					return $steamId[1];
				}
			}
			return null;
		}
		
	}
	
	function sort_friends($a, $b) {
		return strcmp($a["personaname"], $b["personaname"]);
	}
	
	function getValidGameInfo($obj, $appid) {
		return (array_key_exists($appid, $obj) && array_key_exists("data", $obj[$appid])) ? $obj[$appid]["data"] : null;
	}
	
	function getValidOwnedGames($obj, $userid) {
		return (array_key_exists("response", $obj) && array_key_exists("game_count", $obj["response"])) ? $obj["response"] : null;
	}
?>