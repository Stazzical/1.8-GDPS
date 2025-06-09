<?php
class Commands {
	public static function ownCommand($comment, $command, $accountID, $targetExtID){
		require_once "../lib/mainLib.php";
		$gs = new mainLib();
		$commandInComment = strtolower("!".$command);
		$commandInPerms = ucfirst(strtolower($command));
		$commandlength = strlen($commandInComment);
		if(substr($comment,0,$commandlength) == $commandInComment AND (($gs->checkPermission($accountID, "command".$commandInPerms."All") OR ($targetExtID == $accountID AND $gs->checkPermission($accountID, "command".$commandInPerms."Own"))))){
			return true;
		}
		return false;
	}
	public static function createBotComment($comment, $userID, $levelID) {
		include __DIR__ . "/connection.php";
		
		$query = $db->prepare("INSERT INTO actions (type, value, timestamp, account, value2) VALUES ('32', :comment, :timestamp, :userID, :levelID)");
		$query->execute([':comment' => $comment, ':timestamp' => time(), ':userID' => $userID, ':levelID' => $levelID]);
	}
	public static function doCommands($accountID, $comment, $levelID) {
		include dirname(__FILE__)."/../lib/connection.php";
		require_once "../lib/exploitPatch.php";
		require_once "../lib/mainLib.php";
		$gs = new mainLib();
		
		require "../../config/linking.php";
		require "../../config/discord.php";
		$userID = $gs->getUserID($accountID);
		
		// command to manually request a verification key sent to your Discord account at any time
		if (substr($comment, 0, 8) == '!sendkey' OR substr($comment, 0, 8) == '!senddsc' OR substr($comment, 0, 9) == '!senddisc') {
			if (!$discordEnabled) {
				self::createBotComment("!sendkey error: Discord functionality is currently disabled on this server.", $userID, $levelID);
				return true;
			}
			if (!$bottoken) {
				self::createBotComment("!sendkey error: No Discord bot token is configured for the server. Please contact the server administrator(s).", $userID, $levelID);
				return true;
			}
			$discordID = $gs->getLegacyDiscordIDFromAcc($accountID);
			if (!$discordID) {
				self::createBotComment("!sendkey error: You do not have a linked Discord account for this command.", $userID, $levelID);
				return true;
			}
			$message = array(
				"embeds" => array([
					"title" => "Verification Key Request",
					"description" => "Hello! We've received a request for a verification key on **1.8 GDPS** from `" . substr($gs->getAccountName($accountID), 0, 3) . "*****`.",
					"color" => hexdec("FDD938"),
					"footer" => [
						"text" => "Verification keys only last for 15 minutes. If you did not request this verification key, please ignore this message."
					],
					"fields" => [
						[
							"name" => "Your verification key",
							"value" => "Your verification key is: **" . $gs->generateDiscordVerificationKey($accountID, $discordID) . "**"
						],
						[
							"name" => "What should I do?",
							"value" => "You may now use this verification key to perform level commands in comment sections."
						],
						[
							"name" => "One more thing!",
							"value" => "For the purpose of executing commands, you may only use each verification key __3__ times before it is expired. Choose your moves wisely!"
						],
						[
							"name" => "Anything else?",
							"value" => "Please be aware that generation of new Discord verification keys will instantly expire your older keys."
						]
					]
				])
			);
			$gs->sendDiscordPM($discordID, $message);
			self::createBotComment("!sendkey success: A verification key has been sent to '" . substr($gs->getDiscordUsername($discordID), 0, 3) . "*****'.", $userID, $levelID);
			return true;
		}
		
		$uploadDate = time();
		$commentarray = "";

		// anonymous functions amirite
		$verificationCheck = function() use ($db, $gs, $discordEnabled, $bottoken, $accountID, $userID, $levelID, &$comment, &$commentarray) {
			// looking for a verification key before allowing any commands to run
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE (type = '30' OR type = '31') AND timestamp > :timestamp AND account = :accountID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 < 3");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $accountID]); // last generated key of any type from 15 minutes ago that has not been used more than three times
			if ($query->rowCount() == 0) {
				if (!$discordEnabled OR !$bottoken) {
					self::createBotComment("Command error: Please generate a verification key for use before executing.", $userID, $levelID);
					return false;
				}
				// attempting to automatically generate a verification key and send it to the user's linked discord account if there is any
				$discordID = $gs->getLegacyDiscordIDFromAcc($accountID);
				if (!$discordID) {
					self::createBotComment("Command error: Please generate a verification key for use (or link your Discord) before executing.", $userID, $levelID);
					return false;
				}
				$message = array(
					"embeds" => array([
						"title" => "Verification Key Request",
						"description" => "Hello! We've received an automated request for a verification key on **1.8 GDPS** from `" . substr($gs->getAccountName($accountID), 0, 3) . "*****`.",
						"color" => hexdec("FDD938"),
						"footer" => [
							"text" => "Verification keys only last for 15 minutes. If you did not request this verification key, please ignore this message."
						],
						"fields" => [
							[
								"name" => "Your verification key",
								"value" => "Your verification key is: **" . $gs->generateDiscordVerificationKey($accountID, $discordID) . "**"
							],
							[
								"name" => "What should I do?",
								"value" => "You may now use this verification key to perform level commands in comment sections."
							],
							[
								"name" => "One more thing!",
								"value" => "For the purpose of executing commands, you may only use each verification key __3__ times before it is expired. Choose your moves wisely!"
							],
							[
								"name" => "Anything else?",
								"value" => "Please be aware that generation of new Discord verification keys will instantly expire your older keys."
							]
						]
					])
				);
				$gs->sendDiscordPM($discordID, $message);
				self::createBotComment("Command info: A verification key has been sent to '" . substr($gs->getDiscordUsername($discordID), 0, 3) . "*****'.", $userID, $levelID);
				return false;
			}
			$verifyKey = $query->fetchColumn();
			if (!substr_count($comment, $verifyKey)) {
				self::createBotComment("Command error: Incorrect or no verification key in provided comment. Please try again.", $userID, $levelID);
				return false;
			}

			$comment = str_replace($verifyKey, "", $comment);
			$gs->useAnyVerificationKey($accountID, $verifyKey);
			$commentarray = explode(' ', $comment);
			return true;
		};

		// ADMIN COMMANDS
		if(substr($comment,0,5) == '!rate' AND $gs->checkPermission($accountID, "commandRate")){
			if (!$verificationCheck())
				return true;
			$starStars = $commentarray[2];
			if($starStars == ""){
				$starStars = 0;
			}
			$starCoins = $commentarray[3];
			$starFeatured = $commentarray[4];
			$diffArray = $gs->getDiffFromName($commentarray[1]);
			$starDemon = $diffArray[1];
			$starAuto = $diffArray[2];
			$starDifficulty = $diffArray[0];
			$query = $db->prepare("UPDATE levels SET starStars=:starStars, starDifficulty=:starDifficulty, starDemon=:starDemon, starAuto=:starAuto, rateDate=:timestamp WHERE levelID=:levelID");
			$query->execute([':starStars' => $starStars, ':starDifficulty' => $starDifficulty, ':starDemon' => $starDemon, ':starAuto' => $starAuto, ':timestamp' => $uploadDate, ':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value2, value3, timestamp, account) VALUES ('1', :value, :value2, :levelID, :timestamp, :id)");
			$query->execute([':value' => $commentarray[1], ':timestamp' => $uploadDate, ':id' => $accountID, ':value2' => $starStars, ':levelID' => $levelID]);
			if($starFeatured != ""){
				$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('2', :value, :levelID, :timestamp, :id)");
				$query->execute([':value' => $starFeatured, ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);	
				$query = $db->prepare("UPDATE levels SET starFeatured=:starFeatured WHERE levelID=:levelID");
				$query->execute([':starFeatured' => $starFeatured, ':levelID' => $levelID]);
			}
			if($starCoins != ""){
				$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('3', :value, :levelID, :timestamp, :id)");
				$query->execute([':value' => $starCoins, ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
				$query = $db->prepare("UPDATE levels SET starCoins=:starCoins WHERE levelID=:levelID");
				$query->execute([':starCoins' => $starCoins, ':levelID' => $levelID]);
			}
			return true;
		} elseif(substr($comment,0,8) == '!feature' AND $gs->checkPermission($accountID, "commandFeature")){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("UPDATE levels SET starFeatured='1' WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('2', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => "1", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(substr($comment,0,6) == '!delet' AND $gs->checkPermission($accountID, "commandDelete")){
			if (!$verificationCheck())
				return true;
			if(!is_numeric($levelID)){
				return true;
			}
			$query = $db->prepare("DELETE from levels WHERE levelID=:levelID LIMIT 1");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('6', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => "1", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			if(file_exists(dirname(__FILE__)."../../data/levels/$levelID")){
				rename(dirname(__FILE__)."../../data/levels/$levelID",dirname(__FILE__)."../../data/levels/deleted/$levelID");
			}
			return true;
		} elseif(substr($comment,0,7) == '!setacc' AND $gs->checkPermission($accountID, "commandSetacc")){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("SELECT accountID FROM accounts WHERE userName = :userName OR accountID = :userName LIMIT 1");
			$query->execute([':userName' => $commentarray[1]]);
			if($query->rowCount() == 0){
				return true;
			}
			$targetAcc = $query->fetchColumn();
			$targetUserID = $gs->getUserID($targetAcc);
			$query = $db->prepare("UPDATE levels SET extID=:extID, userID=:userID, userName=:userName WHERE levelID=:levelID");
			$query->execute([':extID' => $targetAcc, ':userID' => $targetUserID, ':userName' => $commentarray[1], ':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('7', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => $commentarray[1], ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif (substr($comment, 0, 13) == '!setlinknexus' AND $gs->checkPermission($accountID, "commandSetacc")) {
			if (!$verificationCheck())
				return true;
			if (empty($commentarray[1]) or !is_numeric($commentarray[1]))
				$linkNexusID = $levelID;
			else
				$linkNexusID = $commentarray[1];
			$gs->setLinkNexusLevel($linkNexusID);
			self::createBotComment("!setlinknexus success: The link nexus has been set to level ID '" . $linkNexusID . "'.", $userID, $levelID);
			return true;
		}

		//LEVELINFO
		$query2 = $db->prepare("SELECT extID FROM levels WHERE levelID = :id");
		$query2->execute([':id' => $levelID]);
		$targetExtID = $query2->fetchColumn();
		
		// NON-ADMIN COMMANDS
		if(self::ownCommand($comment, "rename", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$name = ExploitPatch::remove(str_replace("!rename ", "", $comment));
			$query = $db->prepare("UPDATE levels SET levelName=:levelName WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID, ':levelName' => $name]);
			$query = $db->prepare("INSERT INTO modactions (type, value, timestamp, account, value3) VALUES ('8', :value, :timestamp, :id, :levelID)");
			$query->execute([':value' => $name, ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(self::ownCommand($comment, "pass", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$pass = ExploitPatch::remove(str_replace("!pass ", "", $comment));
			if(is_numeric($pass)){
				$pass = sprintf("%06d", $pass);
				if($pass == "000000"){
					$pass = "";
				}
				$pass = "1".$pass;
				$query = $db->prepare("UPDATE levels SET password=:password WHERE levelID=:levelID");
				$query->execute([':levelID' => $levelID, ':password' => $pass]);
				$query = $db->prepare("INSERT INTO modactions (type, value, timestamp, account, value3) VALUES ('9', :value, :timestamp, :id, :levelID)");
				$query->execute([':value' => $pass, ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
				return true;
			}
		} elseif(self::ownCommand($comment, "song", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$song = ExploitPatch::remove(str_replace("!song ", "", $comment));
			if(is_numeric($song)){
				$query = $db->prepare("UPDATE levels SET songID=:song WHERE levelID=:levelID");
				$query->execute([':levelID' => $levelID, ':song' => $song]);
				$query = $db->prepare("INSERT INTO modactions (type, value, timestamp, account, value3) VALUES ('16', :value, :timestamp, :id, :levelID)");
				$query->execute([':value' => $song, ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
				return true;
			}
		} elseif(self::ownCommand($comment, "description", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$desc = base64_encode(ExploitPatch::remove(str_replace("!description ", "", $comment)));
			$query = $db->prepare("UPDATE levels SET levelDesc=:desc WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID, ':desc' => $desc]);
			$query = $db->prepare("INSERT INTO modactions (type, value, timestamp, account, value3) VALUES ('13', :value, :timestamp, :id, :levelID)");
			$query->execute([':value' => $desc, ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(self::ownCommand($comment, "public", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("UPDATE levels SET unlisted='0' WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('12', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => "0", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(self::ownCommand($comment, "unlist", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("UPDATE levels SET unlisted='1' WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('12', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => "1", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(self::ownCommand($comment, "sharecp", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("SELECT userID FROM users WHERE userName = :userName ORDER BY isRegistered DESC LIMIT 1");
			$query->execute([':userName' => $commentarray[1]]);
			$targetAcc = $query->fetchColumn();
			$query = $db->prepare("INSERT INTO cpshares (levelID, userID) VALUES (:levelID, :userID)");
			$query->execute([':userID' => $targetAcc, ':levelID' => $levelID]);
			$query = $db->prepare("UPDATE levels SET isCPShared='1' WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('11', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => $commentarray[1], ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(self::ownCommand($comment, "ldm", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("UPDATE levels SET isLDM='1' WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('14', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => "1", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		} elseif(self::ownCommand($comment, "unldm", $accountID, $targetExtID)){
			if (!$verificationCheck())
				return true;
			$query = $db->prepare("UPDATE levels SET isLDM='0' WHERE levelID=:levelID");
			$query->execute([':levelID' => $levelID]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account) VALUES ('14', :value, :levelID, :timestamp, :id)");
			$query->execute([':value' => "0", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID]);
			return true;
		}
		return false;
	}
	public static function doLinkNexusCommands($UDID, $legacyID, $comment) {
		include dirname(__FILE__) . "/../lib/connection.php";
		require_once "../lib/exploitPatch.php";
		require_once "../lib/mainLib.php";
		$gs = new mainLib();
		
		require "../../config/linking.php";
		require "../../config/discord.php";
		$userID = $legacyID ? $gs->getUserID($legacyID) : $gs->getUserID($UDID);
		$commentarray = explode(' ', $comment);
		
		if (substr($comment, 0, 5) == '!link') {
			if ($legacyID) {
				self::createBotComment("!link error: You are already linked to '" . substr($gs->getAccountName($legacyID), 0, 3) . "*****'! Please use '!relink' instead.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[1])) {
				self::createBotComment("!link error: Please specify the account's username (or ID) to link with.", $userID, $linkNexusLevel);
				return false;
			}
			
			$query = $db->prepare("SELECT accountID FROM accounts WHERE userName = :userName OR accountID = :userName LIMIT 1");
			$query->execute([':userName' => $commentarray[1]]);
			if ($query->rowCount() == 0) {
				self::createBotComment("!link error: The specified account does not exist. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			$newLegacyID = $query->fetchColumn();
			
			// verifying the key
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '30' AND timestamp > :timestamp AND account = :accountID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $newLegacyID]); // last generated key from 15 minutes ago that has not been used already
			// it makes more sense to inform the user first that they do not even have any active keys, than to go straight to asking for one
			if ($query->rowCount() == 0) {
				self::createBotComment("!link error: Please generate a verification key on your account before proceeding.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[2])) {
				self::createBotComment("!link error: Please provide your verification key to link your account.", $userID, $linkNexusLevel);
				return false;
			}
			$verifyKey = $query->fetchColumn();
			if (!($commentarray[2] == $verifyKey)) {
				self::createBotComment("!link error: Verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			
			$oldUDID = $gs->getLegacyExtID($newLegacyID);
			if ($oldUDID) {
				// ask for confirmation before moving link
				if (!($commentarray[3] == "-confirm")) {
					self::createBotComment("!link error: Another user is already linked to this account. Use the '-confirm' argument to proceed.", $userID, $linkNexusLevel);
					return false;
				}
				
				// since (assumingly) the old UDID + userID cannot be used by the client anymore (unless you deploy some trickery)...
				
				$legacyUserName = $gs->getAccountName($newLegacyID);
				$oldLegacyUserID = $gs->getUserID($newLegacyID);
				// move levels and comments to new userID and update usernames
				$query = $db->prepare("UPDATE levels SET userID = :userID, userName = :userName WHERE userID = :oldUserID");
				$query->execute([':userID' => $userID, ':userName' => $legacyUserName, ':oldUserID' => $oldLegacyUserID]);
				$query = $db->prepare("UPDATE comments SET userID = :userID, userName = :userName WHERE userID = :oldUserID");
				$query->execute([':userID' => $userID, ':userName' => $legacyUserName, ':oldUserID' => $oldLegacyUserID]);
				// swap UDID in users with linked account's ID and also update the username
				$query = $db->prepare("UPDATE users SET extID = :extID, userName = :userName WHERE userID = :userID");
				$query->execute([':extID' => $newLegacyID, ':userName' => $legacyUserName, ':userID' => $userID]);
				// swap the old UDID with the new one in user links and reset the Discord link (effectively phasing out the old UDID from having any links)
				$query = $db->prepare("UPDATE userLinks SET extID = :UDID, discordID = '0' WHERE extID = :oldUDID");
				$query->execute([':UDID' => $UDID, ':oldUDID' => $oldUDID]);
				
				// consume key and send success response
				$gs->useVerificationKey($newLegacyID, $verifyKey);
				self::createBotComment("!link success: The linking to the old user with '" . $gs->getAccountName($newLegacyID) . "' was moved to the current one.", $userID, $linkNexusLevel);
			} else {
				$legacyUserName = $gs->getAccountName($newLegacyID);
				// swap UDID in users with linked account's ID and also update the username
				$query = $db->prepare("UPDATE users SET extID = :extID, userName = :userName WHERE userID = :userID");
				$query->execute([':extID' => $newLegacyID, ':userName' => $legacyUserName, ':userID' => $userID]);
				// update levels (and assumingly ones uploaded before applying the linking system) with newly linked account's ID and comments with the new username
				$query = $db->prepare("UPDATE levels SET extID = :extID, userName = :userName WHERE userID = :userID");
				$query->execute([':extID' => $newLegacyID, ':userName' => $legacyUserName, ':userID' => $userID]);
				$query = $db->prepare("UPDATE comments SET userName = :userName WHERE userID = :userID");
				$query->execute([':userName' => $legacyUserName, ':userID' => $userID]);
				// create new user link
				$query = $db->prepare("INSERT INTO userLinks (extID, accountID) VALUES (:UDID, :newLegacyID)");
				$query->execute([':UDID' => $UDID, ':newLegacyID' => $newLegacyID]);
				
				// consume key and send success response
				$gs->useVerificationKey($newLegacyID, $verifyKey);
				self::createBotComment("!link success: Your current user has successfully been linked to '" . $legacyUserName . "'.", $userID, $linkNexusLevel);
			}
		} elseif (substr($comment, 0, 8) == '!dsclink' OR substr($comment, 0, 12) == '!discordlink') {
			if (!$discordEnabled) {
				self::createBotComment("!dsclink error: Discord functionality is currently disabled on this server.", $userID, $linkNexusLevel);
				return false;
			}
			if (!$bottoken) {
				self::createBotComment("!dsclink error: No Discord bot token is configured for the server. Please contact the server administrator(s).", $userID, $linkNexusLevel);
				return false;
			}
			if (!$legacyID) {
				self::createBotComment("!dsclink error: Please link a game account first before continuing.", $userID, $linkNexusLevel);
				return false;
			}
			$legacyDiscordID = $gs->getLegacyDiscordIDFromAcc($legacyID);
			if ($legacyDiscordID) {
				self::createBotComment("!dsclink error: You are already linked to Discord '" . substr($gs->getDiscordUsername($legacyDiscordID), 0, 3) . "*****'! Please use '!dscrelink' instead.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[1])) {
				self::createBotComment("!dsclink error: Please specify the Discord account's ID (or username) to link with.", $userID, $linkNexusLevel);
				return false;
			}
			if (is_numeric($commentarray[1])) {
				$discordID = $commentarray[1];
				$discordUsername = $gs->getDiscordUsername($discordID);
				if (!$discordUsername) {
					self::createBotComment("!dsclink error: Unable to find a Discord account with the specified ID. Please try again.", $userID, $linkNexusLevel);
					return false;
				}
			} else {
				$discordID = $gs->getDiscordIDByName($commentarray[1]);
				if (!$discordID) {
					// I personally recommend you copy paste the line of code below, and leave a second comment informing about joining the Discord guild configured in config/linking.php
					self::createBotComment("!dsclink error: Unable to find a Discord account with the specified username. Please try again.", $userID, $linkNexusLevel);
					return false;
				}
				$discordUsername = $commentarray[1];
			}
			
			// verifying the key
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '31' AND timestamp > :timestamp AND account = :accountID AND value2 = :discordID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $legacyID, ':discordID' => $discordID]); // last generated Discord key from 15 minutes ago that has not been used already
			if ($query->rowCount() == 0) {
				// initially I wanted to do this two times to generate keys for the Discord account, once in the check below this one and once here
				// but then I realized that it makes more sense to first check if the Discord account even has any active verification keys
				// ...than to just straight up tell em that they need to provide a verification key to perform the linkage.
				$message = array(
					"embeds" => array([
						"title" => "Link Verification Key Request",
						"description" => "Hello! We've received an automated request for a verification key to link with this Discord account on **1.8 GDPS** from `" . substr($gs->getAccountName($legacyID), 0, 3) . "*****`.",
						"color" => hexdec("FDD938"),
						"footer" => [
							"text" => "Verification keys only last for 15 minutes. If you did not request this verification key, please ignore this message."
						],
						"fields" => [
							[
								"name" => "Your verification key",
								"value" => "Your verification key is: **" . $gs->generateDiscordVerificationKey($legacyID, $discordID) . "**"
							],
							[
								"name" => "What should I do?",
								"value" => "Use your verification key in the 'Link Nexus' level to link your current in-game user to your Discord account."
							]
						]
					])
				);
				$gs->sendDiscordPM($discordID, $message);
				self::createBotComment("!dsclink info: A verification key has been sent to '" . substr($discordUsername, 0, 3) . "*****'.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[2])) {
				self::createBotComment("!dsclink error: Please provide your Discord verification key to link your account.", $userID, $linkNexusLevel);
				return false;
			}
			$verifyKey = $query->fetchColumn();
			if (!($commentarray[2] == $verifyKey)) {
				self::createBotComment("!dsclink error: Verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			
			$oldLegacyID = $gs->getLegacyAccountIDFromDiscord($discordID);
			if ($oldLegacyID) {
				// verify key from old Discord link owner
				$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '30' AND timestamp > :timestamp AND account = :accountID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
				$query->execute([':timestamp' => time() - 900, ':accountID' => $oldLegacyID]); // last generated key from 15 minutes ago that has not been used already
				if ($query->rowCount() == 0) {
					self::createBotComment("!dsclink error: Please generate a verification key on the old Discord link owner account before proceeding.", $userID, $linkNexusLevel);
					return false;
				}
				if (!isset($commentarray[3])) {
					self::createBotComment("!dsclink error: Please provide a verification key from '" . substr($gs->getAccountName($oldLegacyID), 0, 3) . "*****' to move your Discord linkage." , $userID, $linkNexusLevel);
					return false;
				}
				$oldVerifyKey = $query->fetchColumn();
				if (!($commentarray[3] == $oldVerifyKey)) {
					self::createBotComment("!dsclink error: Old Discord link owner's verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
					return false;
				}
				
				// remove the Discord link from the old linked account and consume the key 
				$query = $db->prepare("UPDATE userLinks SET discordID = '0' WHERE accountID = :oldLegacyID");
				$query->execute([':oldLegacyID' => $oldLegacyID]);
				// I always tend to put consumption of keys at the very end of the code, but here we first delink the Discord from the old owner
				// before using the key for it, so there's no problem we should be worried about
				$gs->useVerificationKey($oldLegacyID, $oldVerifyKey);
				
				$botComment = "!dsclink success: The linking to the old user with '" . $discordUsername . "' was moved to the current one.";
			} else {
				$botComment = "!dsclink success: Your current user has successfully been linked to '" . $discordUsername . "'.";
			}
			// add the new discord ID in user links
			$query = $db->prepare("UPDATE userLinks SET discordID = :discordID WHERE accountID = :legacyID");
			$query->execute([':discordID' => $discordID, ':legacyID' => $legacyID]);
			
			// consume key and send success response
			$gs->useDiscordVerificationKey($legacyID, $discordID, $verifyKey);
			self::createBotComment($botComment, $userID, $linkNexusLevel);
		} elseif (substr($comment, 0, 7) == '!relink') {
			if (!$legacyID) {
				self::createBotComment("!relink error: You currently have no links to an account! Please link one with '!link'.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[1])) {
				self::createBotComment("!relink error: Please specify the account's username (or ID) to change your link with.", $userID, $linkNexusLevel);
				return false;
			}
			
			$query = $db->prepare("SELECT accountID FROM accounts WHERE userName = :userName OR accountID = :userName LIMIT 1");
			$query->execute([':userName' => $commentarray[1]]);
			if ($query->rowCount() == 0) {
				self::createBotComment("!relink error: The specified account does not exist. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			$newLegacyID = $query->fetchColumn();
			if ($newLegacyID == $legacyID) {
				self::createBotComment("!relink error: You are already linked to the specified account.", $userID, $linkNexusLevel);
				return false;
			}
			
			// fetching key for the new account
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '30' AND timestamp > :timestamp AND account = :accountID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $newLegacyID]); // last generated key from 15 minutes ago that has not been used already
			if ($query->rowCount() == 0) {
				self::createBotComment("!relink error: Please generate a verification key on the new account before proceeding.", $userID, $linkNexusLevel);
				return false;
			}
			$newVerifyKey = $query->fetchColumn();
			
			// fetching key for the old account
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '30' AND timestamp > :timestamp AND account = :accountID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $legacyID]); // last generated key from 15 minutes ago that has not been used already
			if ($query->rowCount() == 0) {
				self::createBotComment("!relink error: Please generate a verification key on the old account before proceeding.", $userID, $linkNexusLevel);
				return false;
			}
			$oldVerifyKey = $query->fetchColumn();
			
			// error checks for the fetched verification keys
			if (!isset($commentarray[2])) {
				self::createBotComment("!relink error: Please provide your new account's verification key to link with it.", $userID, $linkNexusLevel);
				return false;
			}
			if (!($commentarray[2] == $newVerifyKey)) {
				self::createBotComment("!relink error: New account's verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[3])) {
				self::createBotComment("!relink error: Please provide your old account's verification key to unlink from it.", $userID, $linkNexusLevel);
				return false;
			}
			if (!($commentarray[3] == $oldVerifyKey)) {
				self::createBotComment("!relink error: Old account's verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			
			$legacyUserName = $gs->getAccountName($newLegacyID);
			// swap old account's ID in users with new account's ID and also update the username
			$query = $db->prepare("UPDATE users SET extID = :extID, userName = :userName WHERE userID = :userID");
			$query->execute([':extID' => $newLegacyID, ':userName' => $legacyUserName, ':userID' => $userID]);
			// update levels with new account's ID and comments with the new username
			$query = $db->prepare("UPDATE levels SET extID = :extID, userName = :userName WHERE userID = :userID");
			$query->execute([':extID' => $newLegacyID, ':userName' => $legacyUserName, ':userID' => $userID]);
			$query = $db->prepare("UPDATE comments SET userName = :userName WHERE userID = :userID");
			$query->execute([':userName' => $legacyUserName, ':userID' => $userID]);
			// update user link with new account's ID
			$query = $db->prepare("UPDATE userLinks SET accountID = :accountID WHERE extID = :UDID");
			$query->execute([':accountID' => $newLegacyID, ':UDID' => $UDID]);
			
			// consume both keys and send success response
			$gs->useVerificationKey($newLegacyID, $newVerifyKey);
			$gs->useVerificationKey($oldLegacyID, $oldVerifyKey);
			self::createBotComment("!relink success: Your current user has successfully been linked to '" . $legacyUserName . "'.", $userID, $linkNexusLevel);
		} elseif (substr($comment, 0, 10) == '!dscrelink' OR substr($comment, 0, 14) == '!discordrelink') {
			if (!$discordEnabled) {
				self::createBotComment("!dscrelink error: Discord functionality is currently disabled on this server.", $userID, $linkNexusLevel);
				return false;
			}
			if (!$bottoken) {
				self::createBotComment("!dscrelink error: No Discord bot token is configured for the server. Please contact the server administrator(s).", $userID, $linkNexusLevel);
				return false;
			}
			if (!$legacyID) {
				self::createBotComment("!dscrelink error: Please link a game account first before continuing.", $userID, $linkNexusLevel);
				return false;
			}
			$oldDiscordID = $gs->getLegacyDiscordIDFromAcc($legacyID);
			if (!$oldDiscordID) {
				self::createBotComment("!dscrelink error: You currently have no links to a Discord account. Please link one with '!dsclink'.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[1])) {
				self::createBotComment("!dscrelink error: Please specify the Discord account's ID (or username) to change your link with.", $userID, $linkNexusLevel);
				return false;
			}
			if (is_numeric($commentarray[1])) {
				$newDiscordID = $commentarray[1];
				$newDiscordUsername = $gs->getDiscordUsername($newDiscordID);
				if (!$newDiscordUsername) {
					self::createBotComment("!dscrelink error: Unable to find a Discord account with the specified ID. Please try again.", $userID, $linkNexusLevel);
					return false;
				}
			} else {
				$newDiscordID = $gs->getDiscordIDByName($commentarray[1]);
				if (!$newDiscordID) {
					// I personally recommend you copy paste the line of code below, and leave a second comment informing about joining the Discord guild configured in config/linking.php
					self::createBotComment("!dscrelink error: Unable to find a Discord account with the specified username. Please try again.", $userID, $linkNexusLevel);
					return false;
				}
				$newDiscordUsername = $commentarray[1];
			}
			if ($newDiscordID == $oldDiscordID) {
				self::createBotComment("!dscrelink error: You are already linked to the specified Discord account.", $userID, $linkNexusLevel);
				return false;
			}
			
			// creating a verification key for the new Discord account if there are none
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '31' AND timestamp > :timestamp AND account = :accountID AND value2 = :discordID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $legacyID, ':discordID' => $newDiscordID]); // last generated new Discord key from 15 minutes ago that has not been used already
			if ($query->rowCount() == 0) {
				$message = array(
					"embeds" => array([
						"title" => "Link Verification Key Request",
						"description" => "Hello! We've received an automated request for a verification key to link this Discord account on **1.8 GDPS** from `" . substr($gs->getAccountName($legacyID), 0, 3) . "*****`.",
						"color" => hexdec("FDD938"),
						"footer" => [
							"text" => "Verification keys only last for 15 minutes. If you did not request this verification key, please ignore this message."
						],
						"fields" => [
							[
								"name" => "Your verification key",
								"value" => "Your verification key is: **" . $gs->generateDiscordVerificationKey($legacyID, $newDiscordID) . "**"
							],
							[
								"name" => "What should I do?",
								"value" => "Use your verification key in the 'Link Nexus' level to link your current in-game user to your new Discord account."
							]
						]
					])
				);
				$gs->sendDiscordPM($newDiscordID, $message);
				self::createBotComment("!dscrelink info: A verification key has been sent to '" . substr($newDiscordUsername, 0, 3) . "*****' (new Discord account).", $userID, $linkNexusLevel);
				return false;
			}
			$newVerifyKey = $query->fetchColumn();
			
			// also creating one for the old Discord account if there are none
			$query = $db->prepare("SELECT A.value FROM (SELECT value, value3 FROM actions WHERE type = '31' AND timestamp > :timestamp AND account = :accountID AND value2 = :discordID ORDER BY timestamp DESC LIMIT 1) as A WHERE A.value3 = '0'");
			$query->execute([':timestamp' => time() - 900, ':accountID' => $legacyID, ':discordID' => $oldDiscordID]); // last generated old Discord key from 15 minutes ago that has not been used already
			if ($query->rowCount() == 0) {
				$message = array(
					"embeds" => array([
						"title" => "Unlink Verification Key Request",
						"description" => "Hello! We've received an automated request for a verification key to unlink this Discord account on **1.8 GDPS** from `" . substr($gs->getAccountName($legacyID), 0, 3) . "*****`.",
						"color" => hexdec("FDD938"),
						"footer" => [
							"text" => "Verification keys only last for 15 minutes. If you did not request this verification key, please ignore this message."
						],
						"fields" => [
							[
								"name" => "Your verification key",
								"value" => "Your verification key is: **" . $gs->generateDiscordVerificationKey($legacyID, $oldDiscordID) . "**"
							],
							[
								"name" => "What should I do?",
								"value" => "Use your verification key in the 'Link Nexus' level to unlink your current in-game user from your old Discord account."
							]
						]
					])
				);
				$gs->sendDiscordPM($oldDiscordID, $message);
				self::createBotComment("!dscrelink info: A verification key has been sent to '" . substr($gs->getDiscordUsername($oldDiscordID), 0, 3) . "*****' (old Discord account).", $userID, $linkNexusLevel);
				return false;
			}
			$oldVerifyKey = $query->fetchColumn();
			
			// error checks for the fetched verification keys
			if (!isset($commentarray[2])) {
				self::createBotComment("!dscrelink error: Please provide your new Discord's verification key to link with it.", $userID, $linkNexusLevel);
				return false;
			}
			if (!($commentarray[2] == $newVerifyKey)) {
				self::createBotComment("!dscrelink error: New Discord's verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			if (!isset($commentarray[3])) {
				self::createBotComment("!dscrelink error: Please provide your old Discord's verification key to unlink from it.", $userID, $linkNexusLevel);
				return false;
			}
			if (!($commentarray[3] == $oldVerifyKey)) {
				self::createBotComment("!dscrelink error: Old Discord's verification key is incorrect. Please try again.", $userID, $linkNexusLevel);
				return false;
			}
			
			// apply the new discord ID in user links
			$query = $db->prepare("UPDATE userLinks SET discordID = :discordID WHERE accountID = :legacyID");
			$query->execute([':discordID' => $newDiscordID, ':legacyID' => $legacyID]);
			
			// consume both keys and send success response
			$gs->useDiscordVerificationKey($legacyID, $newDiscordID, $newVerifyKey);
			$gs->useDiscordVerificationKey($legacyID, $oldDiscordID, $oldVerifyKey);
			self::createBotComment("!dscrelink success: Your current user has successfully been linked to '" . $newDiscordUsername . "'.", $userID, $linkNexusLevel);
		} elseif (substr($comment, 0, 8) == '!sendkey' OR substr($comment, 0, 8) == '!senddsc' OR substr($comment, 0, 9) == '!senddisc') {
			if (!$discordEnabled) {
				self::createBotComment("!sendkey error: Discord functionality is currently disabled on this server.", $userID, $linkNexusLevel);
				return false;
			}
			if (!$bottoken) {
				self::createBotComment("!sendkey error: No Discord bot token is configured for the server. Please contact the server administrator(s).", $userID, $linkNexusLevel);
				return false;
			}
			$discordID = $gs->getLegacyDiscordIDFromAcc($legacyID);
			if (!$discordID) {
				self::createBotComment("!sendkey error: You do not have a linked Discord account for this command.", $userID, $linkNexusLevel);
				return false;
			}
			$message = array(
				"embeds" => array([
					"title" => "Verification Key Request",
					"description" => "Hello! We've received a request for a verification key on **1.8 GDPS** from `" . substr($gs->getAccountName($legacyID), 0, 3) . "*****`.",
					"color" => hexdec("FDD938"),
					"footer" => [
						"text" => "Verification keys only last for 15 minutes. If you did not request this verification key, please ignore this message."
					],
					"fields" => [
						[
							"name" => "Your verification key",
							"value" => "Your verification key is: **" . $gs->generateDiscordVerificationKey($legacyID, $discordID) . "**"
						],
						[
							"name" => "What should I do?",
							"value" => "You may now use this verification key to perform level commands in comment sections."
						],
						[
							"name" => "One more thing!",
							"value" => "For the purpose of executing commands, you may only use each verification key __3__ times before it is expired. Choose your moves wisely!"
						],
						[
							"name" => "Anything else?",
							"value" => "Please be aware that generation of new Discord verification keys will instantly expire your older keys."
						]
					]
				])
			);
			$gs->sendDiscordPM($discordID, $message);
			self::createBotComment("!sendkey success: A verification key has been sent to '" . substr($gs->getDiscordUsername($discordID), 0, 3) . "*****'.", $userID, $linkNexusLevel);
		} else {
			return false;
		}
		return true;
	}
}
?>
