<?php
include_once __DIR__ . "/ip_in_range.php";
class mainLib {
	public function getAudioTrack($id) {
		$songs = ["Stereo Madness by ForeverBound",
			"Back on Track by DJVI",
			"Polargeist by Step",
			"Dry Out by DJVI",
			"Base after Base by DJVI",
			"Can't Let Go by DJVI",
			"Jumper by Waterflame",
			"Time Machine by Waterflame",
			"Cycles by DJVI",
			"xStep by DJVI",
			"Clutterfunk by Waterflame",
			"Theory of Everything by DJ Nate",
			"Electroman Adventures by Waterflame",
			"Club Step by DJ Nate",
			"Electrodynamix by DJ Nate",
			"Hexagon Force by Waterflame"];
		if($id < 0 || $id >= count($songs))
			return "Unknown by DJVI";
		return $songs[$id];
	}
	public function getDifficulty($diff,$auto,$demon) {
		if($auto != 0){
			return "Auto";
		}else if($demon != 0){
			return "Demon";
		}else{
			switch($diff){
				case 0:
					return "N/A";
					break;
				case 10:
					return "Easy";
					break;
				case 20:
					return "Normal";
					break;
				case 30:
					return "Hard";
					break;
				case 40:
					return "Harder";
					break;
				case 50:
					return "Insane";
					break;
				default:
					return "Unknown";
					break;
			}
		}
	}
	public function getDiffFromStars($stars) {
		$auto = 0;
		$demon = 0;
		switch($stars){
			case 1:
				$diffname = "Auto";
				$diff = 50;
				$auto = 1;
				break;
			case 2:
				$diffname = "Easy";
				$diff = 10;
				break;
			case 3:
				$diffname = "Normal";
				$diff = 20;
				break;
			case 4:
			case 5:
				$diffname = "Hard";
				$diff = 30;
				break;
			case 6:
			case 7:
				$diffname = "Harder";
				$diff = 40;
				break;
			case 8:
			case 9:
				$diffname = "Insane";
				$diff = 50;
				break;
			case 10:
				$diffname = "Demon";
				$diff = 50;
				$demon = 1;
				break;
			default:
				$diffname = "N/A: " . $stars;
				$diff = 0;
				$demon = 0;
				break;
		}
		return array('diff' => $diff, 'auto' => $auto, 'demon' => $demon, 'name' => $diffname);
	}
	public function getLength($length) {
		switch($length){
			case 0:
				return "Tiny";
				break;
			case 1:
				return "Short";
				break;
			case 2:
				return "Medium";
				break;
			case 3:
				return "Long";
				break;
			case 4:
				return "XL";
				break;
			case 5:
				return "Platformer";
				break;
			default:
				return "Unknown";
				break;
		}
	}
	public function getGameVersion($version) {
		if($version > 17){
			return $version / 10;
		}elseif($version == 11){
			return "1.8";
		}elseif($version == 10){
			return "1.7";
		}else{
			$version--;
			return "1.$version";
		}
	}
	public function getDemonDiff($dmn) {
		switch($dmn){
			case 3:
				return "Easy";
				break;
			case 4:
				return "Medium";
				break;
			case 5:
				return "Insane";
				break;
			case 6:
				return "Extreme";
				break;
			default:
				return "Hard";
				break;
		}
	}
	public function getDiffFromName($name) {
		$name = strtolower($name);
		$starAuto = 0;
		$starDemon = 0;
		switch ($name) {
			default:
				$starDifficulty = 0;
				break;
			case "easy":
				$starDifficulty = 10;
				break;
			case "normal":
				$starDifficulty = 20;
				break;
			case "hard":
				$starDifficulty = 30;
				break;
			case "harder":
				$starDifficulty = 40;
				break;
			case "insane":
				$starDifficulty = 50;
				break;
			case "auto":
				$starDifficulty = 50;
				$starAuto = 1;
				break;
			case "demon":
				$starDifficulty = 50;
				$starDemon = 1;
				break;
		}
		return array($starDifficulty, $starDemon, $starAuto);
	}
	public function makeTime($time) {
		// Getting the time since the given unix timestamp
		$time = time() - $time;
		$time = ($time < 1) ? 1 : $time;
		$tokens = array (31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second');
		foreach ($tokens as $unit => $text) {
			if ($time < $unit) {
				continue;
			}
			$numberOfUnits = floor($time / $unit);
			return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
		}
	}
	public function getIDFromPost(){
		include __DIR__ . "/../../config/security.php";
		include_once __DIR__ . "/exploitPatch.php";
		include_once __DIR__ . "/GJPCheck.php";

		if(!empty($_POST["udid"]) AND $_POST['gameVersion'] < 20 AND $unregisteredSubmissions) 
		{
			$id = ExploitPatch::remove($_POST["udid"]);
			if(is_numeric($id)) exit("-1");
		}
		elseif(!empty($_POST["accountID"]) AND $_POST["accountID"]!="0")
		{
			$id = GJPCheck::getAccountIDOrDie();
		}
		else
		{
			exit("-1");
		}
		return $id;
	}
	public function getUserID($extID, $userName = "Player") {
		include __DIR__ . "/connection.php";
		if (is_numeric($extID)) {
			$register = 1;
			$userName = $this->getAccountName($extID);
		} else {
			$register = 0;
		}
		
		$query = $db->prepare("SELECT userID FROM users WHERE extID LIKE BINARY :id");
		$query->execute([':id' => $extID]);
		if ($query->rowCount() > 0) {
			$userID = $query->fetchColumn();
		} else {
			$query = $db->prepare("INSERT INTO users (isRegistered, extID, userName, lastPlayed) VALUES (:register, :id, :userName, :uploadDate)");
			$query->execute([':register' => $register, ':id' => $extID, ':userName' => $userName, ':uploadDate' => time()]);
			$userID = $db->lastInsertId();
		}
		return $userID;
	}
	public function getAccountName($accountID) {
		if (!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT userName FROM accounts WHERE accountID = :id");
		$query->execute([':id' => $accountID]);
		if ($query->rowCount() > 0) return $query->fetchColumn();
		return false;
	}
	public function getUserName($userID) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT userName FROM users WHERE userID = :id");
		$query->execute([':id' => $userID]);
		if ($query->rowCount() > 0) {
			$userName = $query->fetchColumn();
		} else {
			$userName = false;
		}
		return $userName;
	}
	public function getAccountIDFromName($userName) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :usr");
		$query->execute([':usr' => $userName]);
		if ($query->rowCount() > 0) {
			return $query->fetchColumn();
		}
		return 0;
	}
	public function getExtID($userID) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT extID FROM users WHERE userID = :id");
		$query->execute([':id' => $userID]);
		if ($query->rowCount() > 0) {
			return $query->fetchColumn();
		} else {
			return 0;
		}
	}
	public function getLegacyAccountID($UDID = 0) {
		if (!$UDID) {
			if (empty($_POST['udid']) OR is_numeric($_POST['udid'])) return 0;
			else {
				require_once __DIR__ . "/exploitPatch.php";
				$UDID = ExploitPatch::remove($_POST['udid']);
			}
		}
		
		require __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT accountID FROM userLinks WHERE extID = :udid");
		$query->execute([':udid' => $UDID]);
		if ($query->rowCount() == 0) return 0;
		return $query->fetchColumn();
	}
	public function getLegacyAccountIDFromDiscord($discordID) {
		require __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT accountID FROM userLinks WHERE discordID = :discordID");
		$query->execute([':discordID' => $discordID]);
		if ($query->rowCount() == 0) return 0;
		return $query->fetchColumn();
	}
	public function getLegacyExtID($accountID) {
		require __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT extID FROM userLinks WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		if ($query->rowCount() == 0) return 0;
		return $query->fetchColumn();
	}
	public function getLegacyDiscordID($UDID = 0) {
		require_once __DIR__ . "/exploitPatch.php";

		if (!$UDID) {
			if (empty($_POST['udid'])) return 0;
			else $UDID = ExploitPatch::remove($_POST['udid']);
		}

		require __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT discordID FROM userLinks WHERE extID = :udid");
		$query->execute([':udid' => $UDID]);
		if ($query->rowCount() == 0) return 0;
		return $query->fetchColumn();
	}
	public function getLegacyDiscordIDFromAcc($accountID) {
		require __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT discordID FROM userLinks WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		if ($query->rowCount() == 0) return 0;
		return $query->fetchColumn();
	}
	public function getUserString($userdata) {
		include __DIR__ . "/connection.php";
		/*$query = $db->prepare("SELECT userName, extID FROM users WHERE userID = :id");
		$query->execute([':id' => $userID]);
		$userdata = $query->fetch();*/
		$extID = is_numeric($userdata['extID']) ? $userdata['extID'] : 0;
		return "${userdata['userID']}:${userdata['userName']}:${extID}";
	}
	public function getSongString($song){
		include __DIR__ . "/connection.php";
		/*$query3=$db->prepare("SELECT ID,name,authorID,authorName,size,isDisabled,download FROM songs WHERE ID = :songid LIMIT 1");
		$query3->execute([':songid' => $songID]);*/
		if($song['ID'] == 0 || empty($song['ID'])){
			return false;
		}
		//$song = $query3->fetch();
		if($song["isDisabled"] == 1){
			return false;
		}
		$dl = $song["download"];
		if(strpos($dl, ':') !== false){
			$dl = urlencode($dl);
		}
		return "1~|~".$song["ID"]."~|~2~|~".str_replace("#", "", $song["name"])."~|~3~|~".$song["authorID"]."~|~4~|~".$song["authorName"]."~|~5~|~".$song["size"]."~|~6~|~~|~10~|~".$dl."~|~7~|~~|~8~|~1";
	}
	public function sendDiscordPM($discordID, $message) {
		require __DIR__ . "/../../config/discord.php";
		if (!$discordEnabled) {
			return false;
		}
		// finding the channel id
		$data = array("recipient_id" => $discordID);                                                                    
		$data_string = json_encode($data);
		$url = "https://discord.com/api/v10/users/@me/channels";
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = '1.8 GDPS (https://github.com/Stazzical/1.8-GDPS/, 1.0)';
		curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($crl, CURLOPT_POSTFIELDS, $data_string);
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Accept: application/json';
		$headr[] = 'Authorization: Bot ' . $bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		$responseDecode = json_decode($response, true);
		$channelID = $responseDecode["id"];

		// sending the msg
		$data_string = json_encode($message);
		$url = "https://discord.com/api/v10/channels/" . $channelID . "/messages";
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = '1.8 GDPS (https://github.com/Stazzical/1.8-GDPS/, 1.0)';
		curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($crl, CURLOPT_POSTFIELDS, $data_string);
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Accept: application/json';
		$headr[] = 'Authorization: Bot ' . $bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		return $response;
	}
	public function getDiscordUsername($discordID) {
		require __DIR__ . "/../../config/discord.php";
		// getting discord acc info
		$url = "https://discord.com/api/v10/users/" . $discordID;
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = '1.8 GDPS (https://github.com/Stazzical/1.8-GDPS/, 1.0)';
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Accept: application/json';
		$headr[] = 'Authorization: Bot ' . $bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		$userinfo = json_decode($response, true);
		return $userinfo["username"];
	}
	public function getDiscordIDByName($username) {
		require __DIR__ . "/../../config/discord.php";
		require __DIR__ . "/../../config/linking.php";
		if (!$discordEnabled OR !$gdpsGuildID) {
			return false;
		}
		// your bot needs to be inside the $gdpsGuildID for this to work
		// searching the users inside the guild to find a match
		$url = "https://discord.com/api/v10/guilds/" . $gdpsGuildID . "/members/search?limit=10&query=" . $username;
		$crl = curl_init($url);
		$headr = array();
		$headr['User-Agent'] = '1.8 GDPS (https://github.com/Stazzical/1.8-GDPS/, 1.0)';
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Accept: application/json';
		$headr[] = 'Authorization: Bot ' . $bottoken;
		curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$response = curl_exec($crl);
		curl_close($crl);
		$result = json_decode($response, true);
		foreach ($result as $user) {
			if ($user["user"]["username"] == $username) {
				return $user["user"]["id"];
			}
		}
		return 0;
	}
	public function getAccountsWithPermission($permission){
		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT roleID FROM roles WHERE $permission = 1 ORDER BY priority DESC");
		$query->execute();
		$result = $query->fetchAll();
		$accountlist = array();
		foreach($result as &$role){
			$query = $db->prepare("SELECT accountID FROM roleassign WHERE roleID = :roleID");
			$query->execute([':roleID' => $role["roleID"]]);
			$accounts = $query->fetchAll();
			foreach($accounts as &$user){
				$accountlist[] = $user["accountID"];
			}
		}
		return $accountlist;
	}
	public function checkPermission($accountID, $permission){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		//isAdmin check
		$query = $db->prepare("SELECT isAdmin FROM accounts WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$isAdmin = $query->fetchColumn();
		if($isAdmin == 1){
			return 1;
		}
		
		$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$roleIDarray = $query->fetchAll();
		$roleIDlist = "";
		foreach($roleIDarray as &$roleIDobject){
			$roleIDlist .= $roleIDobject["roleID"] . ",";
		}
		$roleIDlist = substr($roleIDlist, 0, -1);
		if($roleIDlist != ""){
			$query = $db->prepare("SELECT $permission FROM roles WHERE roleID IN ($roleIDlist) ORDER BY priority DESC");
			$query->execute();
			$roles = $query->fetchAll();
			foreach($roles as &$role){
				if($role[$permission] == 1){
					return true;
				}
				if($role[$permission] == 2){
					return false;
				}
			}
		}
		$query = $db->prepare("SELECT $permission FROM roles WHERE isDefault = 1");
		$query->execute();
		$permState = $query->fetchColumn();
		if($permState == 1){
			return true;
		}
		if($permState == 2){
			return false;
		}
		return false;
	}
	public function isCloudFlareIP($ip) {
    	$cf_ips = array(
	        '173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22'
	    );
	    foreach ($cf_ips as $cf_ip) {
	        if (ipInRange::ipv4_in_range($ip, $cf_ip)) {
	            return true;
	        }
	    }
	    return false;
	}
	public function getIP(){
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isCloudFlareIP($_SERVER['REMOTE_ADDR'])) //CLOUDFLARE REVERSE PROXY SUPPORT
  			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ipInRange::ipv4_in_range($_SERVER['REMOTE_ADDR'], '127.0.0.0/8')) //LOCALHOST REVERSE PROXY SUPPORT (7m.pl)
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		return $_SERVER['REMOTE_ADDR'];
	}
	public function checkModIPPermission($permission){
		include __DIR__ . "/connection.php";
		$ip = $this->getIP();
		$query=$db->prepare("SELECT modipCategory FROM modips WHERE IP = :ip");
		$query->execute([':ip' => $ip]);
		$categoryID = $query->fetchColumn();
		
		$query=$db->prepare("SELECT $permission FROM modipperms WHERE categoryID = :id");
		$query->execute([':id' => $categoryID]);
		$permState = $query->fetchColumn();
		
		if($permState == 1){
			return true;
		}
		if($permState == 2){
			return false;
		}
		return false;
	}
	public function getFriends($accountID){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$friendsarray = array();
		$query = "SELECT person1,person2 FROM friendships WHERE person1 = :accountID OR person2 = :accountID"; //selecting friendships
		$query = $db->prepare($query);
		$query->execute([':accountID' => $accountID]);
		$result = $query->fetchAll();//getting friends
		if($query->rowCount() == 0){
			return array();
		}
		else
		{//oh so you actually have some friends kden
			foreach ($result as &$friendship) {
				$person = $friendship["person1"];
				if($friendship["person1"] == $accountID){
					$person = $friendship["person2"];
				}
				$friendsarray[] = $person;
			}
		}
		return $friendsarray;
	}
	public function isFriends($accountID, $targetAccountID) {
		if(!is_numeric($accountID) || !is_numeric($targetAccountID)) return false;

		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT count(*) FROM friendships WHERE person1 = :accountID AND person2 = :targetAccountID OR person1 = :targetAccountID AND person2 = :accountID");
		$query->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		return $query->fetchColumn() > 0;
	}
	public function getMaxValuePermission($accountID, $permission){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$maxvalue = 0;
		$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$roleIDarray = $query->fetchAll();
		$roleIDlist = "";
		foreach($roleIDarray as &$roleIDobject){
			$roleIDlist .= $roleIDobject["roleID"] . ",";
		}
		$roleIDlist = substr($roleIDlist, 0, -1);
		if($roleIDlist != ""){
			$query = $db->prepare("SELECT $permission FROM roles WHERE roleID IN ($roleIDlist) ORDER BY priority DESC");
			$query->execute();
			$roles = $query->fetchAll();
			foreach($roles as &$role){ 
				if($role[$permission] > $maxvalue){
					$maxvalue = $role[$permission];
				}
			}
		}
		return $maxvalue;
	}
	public function getAccountCommentColor($accountID){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$roleIDarray = $query->fetchAll();
		$roleIDlist = "";
		foreach($roleIDarray as &$roleIDobject){
			$roleIDlist .= $roleIDobject["roleID"] . ",";
		}
		$roleIDlist = substr($roleIDlist, 0, -1);
		if($roleIDlist != ""){
			$query = $db->prepare("SELECT commentColor FROM roles WHERE roleID IN ($roleIDlist) ORDER BY priority DESC");
			$query->execute();
			$roles = $query->fetchAll();
			foreach($roles as &$role){
				if($role["commentColor"] != "000,000,000"){
					return $role["commentColor"];
				}
			}
		}
		$query = $db->prepare("SELECT commentColor FROM roles WHERE isDefault = 1");
		$query->execute();
		if($query->rowCount() > 0)
			return $query->fetchColumn();
		return "255,255,255";
	}
	public function rateLevel($accountID, $levelID, $stars, $difficulty, $auto, $demon){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		//lets assume the perms check is done properly before
		$query = "UPDATE levels SET starDemon=:demon, starAuto=:auto, starDifficulty=:diff, starStars=:stars, rateDate=:now WHERE levelID=:levelID";
		$query = $db->prepare($query);	
		$query->execute([':demon' => $demon, ':auto' => $auto, ':diff' => $difficulty, ':stars' => $stars, ':levelID'=>$levelID, ':now' => time()]);
		
		$query = $db->prepare("INSERT INTO modactions (type, value, value2, value3, timestamp, account) VALUES ('1', :value, :value2, :levelID, :timestamp, :id)");
		$query->execute([':value' => $this->getDiffFromStars($stars)["name"], ':timestamp' => time(), ':id' => $accountID, ':value2' => $stars, ':levelID' => $levelID]);
	}
	public function featureLevel($accountID, $levelID, $state) {
		if(!is_numeric($accountID)) return false;
		switch($state) {
			case 0:
				$feature = 0;
				$epic = 0;
				break;
			case 1:
				$feature = 1;
				$epic = 0;
				break;
			case 2: // Stole from TheJulfor
				$feature = 1;
				$epic = 1;
				break;
			case 3:
				$feature = 1;
				$epic = 2;
				break;
			case 4:
				$feature = 1;
				$epic = 3;
				break;
		}
		include __DIR__ . "/connection.php";
		$query = "UPDATE levels SET starFeatured=:feature, starEpic=:epic, rateDate=:now WHERE levelID=:levelID";
		$query = $db->prepare($query);
		$query->execute([':feature' => $feature, ':epic' => $epic, ':levelID' => $levelID, ':now' => time()]);
		$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('2', :value, :levelID, :timestamp, :id)");
		$query->execute([':value' => $state, ':timestamp' => time(), ':id' => $accountID, ':levelID' => $levelID]);
	}
	public function verifyCoinsLevel($accountID, $levelID, $coins){
		if(!is_numeric($accountID)) return false;

		include __DIR__ . "/connection.php";
		$query = "UPDATE levels SET starCoins=:coins WHERE levelID=:levelID";
		$query = $db->prepare($query);	
		$query->execute([':coins' => $coins, ':levelID'=>$levelID]);
		
		$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('3', :value, :levelID, :timestamp, :id)");
		$query->execute([':value' => $coins, ':timestamp' => time(), ':id' => $accountID, ':levelID' => $levelID]);
	}
	public function songReupload($url){
		require __DIR__ . "/connection.php";
		require_once __DIR__ . "/exploitPatch.php";
		$song = str_replace("www.dropbox.com","dl.dropboxusercontent.com",$url);
		if (filter_var($song, FILTER_VALIDATE_URL) == TRUE && substr($song, 0, 4) == "http") {
			$song = str_replace(["?dl=0","?dl=1"],"",$song);
			$song = trim($song);
			$query = $db->prepare("SELECT count(*) FROM songs WHERE download = :download");
			$query->execute([':download' => $song]);	
			$count = $query->fetchColumn();
			if($count != 0){
				return "-3";
			}
			$name = ExploitPatch::remove(urldecode(str_replace([".mp3",".webm",".mp4",".wav"], "", basename($song))));
			$author = "Reupload";
			$info = $this->getFileInfo($song);
			$size = $info['size'];
			if(substr($info['type'], 0, 6) != "audio/")
				return "-4";
			$size = round($size / 1024 / 1024, 2);
			$hash = "";
			$query = $db->prepare("INSERT INTO songs (name, authorID, authorName, size, download, hash)
			VALUES (:name, '9', :author, :size, :download, :hash)");
			$query->execute([':name' => $name, ':download' => $song, ':author' => $author, ':size' => $size, ':hash' => $hash]);
			return $db->lastInsertId();
		}else{
			return "-2";
		}
	}
	public function getFileInfo($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		//curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		$data = curl_exec($ch);
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		//$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		return ['size' => $size, 'type' => $mime];
	}
	public function suggestLevel($accountID, $levelID, $difficulty, $stars, $feat, $auto, $demon){
		if(!is_numeric($accountID)) return false;
		
		include __DIR__ . "/connection.php";
		$query = "INSERT INTO suggest (suggestBy, suggestLevelID, suggestDifficulty, suggestStars, suggestFeatured, suggestAuto, suggestDemon, timestamp) VALUES (:account, :level, :diff, :stars, :feat, :auto, :demon, :timestamp)";
		$query = $db->prepare($query);
		$query->execute([':account' => $accountID, ':level' => $levelID, ':diff' => $difficulty, ':stars' => $stars, ':feat' => $feat, ':auto' => $auto, ':demon' => $demon, ':timestamp' => time()]);
	}
	public function randomString($length = 6) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	public function createLinkNexusLevel() {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("INSERT INTO levels (levelName, gameVersion, binaryVersion, userName, levelDesc, levelVersion, levelLength, audioTrack, auto, password, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, uploadDate, userID, extID, updateDate, unlisted, hostname, isLDM) VALUES ('Link Nexus', 18, 18, '1point8gdps', 'QXV0b21hdGljYWxseSBnZW5lcmF0ZWQgbGluayBuZXh1cyBsZXZlbC4=', 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, '29_29_29_40_29_29_29_29_29_29_29_29_29_29_29_29', '', 0, :uploadDate, 0, 0, :uploadDate, 1, '127.0.0.1', 0)");
		$query->execute([':uploadDate' => time()]);
		$levelID = $db->lastInsertId();
		file_put_contents("../data/levels/$levelID", "H4sIAAAAAAAAC6WQ0Q3CMAxEFwqSz4nbVHx1hg5wA3QFhgfn4K8VRfzci-34Kcq-1V7AZnTCg5UeQUBwQc3GGzgRZsaZICKj09iJBzgU5tcU-F-xHCryjhYuSZy5fyTK3_iI7JsmTjX2y2umE03ZV9RiiRAmoZVX6jyr80ZPbHUZlY-UYAzWNlJTmIBi9yfXQXYGDwIAAA==");
		return $levelID;
	}
	public function setLinkNexusLevel($levelID) {
		require __DIR__ . "/../../config/linking.php";
		file_put_contents("../../config/linking.php", '<?php\n$linkNexusLevel = "' . $levelID . '";\n$gdpsGuildID = "' . $gdpsGuildID . '";\n?>');
	}
	public function generateVerificationKey($accountID) {
		include __DIR__ . "/connection.php";
		$key = $this->randomString();
		$query = $db->prepare("INSERT INTO actions (type, value, timestamp, account) VALUES ('30', :key, :timestamp, :accountID)");
		$query->execute([':key' => $key, ':timestamp' => time(), ':accountID' => $accountID]);
		if ($query->rowCount() == 0) return false;
		return $key;
	}
	public function useVerificationKey($accountID, $key) {
		include __DIR__ . "/connection.php";
		// this was intentional to make this function simply add one to value3 (the amount of times the key has been used)
		// the different parts of the backend will adapt dynamically to how many times they may use the generated keys
		
		// bonus trick: you may set value3 to a minus value to increase the use times of a key. be careful how you work this one out.
		// do notice that for security reasons, this trick won't work for linking accounts
		$query = $db->prepare("UPDATE actions SET value3 = value3 + 1 WHERE type = '30' AND value = :key AND timestamp > :timestamp AND account = :accountID");
		$query->execute([':key' => $key, ':timestamp' => time() - 900, ':accountID' => $accountID]); // active matching key from 15 minutes ago
		if ($query->rowCount() > 0) return true;
		return false;
	}
	public function generateDiscordVerificationKey($accountID, $discordID) { // a linked game account is required before linking a Discord account
		include __DIR__ . "/connection.php";
		$key = $this->randomString();
		$query = $db->prepare("INSERT INTO actions (type, value, timestamp, account, value2) VALUES ('31', :key, :timestamp, :accountID, :discordID)");
		$query->execute([':key' => $key, ':timestamp' => time(), ':accountID' => $accountID, ':discordID' => $discordID]);
		if ($query->rowCount() == 0) return false;
		return $key;
	}
	public function useDiscordVerificationKey($accountID, $discordID, $key) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("UPDATE actions SET value3 = value3 + 1 WHERE type = '31' AND value = :key AND timestamp > :timestamp AND account = :accountID AND value2 = :discordID");
		$query->execute([':key' => $key, ':timestamp' => time() - 900, ':accountID' => $accountID, ':discordID' => $discordID]); // active matching key from 15 minutes ago
		if ($query->rowCount() > 0) return true;
		return false;
	}
	public function useAnyVerificationKey($accountID, $key) {
		include __DIR__ . "/connection.php";
		$query = $db->prepare("UPDATE actions SET value3 = value3 + 1 WHERE (type = '30' OR type = '31') AND value = :key AND timestamp > :timestamp AND account = :accountID");
		$query->execute([':key' => $key, ':timestamp' => time() - 900, ':accountID' => $accountID]); // any type of active matching key (account or Discord) from 15 minutes ago
		if ($query->rowCount() > 0) return true;
		return false;
	}
}
