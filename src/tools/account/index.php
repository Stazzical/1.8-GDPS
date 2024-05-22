<h3>Account Management</h3>
<a href="..">Go back to main tools page</a><br><br>
<?php
$dirstring = "";
$files = scandir(".");
foreach ($files as $file) {
	if (pathinfo($file, PATHINFO_EXTENSION) == "php" AND $file != "index.php") {
		$dirstring .= "<li><a href='./$file'>$file</a></li>";
	}
}
echo $dirstring;
?>