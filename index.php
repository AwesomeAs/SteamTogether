<?php
	
	require 'steamauth/steamauth.php';
	$online = false;
	
?><!DOCTYPE html>
<html>
	<head>
		<title>Find Steam games you share with friends</title>
		<meta property="og:site_name" content="SteamTogether"/>
		<meta property="og:title" content="SteamTogether"/>
		<meta property="og:type" content="game"/>
		<meta property="og:url" content="https://www.uppah.net/SteamTogether/"/>
		<meta property="og:description" content="Find Steam games you share with friends. Sign in via Steam to easily show your friend list, and see which platforms each game supports and how much you've played it!"/>
		<meta property="og:image" content="https://www.uppah.net/SteamTogether/thumbnails/<?php echo rand(1, 8); ?>.png"/>
		<link rel="icon" type="image/gif" href="favicon.gif"/>
		<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
		<link href="./fontawesome-free-5.0.8/web-fonts-with-css/css/fontawesome-all.min.css" rel="stylesheet">
		<link href="style.css" rel="stylesheet">
		<style>
			.top_cover {
				background-image: url('./bg/<?php echo rand(1, 8); ?>.png');
			}
			
			@media only screen and (max-width: 1020px) {
				body {
					width: calc(100% - 40px);
				}
				
				.top_cover {
					min-width: 600px;
				}
				
				body>.top_cover>.title {
					width: calc(100% - 40px);
				}
				
				.list {
					width: 100%;
				}
				
				.game_item {
					width: calc(100% - 20px);
				}
			}
			
			@media only screen and (max-width: 600px) {
				body {
					width: 500px;
					margin-top: 260px;
					margin-bottom: 30px;
				}
				
				.top_cover {
					min-width: inherit;
				}
				
				body>.top_cover>.title {
					width: calc(100% - 60px);
					padding: 5px 30px;
					font-size: 64px;
					top: 50px;
					text-align: center;
				}
				
				.steam_signout {
					float: left;
					left: 25px;
					top: 72px;
					z-index: 1;
				}
				
				.profile {
					background-color: rgba(0, 0, 0, 0.1);
					position: absolute;
					left: 0;
					width: 100%;
					top: 100px;
					padding-top: 20px;
					height: 80px;
				}
				
				.profile .friend {
					position: absolute;
					right: 22px;
					top: 10px;
					width: 76px;
					height: 76px;
				}
				
				.profile .username {
					position: absolute;
					float: right;
					font-size: 36px;
					right: 130px;
					top: 48px;
				}
				
				h1 {
					font-size: 48px;
				}
				
				.friends>.friend {
					margin-bottom: 50px;
					width: 128px;
					height: 128px;
				}
				
				.friends>.friend:after {
					top: 135px;
					width: 128px;
					font-size: 18px;
				}
				
				#profileurl {
					font-size: 24px;
					outline-width: 2px;
					width: calc(100% - 18px);
				}
				
				.button {
					zoom: 2;
					position: relative;
					left: -6px;
					padding: 6px 12px;
				}
				
				.game_item {
					height: 150px;
				}
				
				.game_item>.icon {
					width: 240px;
				}
				
				.game_item>.right {
					width: 100%;
				}
				
				.time_avg {
					left: 253px;
					top: -106px;
					font-size: 18px;
				}
				
				.compability_list {
					top: -2px;
					zoom: 1.5;
				}
				
				.game_item .title {
					position: absolute;
					top: -80px;
					left: 253px;
					font-size: 18px;
					width: calc(100% - 260px);
				}
				
				.game_item .shared_with {
					left: 2px;
					top: -3px;
				}
				
				.shared_with>.friend {
					width: 48px;
					height: 48px;
				}
				
				.free {
					right: -10px;
					top: -113px;
					width: 45px;
					height: 45px;
				}
				
				.free:before {
					border-width: 25px;
				}
				
				.free:after {
					border-width: 23px;
				}
				
				.nomobile, .playmode {
					display: none !important;
				}
				
				.top_cover .title>a {
					position: absolute;
					zoom: 1.5;
					right: 26px;
					top: 75px;
				}
				
				.invalid[data-error]:after {
					font-size: 14px;
				}
			}
		</style>
		<script src="jquery-3.3.1.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			var added = [];
			var cachedGameInfo = {};
			var lastTicket;
			var pageIndex = 0;
			var reachedEnd = false;
			
			var categoryIcons = {
				"Multi-player": "fas fa-users",
				"Cross-Platform Multiplayer": "far fa-handshake",
				"Steam Achievements": "fas fa-certificate",
				"Steam Trading Cards": "far fa-id-card",
				"Captions available": "far fa-comment",
				"Steam Workshop": "fas fa-flask",
				"In-App Purchases": "fas fa-shopping-cart",
				"Partial Controller Support": "fas fa-gamepad partial",
				"Valve Anti-Cheat enabled": "fas fa-shield-alt",
				"Stats": "fas fa-chart-bar",
				"Includes level editor": "fas fa-cubes",
				"Commentary available": "far fa-comment-alt",
				"Single-player": "fas fa-user",
				"Co-op": "fas fa-cogs",
				"Full controller support": "fas fa-gamepad",
				"Steam Cloud": "fas fa-cloud-download-alt",
				"Includes Source SDK": "fas fa-file-code",
				"SteamVR Collectibles": "fas fa-trophy",
				"Online Multi-Player": "far fa-compass",
				"Local Multi-Player": "fas fa-home",
				"Online Co-op": "far fa-lemon",
				"Local Co-op": "fas fa-user-plus",
				"Shared/Split Screen": "fas fa-columns",
				"Steam Leaderboards": "fas fa-chart-line",
				"MMO": "far fa-map",
				"VR Support": "fas fa-eye"
			};
			
			function generateUserTile(id, name, avatar, url) {
				return $('<a class="friend" id="friend_' + id + '" style="background-image:url(\'' + avatar + '\')" data-username="' + name + '" data-id="' + id + '" data-profile="' + url +
					'" title="' + name + '" onclick="addToList(this)"></a>');
			}
			
			function hasCategory(gameInfo, desc) {
				if (gameInfo == undefined || gameInfo.categories == undefined) {
					return false;
				}
				for (var i = 0; i < gameInfo.categories.length; i++) {
					if (gameInfo.categories[i].description == desc) {
						return true;
					}
				}
			}
			
			function generateGameCard(info) {
				if (cachedGameInfo[info.appid] == undefined || cachedGameInfo[info.appid].error) {
					return;
				}
				var obj = $('<a class="game_item"><img class="icon"/><div class="right"><div class="title"></div><div class="time_avg"></div><div class="shared_with"></div><div class="compability_list"></div></div></a>');
				var ginfo = cachedGameInfo[info.appid];
				obj.find(".icon").attr("src", ginfo.image);
				obj.find(".title").text(ginfo.title);
				obj.attr("href", ginfo.url);
				var list = obj.find(".compability_list");
				if (ginfo.is_free) {
					obj.find(".right").append('<div class="free" title="This game is free"></div>');
				}
				var playmodeVisible = false;
				if (hasCategory(ginfo, "Multi-player") || hasCategory(ginfo, "Cross-Platform Multiplayer") || hasCategory(ginfo, "Co-op")) {
					playmodeVisible = true;
					list.append('<i class="fas fa-users fa-2x playmode" title="Multiplayer"></i>');
				}
				if (hasCategory(ginfo, "Single-player")) {
					playmodeVisible = true;
					list.append('<i class="fas fa-user fa-2x playmode" title="Singleplayer"></i>');
				}
				if (playmodeVisible) {
					list.append('<div class="divider nomobile"></div>');
				}
				if (ginfo.controller_support == "full") {
					list.append('<i class="fas fa-gamepad fa-2x" title="Full controller support"></i>');
				} else if (hasCategory(ginfo, "Partial Controller Support")) {
					list.append('<i class="fas fa-gamepad partial fa-2x" title="Partial controller support"></i>');
				}
				if (ginfo.platforms.windows) {
					list.append('<i class="fab fa-windows fa-2x" title="Supports Windows"></i>');
				}
				if (ginfo.platforms.mac) {
					list.append('<i class="fab fa-apple fa-2x" title="Supports Mac"></i>');
				}
				if (ginfo.platforms.linux) {
					list.append('<i class="fab fa-linux fa-2x" title="Supports Linux"></i>');
				}
				if (ginfo.categories.length > 0) {
					if (list.children().last().length > 0 && !list.children().last().hasClass("divider")) {
						list.append('<div class="divider"></div>');
					}
					list.append('<a onclick="return false;" class="fas fa-caret-down openbutton fa-2x" title="See categories"><div class="buttonpopup" title></div></a>');
					for (var i = 0; i < ginfo.categories.length; i++) {
						list.find(".buttonpopup").append('<i class="' + categoryIcons[ginfo.categories[i].description] + ' fa-2x" data-text="' + ginfo.categories[i].description + '" title="' + ginfo.categories[i].description + '"></i>');
					}
					var button = list.find(".openbutton");
					button.click(function() {
						var oldOpen = button.hasClass("open");
						$(".game_item .openbutton").removeClass("open");
						if (!oldOpen) {
							button.addClass("open");
						}
					});
				}
				var total_playtime = 0;
				for (var i = 0; i < info.people.length; i++) {
					total_playtime += info.people[i].total_playtime;
					var original = $("#friend_" + info.people[i].steamid);
					var tile = original.clone();
					tile.attr("href", original.data("profile"));
					tile.attr("target", "__new");
					if (original.attr("title") == undefined) {
						tile.attr("title", "You");
					}
					tile.attr("title", tile.attr("title") + " - Played " + (Math.round((info.people[i].total_playtime / 60) * 10) / 10) + "h");
					obj.find(".shared_with").append(tile);
				}
				total_playtime = (total_playtime / info.people.length) / 60;
				obj.find(".time_avg").text("Played " + (Math.round(total_playtime * 10) / 10) + "h average");
				
				if (info.people.length == 1) {
					obj.css("opacity", 0.5);
				}
				
				return obj;
			}
			
			function sortPlayerList(container) {
				container.find(".friend").sort(function(a, b) {
					return $(a).data("username").toUpperCase().localeCompare($(b).data("username").toUpperCase());
				}).appendTo(container);
			}
			
			function displaySharedGames(sharedGames) {
				var list = $(".list");
				if (pageIndex == 0) {
					list.find(".game_item").remove();
				}
				$(".loader").addClass("hidden");
				for (var i = 0; i < sharedGames.length; i++) {
					if (cachedGameInfo[sharedGames[i].appid] != undefined && cachedGameInfo[sharedGames[i].appid].title != undefined) {
						var gameCard = generateGameCard(sharedGames[i]);
						list.append(gameCard);
					}
				}
			}
			
			function recalculateSharedGames(keepCurrent) {
				var ids = [];
				$(".added .friends .friend").each(function() {
					ids.push($(this).data("id"));
				});
				if (ids.length > 0) {
					var ticket = Math.random();
					lastTicket = ticket;
					$(".loader").removeClass("hidden");
					if (!keepCurrent) {
						$(".list").find(".game_item").remove();
						pageIndex = 0;
						reachedEnd = false;
					} else {
						pageIndex++;
						$(".list").append($(".loader"));
					}
					
					$.getJSON("FindSharedGames?ids=" + ids.join(",") + "&page=" + pageIndex + "&count=10", function(sharedGames) {
						if (lastTicket != ticket) {
							return;
						}
						var gameIds = [];
						for (var i = 0; i < sharedGames.length; i++) {
							if (cachedGameInfo[sharedGames[i].appid] == undefined) {
								gameIds.push(sharedGames[i].appid);
							}
						}
						reachedEnd = sharedGames.length < 10;
						if (gameIds.length > 0) {
							$.getJSON("GameInfo?appids=" + gameIds.join(","), function(gameInfoList) {
								for (var i = 0; i < sharedGames.length; i++) {
									if (typeof gameInfoList[sharedGames[i].appid] == "object") {
										cachedGameInfo[sharedGames[i].appid] = gameInfoList[sharedGames[i].appid];
									}
								}
								if (lastTicket != ticket) {
									return;
								}
								displaySharedGames(sharedGames);
							});
						} else {
							displaySharedGames(sharedGames);
						}
					});
				} else {
					lastTicket = undefined;
					displaySharedGames([]);
				}
			}
			
			window.onscroll = function(ev) {
				if ((window.innerHeight + window.pageYOffset ) >= document.body.offsetHeight - 300 && !reachedEnd && $(".loader").hasClass("hidden")) {
					recalculateSharedGames(true);
				}
			};
			
			function removeFromList(elem) {
				elem = $(elem);
				var tile = elem.clone();
				tile[0].onclick = function() { addToList(tile); };
				$("#" + elem.attr("id")).remove();
				$(".top .friends").append(tile);
				sortPlayerList($(".top .friends"));
				recalculateSharedGames();
			}
			
			function addToList(elem) {
				elem = $(elem);
				var tile = elem.clone();
				tile[0].onclick = function() { removeFromList(tile); };
				$("#" + elem.attr("id")).remove();
				$(".added .friends").append(tile);
				sortPlayerList($(".added .friends"));
				recalculateSharedGames();
			}
			
			var profileURLregex = new RegExp(/^https:\/\/steamcommunity\.com\/id\/[\d\w\_]{2,}$/);
			var profileURLField;
			var profileURLButton;
			function addProfileFromURL() {
				if (profileURLButton != undefined && profileURLregex.test(profileURLField.val())) {
					profileURLButton.css("display", "none");
					$.getJSON("FindUserFromProfileName?profilename=" + profileURLField.val().replace("https://steamcommunity.com/id/", ""), function(resp) {
						if (resp.success && $("#friend_" + resp.id).length == 0) {
							addToList(generateUserTile(resp.id, resp.username, resp.avatar, resp.url));
						} else if (!resp.success) {
							alert("We could not find that Steam user.");
						}
						profileURLButton.css("display", "");
					});
				}
			}
			
			$(function() {
				sortPlayerList($(".top .friends"));
				sortPlayerList($(".added .friends"));
				
				var profileURLHolder = $("#profileurlHolder");
				profileURLField = $("#profileurl");
				profileURLButton = $("#addprofile");
				var profileURLendpoint = "https://steamcommunity.com/id/";
				
				var profileURLFieldText = profileURLField.val();
				profileURLField.on("input", function(e) {
					var newVal = profileURLField.val();
					if (profileURLFieldText.length == 0 && newVal.length > 0 && !newVal.toLowerCase().startsWith(profileURLendpoint)) {
						profileURLField.val(profileURLendpoint + newVal);
						newVal = profileURLField.val();
					}
					profileURLFieldText = newVal;
					newVal = newVal.replace("//www.steamcommunity", "//steamcommunity").replace("http://steamcommunity.com/id/", profileURLendpoint);
					if (newVal != profileURLFieldText) {
						profileURLFieldText = newVal;
						profileURLField.val(newVal);
					}
					newVal = profileURLField.val();
					if (profileURLregex.test(newVal) || newVal.length == 0) {
						profileURLHolder.removeClass("invalid");
						profileURLButton.removeClass("inactive");
					} else {
						profileURLHolder.addClass("invalid");
						profileURLButton.addClass("inactive");
					}
				});
				profileURLField.trigger("input");
			});
		</script>
	</head>
	<body>
		<div class="top_cover title"><div class="title">SteamTogether<?php
			if(!isset($_SESSION['steamid'])) {
				loginbutton(); //login button
			}  else {
				include ('steamauth/userInfo.php'); //To access the $steamprofile array
				include ('steamauth/SteamConfig.php');
				
				require("SteamService.php");
				$service = new SteamService();
				$service->setApiKey($steamauth['apikey']);
				
				$friends = $service->getFriendsList($_SESSION['steamid']);
				
				logoutbutton(); //Logout Button
				
				$online = true;
			?><div class="profile"><a href="<?php echo $steamprofile['profileurl']; ?>" class="friend" id="friend_<?php echo $_SESSION['steamid']; ?>" style="background-image:url('<?php
			echo $steamprofile['avatarmedium']; ?>')" data-id="<?php echo $_SESSION['steamid']; ?>" data-profile="<?php echo $steamprofile['profileurl']; ?>" data-username="<?php
			echo $steamprofile['personaname'] ?>"></a><div class="username"><?php echo $steamprofile['personaname'] ?></div></div><?php } ?></div></div>
		<noscript>You must enable JavaScript for this website to function properly.</noscript>
		<div class="card top">
			<h1>Find people</h1>
			<div class="friends disable_select">
			<?php if ($online) { ?>
				<?php
					for ($i = 0; $i < count($friends); $i++) {
						echo "\t\t\t\t" . '<a class="friend" id="friend_' . $friends[$i]["steamid"] . '" style="background-image:url(\'' . $friends[$i]["avatarmedium"] . '\')" data-username="' .
							$friends[$i]["personaname"] . '" data-id="' . $friends[$i]["steamid"] . '" data-profile="' . $friends[$i]["profileurl"] . '" title="' . $friends[$i]["personaname"] . '" onclick="addToList(this)"></a>' . "\n";
					}
				?>
			<?php } ?>
			</div>
			<div id="profileurlHolder" maxlength="100" data-error="Invalid URL. Input should be of format 'https://steamcommunity.com/id/(name)'. Minimum 2 characters.">
				<input type="text" id="profileurl" placeholder="Enter Steam profile URL"/>
				<div class="button inactive disable_select" id="addprofile" onclick="addProfileFromURL()">Add to list</div>
			</div>
		</div>
		<div class="card added">
			<h1>Added people</h1>
			<div class="friends disable_select"></div>
		</div>
		<div class="list">
			<h1>Games in common</h1>
			<div class="loader hidden"></div>
		</div>
	</body>
</html>