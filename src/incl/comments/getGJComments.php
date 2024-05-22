<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$gameVersion = isset($_POST['gameVersion']) ? ExploitPatch::remove($_POST["gameVersion"]) : 18;
$binaryVersion = isset($_POST['binaryVersion']) ? ExploitPatch::remove($_POST["binaryVersion"]) : $gameVersion;
$userID = isset($_POST["userID"]) ? ExploitPatch::remove($_POST["userID"]) : 0;

if (!isset($_POST['levelID']) OR !is_numeric($_POST['levelID']) exit("-1"); 
$levelID = ExploitPatch::remove($_POST["levelID"]);

$commentstring = "";
$commentcountfinal = 0;
$botcommentcount = 0;
$isNotLinked = 0;

require "../../config/linking.php";
if ($linkNexusLevel AND $levelID == $linkNexusLevel) {
	if (!$userID) exit("2~userID not provided. Information will be unavailable.~3~0~4~0~5~0~7~0~9~Automated~6~0~10~0#0:Information:0");
	
	$UDID = $gs->getExtID($userID);
	if (!$UDID) exit("2~A non-existent userID has been provided. Information will be unavailable.~3~0~4~0~5~0~7~0~9~Automated~6~0~10~0#0:Information:0");
	if (is_numeric($UDID)) {
		$legacyID = $UDID;
		$UDID = $gs->getLegacyExtID($legacyID);
		if (!$UDID) exit("2~Your userID has a non-linked account tied to it. Information will be unavailable.~3~0~4~0~5~0~7~0~9~Automated~6~0~10~0#0:Information:0");
	}
	
	// fetching recent link nexus bot comments
	// only the ones from 2 minutes ago and the last 9 are shown
	$query = $db->prepare("SELECT * FROM (SELECT value, timestamp FROM actions WHERE type = '32' AND timestamp >= :time AND account = :userID AND value2 = :linkNexus ORDER BY timestamp DESC LIMIT 9) ORDER BY timestamp ASC");
	$query->execute([':timestamp' => time() - 120, ':userID' => $userID, ':linkNexus' => $linkNexusLevel]);
	$result = $query->fetchAll();
	foreach ($result as $comment) {
		$uploadDate = $gs->makeTime($comment["timestamp"]);
		$commentText = ($gameVersion > 20) ? $comment["value"] : base64_encode($comment["value"]);
		echo "2~" . $commentText . "~3~0~4~0~5~0~7~0~9~" . $uploadDate . "~6~" . $botcommentcount . "~10~0|";
		$botcommentcount--;
	}
	
	$legacyID = $legacyID ? $legacyID : $gs->getLegacyAccountID($UDID);
	if (!$legacyID) exit("2~You haven't linked your account yet!~3~0~4~0~5~0~7~0~9~Automated~6~" . $botcommentcount . "~10~0#0:Information:0");
	echo "2~Linked account: " . $gs->getAccountName($legacyID);
	$discordID = $gs->getLegacyDiscordID($UDID);
	if ($discordID) echo " | Linked Discord: " . $gs->getDiscordUsername($discordID);
	exit("~3~0~4~0~5~0~7~0~9~Automated~6~" . $botcommentcount . "~10~0#0:Information:0");
} elseif ($userID) {
	$id = $gs->getExtID($userID);
	if (!$id OR !is_numeric($id)) {
		$commentcountfinal += 2;
		$botcommentcount -= 2;
		$isNotLinked = 1;
	}
}

$filterColumn = 'levelID';
$filterToFilter = '';
$displayLevelID = false;
$filterID = $levelID;
$userListJoin = $userListWhere = $userListColumns = "";

$userstring = "";
$users = array();

$mode = isset($_POST["mode"]) ? ExploitPatch::remove($_POST["mode"]) : 0;
$count = (isset($_POST["count"]) AND is_numeric($_POST["count"])) ? ExploitPatch::remove($_POST["count"]) : 10;
$page = isset($_POST['page']) ? ExploitPatch::remove($_POST["page"]) : 0;

// fetching recent level bot comments (e.g. command responses and errors)
// only the ones from 2 minutes are shown, amount is based on how many $count allows
$query = $db->prepare("SELECT * FROM (SELECT value, timestamp FROM actions WHERE type = '32' AND timestamp >= :time AND account = :userID AND value2 = :levelID ORDER BY timestamp DESC LIMIT ${count}) ORDER BY timestamp ASC");
$query->execute([':timestamp' => time() - 120, ':userID' => $userID, ':levelID' => $levelID]);
$result = $query->fetchAll();
foreach ($result as $comment) {
	$uploadDate = $gs->makeTime($comment["timestamp"]);
	$commentText = ($gameVersion > 20) ? $comment["value"] : base64_encode($comment["value"]);
	$commentstring .= "2~" . $commentText . "~3~0~4~0~5~0~7~0~9~" . $uploadDate . "~6~" . $botcommentcount . "~10~0|";
	$commentcountfinal++;
	$botcommentcount--;
}

$count = $count + $botcommentcount;

$countquery = "SELECT COUNT(*) FROM comments ${userListJoin} WHERE ${filterToFilter}${filterColumn} = :filterID ${userListWhere}";
$countquery = $db->prepare($countquery);
$countquery->execute([':filterID' => $filterID]);
$commentcount = $countquery->fetchColumn();
$commentcountfinal += $commentcount;
if ($commentcountfinal == 0) {
	exit("-2");
}

if ($commentcount != 0 OR $count != 0) {
	$commentpage = $page * $count;
	if ($mode == 0)
		$modeColumn = "commentID";
	else
		$modeColumn = "likes";
	
	$query = "SELECT comments.levelID, comments.commentID, comments.timestamp, comments.comment, comments.userID, comments.likes, comments.isSpam, comments.percent, users.userName, users.icon, users.color1, users.color2, users.iconType, users.special, users.extID FROM comments LEFT JOIN users ON comments.userID = users.userID ${userListJoin} WHERE comments.${filterColumn} = :filterID ${userListWhere} ORDER BY comments.${modeColumn} DESC LIMIT ${count} OFFSET ${commentpage}";
	$query = $db->prepare($query);
	$query->execute([':filterID' => $filterID]);
	$result = $query->fetchAll();
	$visiblecount = $query->rowCount();
	
	foreach ($result as $comment1) {
		if ($comment1["commentID"] != "") {
			$uploadDate = $gs->makeTime($comment1["timestamp"]);
			$commentText = ($gameVersion < 20) ? base64_decode($comment1["comment"]) : $comment1["comment"];
			$commentstring .= "2~".$commentText."~3~".$comment1["userID"]."~4~".$comment1["likes"]."~5~0~7~".$comment1["isSpam"]."~9~".$uploadDate."~6~".$comment1["commentID"]."~10~".$comment1["percent"];
			if ($comment1['userName']) { //TODO: get rid of queries caused by getMaxValuePermission and getAccountCommentColor
				$extID = is_numeric($comment1["extID"]) ? $comment1["extID"] : 0;
				if ($binaryVersion > 31) {
					$badge = $gs->getMaxValuePermission($extID, "modBadgeLevel");
					$colorString = $badge > 0 ? "~12~" . $gs->getAccountCommentColor($extID) : "";
					
					$commentstring .= "~11~${badge}${colorString}:1~".$comment1["userName"]."~7~1~9~".$comment1["icon"]."~10~".$comment1["color1"]."~11~".$comment1["color2"]."~14~".$comment1["iconType"]."~15~".$comment1["special"]."~16~".$extID;
				} elseif (!in_array($comment1["userID"], $users)) {
					$users[] = $comment1["userID"];
					$userstring .=  $comment1["userID"] . ":" . $comment1["userName"] . ":" . $extID . "|";
				}
				$commentstring .= "|";
			}
		}
	}
}

if ($isNotLinked == 1) {
	$commentstring .= "2~Your current user does not have a linked account on it. Some functionality may be unavailable.~3~0~4~0~5~0~7~0~9~Automated~6~" . $botcommentcount . "~10~0|";
	$commentstring .= "2~Please search for level ID '" . $linkNexusLevel . "' to begin linking your account.~3~0~4~0~5~0~7~0~9~Automated~6~" . ($botcommentcount - 1) . "~10~0|";
	$userstring .= "0:Information:0|";
}

$commentstring = substr($commentstring, 0, -1);
echo $commentstring;
if ($binaryVersion < 32) {
	$userstring = substr($userstring, 0, -1);
	echo "#$userstring";
}
echo "#${commentcount}:${commentpage}:${visiblecount}";
?>
