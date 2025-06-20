<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$id = $gs->getLegacyAccountID();
if (!$id) {
	require "../../config/linking.php";
	if (!$linkNexusLevel) {
		$linkNexusLevel = $gs->createLinkNexusLevel();
		$gs->setLinkNexusLevel($linkNexusLevel);
	}
	exit("-1");
}

$gameVersion = !empty($_POST["gameVersion"]) ? ExploitPatch::remove($_POST["gameVersion"]) : 18;
$levelDesc = ExploitPatch::remove($_POST["levelDesc"]);
if ($gameVersion < 20) {
	$rawDesc = $levelDesc;
} else {
	$rawDesc = str_replace('_', '/', $levelDesc);
	$rawDesc = str_replace('-', '+', $rawDesc);
	$rawDesc = base64_decode($rawDesc);
}

// last generated key of any type from 15 minutes ago that has not been used thrice already
$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE (type = '30' OR type = '31') AND timestamp > :timestamp AND account = :accountID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 < 3");
$query->execute([':timestamp' => time() - 900, ':accountID' => $id]);
$verifyKey = $query->fetchColumn();
if (!$verifyKey OR !substr_count($rawDesc, $verifyKey)) exit("-1");
$rawDesc = str_replace($verifyKey, "", $rawDesc);
$gs->useAnyVerificationKey($id, $verifyKey);
//TODO: move description fixing code to a function
if (strpos($rawDesc, '<c') !== false) {
	$tags = substr_count($rawDesc, '<c');
	if ($tags > substr_count($rawDesc, '</c>')) {
		$tags = $tags - substr_count($rawDesc, '</c>');
		for ($i = 0; $i < $tags; $i++) {
			$rawDesc .= '</c>';
		}
	}
}
$levelDesc = str_replace('+', '-', base64_encode($rawDesc));
$levelDesc = str_replace('/', '_', $levelDesc);

$userName = $gs->getAccountName($id);
$levelID = ExploitPatch::remove($_POST["levelID"]);
$levelName = ExploitPatch::charclean($_POST["levelName"]);
$levelVersion = ExploitPatch::remove($_POST["levelVersion"]);
$levelLength = ExploitPatch::remove($_POST["levelLength"]);
$audioTrack = ExploitPatch::remove($_POST["audioTrack"]);
$secret = ExploitPatch::remove($_POST["secret"]);

$binaryVersion = !empty($_POST["binaryVersion"]) ? ExploitPatch::remove($_POST["binaryVersion"]) : $gameVersion;
$auto = !empty($_POST["auto"]) ? ExploitPatch::remove($_POST["auto"]) : 0;

$original = !empty($_POST["original"]) ? ExploitPatch::remove($_POST["original"]) : 0;
$twoPlayer = !empty($_POST["twoPlayer"]) ? ExploitPatch::remove($_POST["twoPlayer"]) : 0;
$songID = !empty($_POST["songID"]) ? ExploitPatch::remove($_POST["songID"]) : 0;
$objects = !empty($_POST["objects"]) ? ExploitPatch::remove($_POST["objects"]) : 0;
$coins = !empty($_POST["coins"]) ? ExploitPatch::remove($_POST["coins"]) : 0;
$requestedStars = !empty($_POST["requestedStars"]) ? ExploitPatch::remove($_POST["requestedStars"]) : 0;
//TODO: verify if this is an optimal extraString for old levels
$extraString = !empty($_POST["extraString"]) ? ExploitPatch::remove($_POST["extraString"]) : "29_29_29_40_29_29_29_29_29_29_29_29_29_29_29_29";
$levelString = ExploitPatch::remove($_POST["levelString"]);
//TODO: optionally utilize the 1.9 parameter instead
$levelInfo = !empty($_POST["levelInfo"]) ? ExploitPatch::remove($_POST["levelInfo"]) : "";
//TODO: optionally utilize the 2.2 parameter instead
$unlisted = !empty($_POST["unlisted1"]) ? ExploitPatch::remove($_POST["unlisted1"]) : 
	(!empty($_POST["unlisted"]) ? ExploitPatch::remove($_POST["unlisted"]) : 0);
$unlisted2 = !empty($_POST["unlisted2"]) ? ExploitPatch::remove($_POST["unlisted2"]) : $unlisted;
$ldm = !empty($_POST["ldm"]) ? ExploitPatch::remove($_POST["ldm"]) : 0;
$wt = !empty($_POST["wt"]) ? ExploitPatch::remove($_POST["wt"]) : 0;
$wt2 = !empty($_POST["wt2"]) ? ExploitPatch::remove($_POST["wt2"]) : 0;
$settingsString = !empty($_POST["settingsString"]) ? ExploitPatch::remove($_POST["settingsString"]) : "";
$songIDs = !empty($_POST["songIDs"]) ? ExploitPatch::numbercolon($_POST["songIDs"]) : '';
$sfxIDs = !empty($_POST["sfxIDs"]) ? ExploitPatch::numbercolon($_POST["sfxIDs"]) : '';
$ts = !empty($_POST["ts"]) ? ExploitPatch::number($_POST["ts"]) : 0;

