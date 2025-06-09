<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";

if (!isset($_POST['levelID']) OR !is_numeric($_POST['levelID'])) exit("-1");
$levelID =  ExploitPatch::remove($_POST["levelID"]);
$ip = mainLib::getIP();

$query = $db->prepare("SELECT COUNT(*) FROM reports WHERE levelID = :levelID AND hostname = :hostname");
$query->execute([':levelID' => $levelID, ':hostname' => $ip]);
if ($query->fetchColumn() == 0) {
	$query = $db->prepare("INSERT INTO reports (levelID, hostname) VALUES (:levelID, :hostname)");	
	$query->execute([':levelID' => $levelID, ':hostname' => $ip]);
	echo $db->lastInsertId();
} else {
	echo -1;
}	
?>
