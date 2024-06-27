<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
$xi = 0;

require_once "../lib/mainLib.php";
if (!mainLib::getLegacyAccountID()) {
	require "../../config/linking.php";
	if (!$linkNexusLevel) {
		$linkNexusLevel = $gs->createLinkNexusLevel();
		mainLib::setLinkNexusLevel($linkNexusLevel);
	}
	exit("1:Search for level " . $linkNexusLevel . ":2:0:13:0:6:1:9:0:10:0:11:0:14:0:15:0:16:0:3:0:8:0:4:0:7:0");
}

// $type = ExploitPatch::remove($_POST["type"]);

$query = $db->prepare("SELECT * FROM users WHERE isCreatorBanned = '0' ORDER BY creatorPoints DESC LIMIT 100");
$query->execute();
$result = $query->fetchAll();
foreach ($result as $user){
	$xi++;
	$extid = is_numeric($user['extID']) ? $user['extID'] : 0;
	$pplstring .= "1:".$user["userName"].":2:".$user["userID"].":13:".$user["coins"].":17:".$user["userCoins"].":6:".$xi.":9:".$user["icon"].":10:".$user["color1"].":11:".$user["color2"].":14:".$user["iconType"].":15:".$user["special"].":16:".$extid.":3:".$user["stars"].":8:".round($user["creatorPoints"],0,PHP_ROUND_HALF_DOWN).":4:".$user["demons"].":7:".$extid.":46:".$user["diamonds"]."|";
}
$pplstring = substr($pplstring, 0, -1);
echo $pplstring;
?>