if(isset($_POST["password"])){
	$password = ExploitPatch::remove($_POST["password"]);
}else{
	$password = 1;
	if($gameVersion > 17){
		$password = 0;
	}
}

$hostname = $gs->getIP();
$uploadDate = time();
$query = $db->prepare("SELECT COUNT(*) FROM levels WHERE uploadDate > :time AND (extID = :id OR hostname = :ip)");
$query->execute([':time' => $uploadDate - 60, ':id' => $id, ':ip' => $hostname]);
if ($query->fetchColumn() > 0) {
	exit("-1");
}

$query = $db->prepare("INSERT INTO levels (levelName, gameVersion, binaryVersion, userName, levelDesc, levelVersion, levelLength, audioTrack, auto, password, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, secret, uploadDate, userID, extID, updateDate, unlisted, hostname, isLDM, wt, wt2, unlisted2, settingsString, songIDs, sfxIDs, ts)
VALUES (:levelName, :gameVersion, :binaryVersion, :userName, :levelDesc, :levelVersion, :levelLength, :audioTrack, :auto, :password, :original, :twoPlayer, :songID, :objects, :coins, :requestedStars, :extraString, :levelString, :levelInfo, :secret, :uploadDate, :userID, :id, :uploadDate, :unlisted, :hostname, :ldm, :wt, :wt2, :unlisted2, :settingsString, :songIDs, :sfxIDs, :ts)");


if($levelString != "" AND $levelName != ""){
	$querye=$db->prepare("SELECT levelID FROM levels WHERE levelName = :levelName AND extID = :id");
	$querye->execute([':levelName' => $levelName, ':id' => $id]);
	$levelID = $querye->fetchColumn();
	if($querye->rowCount() > 0){
		$query = $db->prepare("UPDATE levels SET levelName=:levelName, gameVersion=:gameVersion, binaryVersion=:binaryVersion, userName=:userName, levelDesc=:levelDesc, levelVersion=:levelVersion, levelLength=:levelLength, audioTrack=:audioTrack, auto=:auto, password=:password, original=:original, twoPlayer=:twoPlayer, songID=:songID, objects=:objects, coins=:coins, requestedStars=:requestedStars, extraString=:extraString, levelString=:levelString, levelInfo=:levelInfo, secret=:secret, updateDate=:uploadDate, unlisted=:unlisted, hostname=:hostname, isLDM=:ldm, wt=:wt, wt2=:wt2, unlisted2=:unlisted2, settingsString=:settingsString, songIDs=:songIDs, sfxIDs=:sfxIDs, ts=:ts WHERE levelName=:levelName AND extID=:id");	
		$query->execute([':levelName' => $levelName, ':gameVersion' => $gameVersion, ':binaryVersion' => $binaryVersion, ':userName' => $userName, ':levelDesc' => $levelDesc, ':levelVersion' => $levelVersion, ':levelLength' => $levelLength, ':audioTrack' => $audioTrack, ':auto' => $auto, ':password' => $password, ':original' => $original, ':twoPlayer' => $twoPlayer, ':songID' => $songID, ':objects' => $objects, ':coins' => $coins, ':requestedStars' => $requestedStars, ':extraString' => $extraString, ':levelString' => "", ':levelInfo' => $levelInfo, ':secret' => $secret, ':levelName' => $levelName, ':id' => $id, ':uploadDate' => $uploadDate, ':unlisted' => $unlisted, ':hostname' => $hostname, ':ldm' => $ldm, ':wt' => $wt, ':wt2' => $wt2, ':unlisted2' => $unlisted2, ':settingsString' => $settingsString, ':songIDs' => $songIDs, ':sfxIDs' => $sfxIDs, ':ts' => $ts]);
		file_put_contents("../../data/levels/$levelID", $levelString);
		echo $levelID;
	}else{
		$query->execute([':levelName' => $levelName, ':gameVersion' => $gameVersion, ':binaryVersion' => $binaryVersion, ':userName' => $userName, ':levelDesc' => $levelDesc, ':levelVersion' => $levelVersion, ':levelLength' => $levelLength, ':audioTrack' => $audioTrack, ':auto' => $auto, ':password' => $password, ':original' => $original, ':twoPlayer' => $twoPlayer, ':songID' => $songID, ':objects' => $objects, ':coins' => $coins, ':requestedStars' => $requestedStars, ':extraString' => $extraString, ':levelString' => "", ':levelInfo' => $levelInfo, ':secret' => $secret, ':uploadDate' => $uploadDate, ':userID' => $gs->getUserID($id), ':id' => $id, ':unlisted' => $unlisted, ':hostname' => $hostname, ':ldm' => $ldm, ':wt' => $wt, ':wt2' => $wt2, ':unlisted2' => $unlisted2, ':settingsString' => $settingsString, ':songIDs' => $songIDs, ':sfxIDs' => $sfxIDs, ':ts' => $ts]);
		$levelID = $db->lastInsertId();
		file_put_contents("../../data/levels/$levelID", $levelString);
		echo $levelID;
	}
}else{
	echo -1;
}
?>
