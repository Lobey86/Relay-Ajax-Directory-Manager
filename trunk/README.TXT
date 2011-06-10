*** REQUIRED APPLICATIONS ***

You will need Apache2, MySQL, php5, gd, imagemagick, 
ghostscript, unzip, and mod-perl2.

On a debian or debian derivative:

sudo apt-get update
sudo apt-get install apache2 mysql-server php5 php5-gd imagemagick gs-common unzip libabpache2-mod-perl2


*** MYSQL DATABASE ***

You will need a database for Relay, to store user authentication.

mysql -u root -p
create database relay;
grant all privileges on relay.* to 'relayuser'@'localhost' identified by 'relaypassword';

PLEASE change the username and password as you see fit - don't create 
unnecessary security holes!


*** SETTING PERMISSIONS ***

We will assume that "www-data" is your Apache user.  If not, change as necessary.

sudo chown www-data relay/
sudo chown www-data relay/uploads/
sudo chown www-data relay/filestore/
sudo chown www-data relay/upload.pl
sudo chmod 755 relay/
sudo chmod 755 relay/upload.pl
sudo chmod 755 relay/uploads/
sudo chmod 755 relay/filestore/


*** CONFIGURING APACHE2 ***

In the vhost container for the site which will contain relay, you will need
to grant the relay folder +ExecCGI and AllowOverride All.

<Directory "/path/to/relay/">
   AllowOverride All
   Options +ExecCGI
   AddHandler cgi-script cgi pl
</Directory>

Remember to reload Apache after making the changes to your vhost.


*** INITIAL CONFIGURATION OF RELAY ***

Open a web browser, and browse to the Relay URL.  Enter all the information
above - db name, db username, etc.  Also create a username and password for
the Relay admin user.

In the Utilities section, you'll want to enter the location of GhostScript
and ImageMagick binaries.  On most Debian or Debian-derivative servers, that
will be:

  GhostScript:  /usr/bin/gs
  ImageMagick:  /usr/bin/convert

If in doubt, enter "which gs" and "which convert" on the command line of
your server to find the correct locations.

Once you're done, click "Submit Query" and you are ready to start using Relay!

