<?php
chdir(__DIR__);
require "../lib/connection.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();
require_once "../lib/exploitPatch.php";

if (empty($_POST["commentID"]) OR !is_numeric($_POST["commentID"])) {
	exit("-1");
}
$legacyID = $gs->getLegacyAccountID();
if (!$legacyID) {
	require "../../config/linking.php";
	if (!$linkNexusLevel) {
		$linkNexusLevel = $gs->createLinkNexusLevel();
		$gs->setLinkNexusLevel($linkNexusLevel);
	}
	exit("-1");
}

$commentID = ExploitPatch::remove($_POST["commentID"]);
$userID = $gs->getUserID($legacyID);
$query = $db->prepare("DELETE FROM comments WHERE commentID = :commentID AND userID = :userID LIMIT 1");
$query->execute([':commentID' => $commentID, ':userID' => $userID]);
if ($query->rowCount() == 0) {
	// getting level creator account ID
	$query = $db->prepare("SELECT users.extID FROM comments INNER JOIN levels ON levels.levelID = comments.levelID INNER JOIN users ON levels.userID = users.userID WHERE commentID = :commentID");
	$query->execute([':commentID' => $commentID]);
	if ($query->fetchColumn() == $legacyID OR $gs->checkPermission($legacyID, "actionDeleteComment") == 1) {
		$query = $db->prepare("DELETE FROM comments WHERE commentID = :commentID AND levelID = :levelID LIMIT 1");
		$query->execute([':commentID' => $commentID, ':levelID' => $levelID]);
		if ($query->rowCount() == 0) {
			echo "-1";
		} else {
			echo "1";
		}
	} else {
		echo "-1";
	}
}
echo "1";
?>
