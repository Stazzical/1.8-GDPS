# 1.8 GDPS
Source code for Exenity's 1.8 GDPS, based on Cvolton's GMDprivateServer.
Intended only for use on 1.8, usage with other versions untested but most versions pre-1.9 should be usable.

See all documentation about this edition [here](Documentation.md).

### Basic Instructions
1) Upload the files inside 'src' folder on a webserver
2) Import database.sql into a MySQL/MariaDB database
3) Import sql_upgrade.sql over the database you've created
3) Edit the server endpoints inside your Geometry Dash client to point to your server

### Credits

- GMDprivateServer by Cvolton
- Some code used here is taken from Dashbox/GDOpenServer (now discontinued, public archive available [here](https://github.com/Stazzical/dashbox-old/)), by (mostly) me, ryzzica and Wyliemaster.

### GMDprivateServer Credits
Base for account settings and the private messaging system by someguy28

Using this for XOR encryption - https://github.com/sathoro/php-xor-cipher - (incl/lib/XORCipher.php)

Using this for cloud save encryption - https://github.com/defuse/php-encryption - (incl/lib/defuse-crypto.phar)

Most of the stuff in generateHash.php has been figured out by pavlukivan and Italian APK Downloader, so credits to them
