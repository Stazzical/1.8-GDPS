<?php
session_start();
if (isset($_POST["userName"]) AND isset($_POST["email"]) AND isset($_POST["password"]) AND isset($_POST["repeatPassword"])){
	require "../../incl/lib/connection.php";
	require_once "../../incl/lib/exploitPatch.php";
	$ep = new exploitPatch();
	// Loading the data
	$userName = $ep->remove($_POST["userName"]);
	$password = $_POST["password"];
	$repeatPassword = $_POST["repeatPassword"];
	$email = $ep->remove($_POST["email"]);
	if (strlen($userName) < 3 OR strlen($userName) > 15) {
		exit("Naughty naughty!");
	} elseif (strlen($password) < 6 OR strlen($password) > 20) {
		exit("Naughty naughty!");
	}
	// Checking 
	$query = $db->prepare("SELECT COUNT(*) FROM accounts WHERE userName LIKE :userName");
	$query->execute([':userName' => $userName]);
	if ($query->fetchColumn() != 0) {
		echo 'Username already taken. Please try again.';
	} else {
		if ($password != $repeatPassword) {
			echo 'Passwords do not match. Please try again.';
		} else {
			if (isset($_POST["captcha"]) AND $_POST["captcha"] != "" AND $_SESSION["code"] == $_POST["captcha"]) {
				require_once "../../incl/lib/generatePass.php";
				$query = $db->prepare("INSERT INTO accounts (userName, password, email, registerDate, isActive, gjp2) VALUES (:userName, :password, :email, :time, 1, :gjp2)");
				$query->execute([':userName' => $userName, ':password' => password_hash($password, PASSWORD_DEFAULT), ':email' => $email, ':time' => time(), ':gjp2' => GeneratePass::GJP2hash($password)]);
				
				echo "Your account has successfully been registred!<br>";
				
				require_once "../../incl/lib/mainLib.php";
				$verificationKey = mainLib::generateVerificationKey($accountID);
				if ($verificationKey) echo "Your verification key is: " . $verificationKey . "<br>Use it to link your in-game user to this account.<br>Verification keys will only last for 15 minutes.<br>";
				else echo "Failed to generate a verification key! <a href='generateKey.php'>Try generating one manually.</a>";
				
				echo "<a href='index.php'>Go back to account management.</a>";
			} else {
				echo "Captcha verification failed. Please try again.";
			}
		}
	}
	echo "<br><br>";
}
?>
<form action="registerAccount.php" method="post">
	Username: <input type="text" name="userName" minlength=3 maxlength=15><br>
	Password: <input type="password" name="password" minlength=6 maxlength=20><br>
	Repeat Password: <input type="password" name="repeatPassword" minlength=6 maxlength=20><br>
	Email: <input type="email" name="email" maxlength=50><br>
	Verify Captcha: <input name="captcha" type="text"><br>
	<img src="../../incl/misc/captchaGen.php"><br><br>
	<input type="submit" value="Register">
</form>