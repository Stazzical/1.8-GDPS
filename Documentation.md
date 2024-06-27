# Attention!
This source code was never intended to be used outside of a 1.8-based GDPS enviornment!
I am not responsible if you use this and something doesn't work!

# Differences
This edition of GMDprivateServer has been specifically modified to work with a 1.8 private server, and has been fine-tuned to do so.

Though, to achieve this, many new things have been added over that one would not expect once they enter any private server. Below I will explain in technical detail all there is to know about my modifications.

## A linking system
One quite unfortunate thing about every version before 1.9, is that it does not contain any functionality whatsoever regarding authentication of users.

Most identification of different people is done through your **U**nique **D**evice **Id**entifier, shortened to UDID.

Since we cannot directly relate UDIDs to specific people due to no passwords of any kind being shared with us, we need to turn to other solutions. One such I had come up with was linking people to the usual game accounts.

### How does it all work?
The backend was deeply and thoroughly modified in order to properly both associate UDIDs to accounts, and authenticate them whenever necessary.

You will hear the following terms throughout this documentation:
- Account: Accounts are exactly what they used to be, but you cannot use them in-game anymore.
- Link: The association of a UDID to an account
- Linking: The process in which a user creates a "link" for their UDID to their personally created account.
- Link nexus: An online level inside the game which will allow you to perform many actions regarding linking your UDIDs to accounts, while also providing information wherever possible. The ID of it is customizable.
- Verification key: A random string made of English alphabetics and numbers, currently longing at 6 characters (the default for its function). It is used for authenticating users through the linking system.

### How do you create accounts if the version is too old for that?
The registration tool (registerAccount.php) within the account management section of the tools page will let you do just that. You will also automatically receive a verification key on the page, which its use will be explained later on.

### I've created an account. Now what?
In order to link your account, you will need to head to the link nexus set up by the server manager(s). In the comment section of this level, a set of special commands can be performed which are listed and explained further down below in this documentation.

The leaderboards and comment sections will automatically hint you to the link nexus' level ID whilst you have no linked account. Search for this level ID and enter the comment section.

As a server manager, if you had not set any link nexus for your users, interacting with the following list of endpoints will automatically create an empty level and set it as the link nexus for you (or the players):
- getGJScores.php (Viewing leaderboards)
- getGJCreators.php (Viewing creators leaderboard)
- getGJComments.php (Viewing level comments)
- getGJLevels.php (Searching levels)
- uploadGJLevel.php (Uploading levels)
- deleteGJLevelUser.php (Deleting levels)
- deleteGJComment.php (Deleting comments)

If for any reason you feel the need to set another level to the link nexus, you may either use the `!setlinknexus` global command (explained further down below) or modify the value of $linkNexusLevel inside `config/linking.php` like so:
```
$linkNexusLevel = "link_nexus_levelID_here";
```
...with the content inside the double quotation marks being the level ID of the link nexus.

The information and details of the link nexus level may be anything you desire. The comment section, however, will be taken exclusive access to by the server to relay special information to players.

### New commands (link nexus)
There are 4 new commands created dedicated to managing everything related to your account links.
- `!link <account username/ID> <verification key> [-confirm]`

- `!dsclink (alias: !discordlink) <Discord account ID (preferrably)/username> [Discord verification key] [old owner verification key]`

- `!relink <account username/ID> <new verification key> <old verification key>`

- `!dscrelink (alias: !discordrelink) <new Discord account ID (preferrably)/username> [new Discord verification key] [old Discord verification key]`

### Link nexus
The link nexus is where you can perform all the link nexus commands mentioned above.
It also contains info about your current linked accounts, both game and Discord account if you have any of either.

You may only perform linking commands in the comment section of this level. Performing global commands or uploading a non-command comment will not function as the link nexus will be set up exclusively for performing linkage commands by the server.

Up to 9 recent command bot responses will be shown above the info comment. The bottom and last comment will always be reserved for information regarding current linkage status (current game account and Discord account).

### How do you create a link?
The '!link' command allows you to seamlessly link your registred game account to your current in-game user through a verification process with 'verification keys'.

