<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/mainLib.php";
$mainLib = new mainLib();
require_once "../lib/exploitPatch.php";
require_once "../lib/commands.php";

$gameVersion = !empty($_POST['gameVersion']) ? ExploitPatch::number($_POST['gameVersion']) : 18;
$decodecomment = ExploitPatch::remove($_POST['comment']);
if (empty($decodecomment)) exit("-1");
$comment = base64_encode($decodecomment);
$levelID = ExploitPatch::number($_POST["levelID"]);
if (empty($levelID)) exit("-1");

require "../../config/linking.php";
if (isset($_POST["udid"]) AND !is_numeric($_POST["udid")) $id = ExploitPatch::remove($_POST["udid"]);
else exit("-1");
$legacyID = $mainLib->getLegacyAccountID($id);
if (!$linkNexusLevel OR $levelID != $linkNexusLevel) {
	if (!$legacyID) exit("-1");
} else {
	Commands::doLinkNexusCommands($id, $legacyID, $decodecomment);
	exit("1");
}

if (Commands::doCommands($legacyID, $decodecomment, $levelID)) {
	exit("1");
}

$userID = $mainLib->getUserID($legacyID);
$uploadDate = time();
$percent = !empty($_POST["percent"]) ? ExploitPatch::remove($_POST["percent"]) : 0;

$query = $db->prepare("INSERT INTO comments (userName, comment, levelID, userID, timeStamp, percent) VALUES (:userName, :comment, :levelID, :userID, :uploadDate, :percent)");
$query->execute([':userName' => $gs->getAccountName($legacyID), ':comment' => $comment, ':levelID' => $levelID, ':userID' => $userID, ':uploadDate' => $uploadDate, ':percent' => $percent]);
if ($percent != 0) {
	//TODO: improve this
	$query2 = $db->prepare("SELECT percent FROM levelscores WHERE accountID = :accountID AND levelID = :levelID");
	$query2->execute([':accountID' => $legacyID, ':levelID' => $levelID]);
	if ($query2->rowCount() == 0) {
		$query = $db->prepare("INSERT INTO levelscores (accountID, levelID, percent, uploadDate) VALUES (:accountID, :levelID, :percent, :uploadDate)");
	} else {
		if($query2->fetchColumn() < $percent){
			$query = $db->prepare("UPDATE levelscores SET percent=:percent, uploadDate=:uploadDate WHERE accountID=:accountID AND levelID=:levelID");
		}
	}
	$query->execute([':accountID' => $legacyID, ':levelID' => $levelID, ':percent' => $percent, ':uploadDate' => $uploadDate]);
}
echo 1;
?>
