<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/exploitPatch.php";
$stars = 0;
$count = 0;
$xi = 0;
$lbstring = "";

require_once "../lib/mainLib.php";
$accountID = mainLib::getLegacyAccountID();
if (!$accountID) {
	require "../../config/linking.php";
	if (!$linkNexusLevel) {
		$linkNexusLevel = $gs->createLinkNexusLevel();
		mainLib::setLinkNexusLevel($linkNexusLevel);
	}
	exit("1:Search for level " . $linkNexusLevel . ":2:0:13:0:6:1:9:0:10:0:11:0:14:0:15:0:16:0:3:0:8:0:4:0:7:0");
}

if (empty($_POST["gameVersion"])) {
	$sign = "<= 18 AND gameVersion <> 0";
} else {
	$sign = "= 18";
}

$type = ExploitPatch::remove($_POST["type"]);
if ($type == "top" OR $type == "creators" OR $type == "relative") {
	if ($type == "top") {
		$query = $db->prepare("SELECT * FROM users WHERE isBanned = '0' AND gameVersion $sign AND stars > 0 ORDER BY stars DESC LIMIT 100");
		$query->execute();
	}
	if ($type == "creators") {
		$query = $db->prepare("SELECT * FROM users WHERE isCreatorBanned = '0' AND creatorPoints > 0 ORDER BY creatorPoints DESC LIMIT 100");
		$query->execute();
	}
	if ($type == "relative") {
		$query = $db->prepare("SELECT * FROM users WHERE extID = :accountID");
		$query->execute([':accountID' => $accountID]);
		$user = $query->fetchAll()[0];
		$stars = $user["stars"];
		if($_POST["count"]){
			$count = ExploitPatch::remove($_POST["count"]);
		}else{
			$count = 50;
		}
		$count = floor($count / 2);
		$query = $db->prepare("SELECT	A.* FROM	(
			(
				SELECT	*	FROM users
				WHERE stars <= :stars
				AND isBanned = 0
				AND gameVersion $sign
				ORDER BY stars DESC
				LIMIT $count
			)
			UNION
			(
				SELECT * FROM users
				WHERE stars >= :stars
				AND isBanned = 0
				AND gameVersion $sign
				ORDER BY stars ASC
				LIMIT $count
			)
		) as A
		ORDER BY A.stars DESC");
		$query->execute([':stars' => $stars]);
	}
	$result = $query->fetchAll();
	if ($type == "relative") {
		$user = $result[0];
		$extID = $user["extID"];
		$e = "SET @rownum := 0;";
		$query = $db->prepare($e);
		$query->execute();
		$f = "SELECT rank, stars FROM (
			SELECT @rownum := @rownum + 1 AS rank, stars, extID, isBanned
			FROM users WHERE isBanned = '0' AND gameVersion $sign ORDER BY stars DESC
		) as result WHERE extID=:extID";
		$query = $db->prepare($f);
		$query->execute([':extID' => $extID]);
		$leaderboard = $query->fetchAll();
		$leaderboard = $leaderboard[0];
		$xi = $leaderboard["rank"] - 1;
	}
	foreach ($result as $user) {
		$xi++;
		$extID = is_numeric($user['extID']) ? $user['extID'] : 0;
		$userName = $extID ? $gs->getAccountName($extID) : $user["userName"];
		$lbstring .= "1:".$userName.":2:".$user["userID"].":13:".$user["coins"].":17:".$user["userCoins"].":6:".$xi.":9:".$user["icon"].":10:".$user["color1"].":11:".$user["color2"].":51:".$user["color3"].":14:".$user["iconType"].":15:".$user["special"].":16:".$extID.":3:".$user["stars"].":8:".round($user["creatorPoints"],0,PHP_ROUND_HALF_DOWN).":4:".$user["demons"].":7:".$extID."|";
	}
}
if ($type == "week") { // By Absolute, with some edits done
	$weekStartingDay = "monday";
	$query = $db->prepare("SELECT users.userName, actions.account, SUM(actions.value), SUM(actions.value2), SUM(actions.value3)
	FROM actions
	LEFT JOIN users ON users.userID = actions.account
	WHERE actions.type = 9
	AND actions.timestamp > :since
	GROUP BY actions.account
	HAVING SUM(actions.value) != 0
	OR SUM(actions.value2) != 0
	OR SUM(actions.value3) != 0
	ORDER BY SUM(actions.value) DESC
	LIMIT 100");
	$query->execute([':since' => strtotime("last " . $weekStartingDay . " 00:00:00")]);
	$result = $query->fetchAll();
	if (count($result) == 0) {
		exit("-1");
	}
	foreach ($result as $user) {
		$xi++;
		$extID = is_numeric($user[6]) ? $user[6] : 0;
		$userName = $extID ? $gs->getAccountName($extID) : $user[0];
		$lbstring .= "1:" . $userName . ":2:" . $user[7] . ":4:" . $user[10] . ":13:" . $user[9] . ":6:" . $xi . ":9:" . $user[1] . ":10:" . $user[2] . ":11:" . $user[3] . ":14:" . $user[4] . ":15:" . $user[5] . ":16:" . $extID . ":3:" . $user[8] . ":7:" . $extID . "|";
	}
}
if ($lbstring == "") {
	exit("-1");
}
$lbstring = substr($lbstring, 0, -1);
echo $lbstring;
?>