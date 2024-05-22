<?php
session_start();
if (isset($_POST["userName"]) AND isset($_POST["password"]) AND isset($_POST["captcha"])) {
	require "../../incl/lib/connection.php";
	require_once "../../incl/lib/generatePass.php";
	$gp = new generatePass();
	require_once "../../incl/lib/exploitPatch.php";
	$ep = new exploitPatch();
	$userName = $ep->remove($_POST["userName"]);
	$password = $_POST["password"];
	$query = $db->prepare("SELECT accountID FROM accounts WHERE userName = :userName");
	$query->execute([':userName' => $userName]);
	if ($query->rowCount() == 0) {
		echo "Non-existent account. Please try again.";
	} else {
		$accountID = $query->fetchColumn();
		if ($_POST["captcha"] != "" AND $_SESSION["code"] == $_POST["captcha"]) {
			if ($gp->isValid($accountID, $password)) {
				require_once "../../incl/lib/mainLib.php";
				$verificationKey = mainLib::generateVerificationKey($accountID);
				if ($verificationKey) echo "New verification key generated: " . $verificationKey . "<br>Verification keys will only last for 15 minutes.<br><a href='index.php'>Go back to account management.</a><br><br>";
				else echo "Failed to generate a verification key! Please try again.";
			} else {
				echo "Incorrect username-password combination. Please try again.";
			}
		} else {
			echo "Captcha check failed. Please try again.";
		}
	}
	echo "<br><br>";
}
?>
<form action="generateKey.php" method="post">
	Username: <input type="text" name="userName" minlength=3 maxlength=15><br>
	Password: <input type="password" name="password" minlength=6 maxlength=20><br>
	Verify Captcha: <input name="captcha" type="text"><br>
	<img src="../../incl/misc/captchaGen.php"><br><br>
	<input type="submit" value="Generate">
</form>