<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

if (!isset($_POST['levelID']) OR !is_numeric($_POST["levelID"])) {
	exit("-1");
}
$levelID = ExploitPatch::remove($_POST["levelID"]);

$accountID = $gs->getLegacyAccountID();
if (!$accountID) exit("-1");

$userID = $gs->getUserID($accountID);
$query = $db->prepare("DELETE from levels WHERE levelID=:levelID AND userID=:userID AND starStars = 0 LIMIT 1");
$query->execute([':levelID' => $levelID, ':userID' => $userID]);
if (file_exists("../../data/levels/$levelID") AND $query->rowCount() != 0){
	rename("../../data/levels/$levelID", "../../data/levels/deleted/$levelID");
	$query6 = $db->prepare("INSERT INTO actions (type, value, timestamp, value2) VALUES ('8', :itemID, :time, :ip)");
	$query6->execute([':itemID' => $levelID, ':time' => time(), ':ip' => $userID]);
	echo "1";
} else {
	echo "-1";
}
?>