Upon registration, you automatically receive a verification key on the `registerAccount.php` tool. You may use this verification key alongside the username of your account (or the ID if you're a sneaky one) to link your account to your current in-game user. This will allow your access to see leaderboards, post comments on levels, upload levels and more.

Once you perform a link, your data on the server is updated to properly represent your linked account. This means that your uploaded comments and levels (assumingly ones uploaded before you updated to this linking system) will have their extIDs and userNames updated with your linked account's information.

Notes:
- If you miss the time window to use your automatically generated verification key, you may use the generateKey.php tool in the account management section at any time to generate a new key for yourself to use.

- In the case that you lose your old UDID (for any reason), you may still link your new UDID to the same account, but you will be prompted to use the '-confirm' argument to make sure you really want to move everything over to your new UDID. Performing this action will also reassign all your old UDID's uploaded content (levels, comments) to your new UDID.

### What if I don't link my account?
- Leaderboards will display a warning that would ask you to link a game account to your current UDID, effectively denying you from viewing user scores until the linkage is performed.

- You will be denied from uploading any levels without having either an account link or a verification key.

- You will be denied from uploading any comments on any level other than the link nexus.

- Your scores will not be uploaded and displayed on the leaderboards.

### Everything regarding verification keys
Verification keys are what you use for linking your accounts and/or performing different in-game actions. It is a means of authentication by sharing the key with only the person who owns the account on the other side of the server, serving similarly to passwords with the difference of being.

There are two types of verification keys: One for accounts and one for Discord accounts.
You may use both of these types when performing an in-game action, such as uploading a level or executing a command.

All account verification keys are obtained only through the website tools available on the server's tools section.
All Discord verification keys are obtained only by receiving them inside your Discord account's DMs.

For any type of action, only your last ever created key that is from 15 minutes ago will be used.

For linking accounts, you may only use keys that have **NOT** been used before.
Each type of linking will require its own type of verification key, e.g. linking a Discord account will only accept a Discord verification key.

For performing level uploads and executing commands on comment sections, you may use keys that have been used less than three times.
- This system has an unintended side-effect to it where you may manually set the used amount of the key to a negative number to effectively increase the amount of uses a key has for any action, except for linking your account.

### Setup for Discord linking
To be able to make use of that sweet, sweet system to ease everything regarding verification keys, you will first need to configure a few things (as the server manager).

Among the most under-used features of GMDprivateServer, lies the aged and dusty Discord linking functionality, previously done through Cvolton's own made bot and an API inside the tools folder to perform everything on the server's side.

The old API has now been deleted and the functionality has been refreshed and repurposed for our new linking system to ease the flow and add to it. Below is a guide to setting up configurations for the new Discord functionalities.

You will first have to create a Discord bot application on the Discord developer portal and obtain its token. I will not explain that as there are many guides about it on the internet.

Once you have your bot's token, change these values inside config/discord.php like so:
```
$discordEnabled = true;
$bottoken = "this_will_be_your_token";
```
Put your bot's token inside the double quotation marks next to `$bottoken`. You will now be able to successfully link your in-game client to a Discord account.

Additional configuration will also help it work better by letting your bot identify accounts by their username.

Have your GDPS players join a singular Discord server and add your Discord bot to it.
Then, obtain the ID of the Discord server you have and put it inside config/linking.php like so:
```
$gdpsGuildID = "your_gdps_server_id_here";
```
...with the server ID being inside the double quotation marks.

Voila! Your server now works great with all Discord functionality!

### Linking a Discord account
Using the '!dsclink' command, it is possible to extend your contacts on the server further and allow the server to make use of your Discord account by sending you your verification keys, either automatically on some occasions or by your own request.
- Linking a game account is required before you can link a Discord account. What are you gonna need the Discord for if you can't even do much on the server anyway?

The server needs to have had the setup required performed for all the functionality related to Discord to work.

When using this command, preferrably provide your account's ID instead of its username for the best possible results. The Discord bot set up on the server may not be able to find the ID of your account due to limitations.

If you are the server owner, please find the comment where I said to put a bot comment regarding joining your Discord server, inside 'incl/lib/commands.php'.
If you are a player in the server, please make sure to join the server owner's Discord server in the odd case that getting your account's ID is more effort than joining a server.

Performing this command without providing any Discord verification key (and/or after checking that you have no active Discord verification keys) will have the server automatically generate and send one to the specified Discord account's DMs.

If you use this command on a Discord account that is already linked to another user,
you will be required to provide a verification key from the account that currently owns the link to that particular Discord account.

### Use case of a Discord account
For uploading levels and executing commands, it is possible to use any type of verification key (game account or Discord) in order to verify your authenticity.

At times when executing link commands or level commands, the server may detect that you do not currently have any active usable keys for such actions.
At that moment, the server will attempt to send a direct message to your Discord account if you have one, with the message containing a freshly generated verification key for use in your in-game actions.

You may also manually request one using the `!sendkey` command inside the comment section of any level.

### General backend changes
Any endpoint that allows writing and/or modifying content on the server in any way or in any form will now require that the UDID sent to it have a linked game account associated with.

These endpoints include:
- uploadGJLevel.php (Uploading levels)
- deleteGJLevelUser.php (Deleting levels)
- deleteGJComment.php (Deleting comments)

If the endpoint for viewing comments (`getGJComments.php`) receives a userID that does not have a linked account to it, the player will receive a warning about linking their game account and where to find the link nexus alongside the comments.

### Uploading levels
Uploading a level now requires a verification key of any type to be included inside the **description** of the level before uploading any content to the server.
This key can be anywhere inside the description, and as long as it is present your upload will pass.

Don't worry, the key won't ruin the look of your precious description. It's trimmed out afterwards.

### Executing commands
Executing commands has been slightly changed in order to assure of the user's authenticity when executing them.
Before any command is executed, similar to uploading levels, the verification key needs to be inside the comment and after it gets trimmed, your command gets executed as if you did not put the key there.

If you do not have any active keys, the server will attempt to automatically generate a Discord verification key for you if you have a linked Discord account, otherwise it will ask for a manually generated verification key from the generateKey.php tool.

Additionally, to make problem solving easier for players, an all-new system has been implemented, allowing a bot user to automatically send responses to players within comment sections for the player to figure out their problems or notice their successful executions.
This, unfortunately however, does not apply to any of the old commands created by Cvolton. I would welcome any pull requests to spread its implementation if anyone would like to do it.

Every new command that has been added contains a few of these responses for your convenience, most of them informing of your mistakes when performing the command.

Do note that to see the bot comments, you will have to provide a userID to the server in your POST request to getGJComments.php. Not providing one will have the endpoint send you pure comment lists without any bot comments.

### New commands
- `!sendkey (alias: !senddsc, !senddisc)`

Manually generates and sends a Discord verification key for use to your DMs any time that you wish, as long as you have a linked Discord account. No permissions required.

- `!setlinknexus [level ID]`

Sets the link nexus to the provided level ID or the current level if no ID is provided. `commandSetacc` permission (or account with `isAdmin` set to 1) required.

### Minor differences
- Some code styling has been changed and a lot of junk has been cleaned up. Nice work Cvolton!
- A lot of code regarding newer versions were erased to ease the processing load on the server. This is only for 1.8, after all.
- The makeTime function was taken from Dashbox and made use of within comments and level upload timestamps. Such an ancient relic left unused.
- Functions interacting with the Discord API inside mainLib.php have been updated and modified for use.
- An amount of new functions regarding the new linking systems and other new functionality have been added to mainLib.php to be used throughout the backend.

### Missing functionality
- The 'submitGJUserInfo' and 'restoreGJItems' endpoints have been left out due to having little to no documentation or research regarding their whereabouts.

Though I would've personally taken care of researching it, I did not have enough time to bother it. I would welcome any pull requests to properly implement these if they come up however.

- The 'rateGJStars' and 'rateGJLevel' endpoints have no practical use due to no UDIDs being sent to the server to identify the player

I have erased these endpoints and only left files with success responses inside of them to not bug the game clients with the missing processing.