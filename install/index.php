<html>
<head>
	<style type="text/css" media="all">

		body { margin:0; padding:0; font-size:10pt; background:white url(../images/headerbar.png) left top repeat-x; font-family:Helvetica, Arial, sans-serif;}
		p { margin:0; padding:0;}
		h1 { color:white; font-size:24pt; margin:60px 0; }
		h2 { color:#606060; font-size:12pt; margin:0; }
		hr {border:0; height:1px; margin:20px 0; background:#7ecc1b; }
		a {color:#ffaa06; font-weight:bold; text-decoration:none;}
		#container { width:600px; position:relative; left:50%; margin-left:-325px; padding-bottom:200px; }
		#header { height:121px; margin-bottom:20px;}
		#blurb {float:left; color:#606060; margin-top:5px; font-size:11pt; line-height:16pt;}
		#body {float:left; margin-top:15px; width:100%; padding:12px; background:white url(../images/body-grad.png) left top repeat-x;}
		#body ul { margin:0 0 0 10px; padding:10px; }
		#body li { padding:3px 0; font-size:14pt; color:#707b65; }
		.left {float:left; width:320px; padding:0 5px 5px; margin-left:10px; }
		.right { float:right; width:254px; }
		#footer { float:left; width:100%; margin-top:100px; text-align:center;}
		#footer p {color:#bbb; }

		td {font-size:9pt;  color:#333;}
		td.note {
			color:#999;
			padding-bottom:15px;
			padding-left:15px;
		}
		td h2 {
			
			margin-top:20px;
		}
		.label {
			text-align:right;
			vertical-align:top;
			padding-top:3px;
			 
		}
		
		input {
			font-family:helvetica, arial, sans-serif;
			font-size:10pt;
			padding:3px;
			background:white;
			margin:0 10px 5px;
			border-left:1px solid #83a5c7; 
			border-top:1px solid #83a5c7; 
			border-bottom:1px solid #d3e1ee;  
			border-right:1px solid #d3e1ee; 
		}
		
		.radio{
			margin:4px 4px 0 0px;
			padding:2px;
		}
		.warning {
			border:1px solid red;
			background: #fff6f6;
			padding:10px;
			margin:0 0 15px 0;
		}
		#installform { margin:0 20px; }
		#installform td img {margin-bottom:5px; }
		.submit {
			border:auto;
		}
	</style>


</head>
<body>

<div id="container">

<div id="header">

		<img src="config.png" alt="Relay ajax directory manager" />
	</div>
<?php

    
	if(isset($_SERVER['ORIG_PATH_TRANSLATED'])){
	    $rootpath =$_SERVER['ORIG_PATH_TRANSLATED'];
	}else if(isset($_SERVER['PATH_TRANSLATED'])){
	    $rootpath = $_SERVER['PATH_TRANSLATED'];
	}else{
	    $rootpath = $_SERVER['SCRIPT_FILENAME'];
	}
	

	
	if(substr($rootpath,0,1) == '/'){
		$path = explode("/","$rootpath"); // "/"
	}else if(substr($rootpath,2,1) == "\\"){
		$path = explode("\\","$rootpath"); // "\"
	}else if(substr($rootpath,2,2) == "\\\\"){
		$path = explode("\\\\","$rootpath"); // "\\"
	}else{
		$path = explode("/","$rootpath");
	}
	
	
	$rootpath = '';
	for($i=0;$i<count($path)-2;$i++){
	    $rootpath .= "$path[$i]/";
	}
	
	if(substr($rootpath,-1,1) == "/"){
	    $rootpath = substr($rootpath,0,strlen($rootpath)-1);
	}
	
	if($rootpath == ''){
		?>
<p class='warning'><strong>Warning</strong><br />
    Your webservers rootpath could not be determined, please post the following to <a href="http://ecosmear.com/relay/wiki/index.php/Bugs">Relay's Wiki</a></p>
    <pre>
    <?php print_r($_SERVER); ?>
    </pre>
    

		<?php
		exit;
	}

    
        if(file_exists("$rootpath/conf.inc.php")){
		
		?>
<p class='warning'><strong>Warning</strong><br />
    Configuration file already detected.  Delete conf.inc.php in Relay's root directory before running this script again.</p>
    

		<?php
		exit;
	}
?>

<?php
    if(isset($_POST['stage2'])){
        
    $database = mysql_connect($_POST['host'],$_POST['relayUser'],$_POST['relayPassword']) || die("Bad database information, press back and try again");
    echo "Database Connected....<br/>";
    /*
    echo "Deleting relay database...<br/>";
    mysql_query("drop database relay");
    
    echo "Creating database relay...";
    
    mysql_query("create database relay");
    */
    
    mysql_select_db($_POST['database'])||die("could not connect to the database $_POST[database]"); echo "done<br/>";
    
    if(function_exists('ldap_connect') & isset($_POST['dc'],$_POST['adu'],$_POST['adp'],$_POST['ads']) & $_POST['uad'] == 'on'){
        echo "Verifying Active Directory installation on $_POST[ads]...";
        
        $activeDirectoryServer = $_POST['ads'];
        $activeDirectoryDC = explode(".",$_POST['dc']);
        
	include_once("inc/adLDAP.php");
        
        $ad = new adLDAP;
        if($ad->authenticate($_POST['adu'],$_POST['adp'])){
            echo "success!<br/>";
            
            $first = true;
            foreach($activeDirectoryDC as $dc){
                    if(!$first){
                            $addc .= ",";
                    }
                    
                    $addc .= "\"$dc\"";
                    $first = false;
            }
            
$ldapConfig = "// activeDirectory
    \$activeDirectoryDC = array($addc);
    \$activeDirectoryServer = \"$_POST[ads]\";
    \$activeDirectoryUser = \"$_POST[adu]\";
    \$activeDirectoryPass = \"$_POST[adp]\";";
        }else{
            echo "FAILED, check your settings and try again.";
            exit;
        }
        
        
    }
    
    echo "Dropping tables if the exist...";
    mysql_query("DROP TABLE `$_POST[pre]clients`, `$_POST[pre]filesystem`, `$_POST[pre]log`, `$_POST[pre]permissions`, `$_POST[pre]users`");
    echo "done</br>";
    
    echo "Creating tables<br/>*$_POST[pre]clients...";
    
    mysql_query("
CREATE TABLE IF NOT EXISTS `$_POST[pre]clients` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `display` text,
  `path` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `pn` (`path`(250),`name`(250))
);
")||die(mysql_error() . " could not create the table clients");

echo "<br/>*$_POST[pre]filesystem...";
mysql_query("
CREATE TABLE  IF NOT EXISTS `$_POST[pre]filesystem` (
  `id` int(11) NOT NULL auto_increment,
  `filename` text NOT NULL,
  `path` longtext NOT NULL,
  `rpath` longtext NOT NULL,
  `type` varchar(32) NOT NULL,
  `downloads` int(11) NOT NULL default '0',
  `status` enum('found','lost') NOT NULL default 'found',
  `uploader` int(11) NOT NULL default '0',
  `flags` enum('hot','emergency','normal') NOT NULL default 'normal',
  `description` longtext,
  `date` timestamp,
  `size` int(11) NOT NULL default '0',
  `thumb` blob,
  `thumbC` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `Path` (`path`(255)),
  FULLTEXT KEY `Search` (`filename`,`description`,`rpath`)
) TYPE=MyISAM AUTO_INCREMENT=100000 ;
")||die(mysql_error() . " could not create the table filesystem");


echo "<br/>*$_POST[pre]log...";
mysql_query("
CREATE TABLE  IF NOT EXISTS `$_POST[pre]log` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `timestamp` timestamp,
  `ip` text NOT NULL,
  `type` text NOT NULL,
  `details` text NOT NULL,
  `user` text,
  PRIMARY KEY  (`id`)
);
")||die(mysql_error() . " could not create the table log");

echo "<br/>*$_POST[pre]permissions...";
mysql_query("
CREATE TABLE  IF NOT EXISTS `$_POST[pre]permissions` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `clientid` int(11) NOT NULL default '0',
  `scheme` enum('read','write','admin','cust') NOT NULL,
  `view` tinyint(1) NOT NULL default '1',
  `rename` tinyint(1) NOT NULL default '1',
  `download` tinyint(1) NOT NULL default '1',
  `metaEdit` tinyint(1) NOT NULL default '1',
  `delete` tinyint(1) NOT NULL default '1',
  `move` tinyint(1) NOT NULL default '1',
  `folderRename` tinyint(1) NOT NULL default '1',
  `folderDelete` tinyint(1) NOT NULL default '1',
  `folderMove` tinyint(1) NOT NULL default '1',
  `newFolder` tinyint(1) NOT NULL default '1',
  `upload` tinyint(1) NOT NULL default '1',
  `admin` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uc` (`userid`,`clientid`)
);
")||die(mysql_error() . " could not create the table permissions");

echo "<br/>*$_POST[pre]users...";
mysql_query("
CREATE TABLE  IF NOT EXISTS `$_POST[pre]users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `email` text,
  `name` text,
  `path` text,
  `admin` tinyint(1) NOT NULL default '0',
  `ADauth` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `user` (`username`(255))
);
")||die(mysql_error() . " could not create the table users");
    echo "<br/>done creating tables<br/>";
    
/*
    echo "Creating database user to use for connection...";
    mysql_query("grant all on relay.* to '$_POST[relayUser]'@localhost IDENTIFIED BY \"$_POST[relayPassword]\"")||die("granting permission failed");
    // create relay user
    echo "done...<br/>";
*/

    /*echo "switching to relay username for database connection....";
    mysql_close();
    $database = mysql_connect($_POST['host'],$_POST['relayUser'],$_POST['relayPassword']) || die("failed");
    echo "success</br>";
    */
    
    $key = md5(rand() * rand());
    echo "generating random password key : $key ... done<br/>";
    echo "creating first relay administrator...";
    
    mysql_query("INSERT INTO `$_POST[pre]users` ( `username` , `password` ,`admin`,`email`,`name` )
        VALUES (
         '$_POST[user]', md5('$key$_POST[password]'), '1', '' , 'Administrator'
        );
") || die(mysql_error());
    echo "done<br/>";
    

    
    
    
    echo "rootpath : $rootpath<br/>";

    echo "setting up first Virtual Directory: $rootpath/filestore....";
    mysql_query("INSERT INTO `$_POST[pre]clients` ( `name` , `display` ,`path` )
        VALUES (
         'filestore', 'First Virtual Directory', '$rootpath'
        );
") || die(mysql_error());
    echo "done<br/>";

    echo "setting up permissions....";
    mysql_query("INSERT INTO `$_POST[pre]permissions` ( `userid` , `clientid` ,`scheme`,`admin` )
        VALUES (
         '1', '1', 'admin','1'
        );
") || die(mysql_error());

    echo "done<br/>";
    
$imageTypes = "imagetypeinit";

if(function_exists("imagecreatefromjpeg")){
    $imageTypes = "image\/jpeg|image\/png";
}else{
    echo "<b>GD2 is not enabled this is NOT recommended, this means you can not thumbnail jpg or pngs</b><br/>";
}
    

if(is_file($_POST['gs'])){
    $imageTypes .= "|application\/pdf|application\/postscript";
    $imageProcessors = "\$ghostScript = \"$_POST[gs]\"; // ghostScript\n";
}else{
    echo "Ghostscript NOT enabled<br/>";
}

if(is_file($_POST['con'])){
    $imageTypes .= "|image\/x-photoshop|image\/";
    $imageProcessors .= "    \$convertpath = \"$_POST[con]\"; // imageMagick convert path";
}else{
    echo "ImageMagik Convert NOT enabled<br/>";
}

echo "thumbnailed image types : $imageTypes<br>";

$conffile = "<?
    \$database = mysql_connect('$_POST[host]','$_POST[relayUser]','$_POST[relayPassword]') or die(\"Database error check conf.inc.php\");
    mysql_select_db('$_POST[database]', \$database);
    \$GLOBALS['tablePrefix'] = \"$_POST[pre]\";
    
    $ldapConfig
    
    // global variables
    \$imageTypes = \"/$imageTypes/\";
    \$dateFormat = \"%b,%e %Y %h:%i %p\";
    \$passwordKey = \"$key\";
	\$thumbnailPrefix = \".relaytn_\";

    // paths
    \$rootPath    = \"$rootpath\";
    \$uploadDir   = \"$rootpath/uploads/\";
    \$defaultFileStore = \"$rootpath/filestore\";
    $imageProcessors
?>
";
    
    $perlUploadConfig = "\$uploadsFolder = \"$rootpath/uploads/\";
    true;
    ";
    
    if(!function_exists("file_put_contents")){
	
	function file_put_contents($file,$data){
	    $f = fopen($file,"w+") or die("$file can not open");
	    fwrite($f,$data);
	    fclose($f);
	}
    }
    
    echo "Generating config files: if creation fales make sure the webserver has permission to write to here : $rootPath... ";
    file_put_contents("$rootpath/conf.inc.php",$conffile);
    file_put_contents("$rootpath/conf.uploader",$perlUploadConfig);
    
    echo "Config Files Created<br/>";
    echo "Verifying Perl Installation... for the upload script @ ";
    if(isset($_SERVER['PATH_INFO'])){$uploadPath = $_SERVER['PATH_INFO'];}
    if(isset($_SERVER['REQUEST_URI'])){$uploadPath = $_SERVER['REQUEST_URI'];}
    $uploadPath = str_replace("install/index.php","upload.pl?test",$uploadPath);

    $uploadPath = "http://$_SERVER[SERVER_NAME]$uploadPath";
    echo $uploadPath . " ...";

    $response = file_get_contents($uploadPath);

    if($response == 'ok'){
        echo "done...<br/>";
    }else{
        echo "the upload.pl script seems to be having problems.  Try chmod 755 to upload.  You may also need to change the 1st line to reflect the path to your perl installation, or in iis map the .pl extension to the perl executable.  You may need to enable mod_cgi for .pl files.  Look at the <a href='http://ecosmear.com/relay/wiki/index.php/Internal_Server_Error'>Relay Wiki</a> on this topic for more troupshooting.";
        exit;
    }
    
    }else{
?>


<?php

error_reporting(0);
if(!function_exists('finfo') and !(function_exists('mime_content_type') and mime_content_type("relay.php") != "")){
    echo "<p class='warning'><strong>Warning</strong><br />
    It is recommended that you enable mime-magic or the fileinfo extention, you can get fileinfo <a href='http://us2.php.net/manual/en/ref.fileinfo.php'>here</a>. <em>http://us2.php.net/manual/en/ref.fileinfo.php</em><br />
    Sometimes it is not possible to get these functions running, so you can still use Relay and file types will be assumed by file extension.</p>";
}

?>

<p id="blurb">This script will create <strong>conf.inc.php</strong> which is Relay's config file.  It will also configure your database and setup initial accounts.  If you have problems refer to <a href='http://ecosmear.com/relay/wiki/index.php/Installation'>Relays Wiki</a></p>

	<div id="body">
	<form action='index.php' method='post'>
	
	<table id="installform" cellspacing="0" cellpadding="0" border="0">
		<tr><td colspan="2"><img src="database.gif" /></td></tr>
		<tr><td class="label" width="150">Database Host:</td><td><input type='text' name='host' value='localhost' /></td></tr>
		<tr><td class="label">Database Name:</td> <td><input type='text' name='database' value='relay' /></td></tr>
		<tr><td class="label">Table Prefix:</td> <td><input type='text' name='pre' value='relay_' /></td></tr>
		<tr><td class="label">Database Username:</td> <td><input type='text' name='relayUser' value='relay' /></td></tr>
		<tr><td class="label">Database Password:</td> <td><input type='password' name='relayPassword' /></td></tr>
		<!-- <tr><td></td><td class="note">* password not masked</td></tr> -->
		<tr><td colspan="2"><hr></td></tr>
		<tr><td colspan="2"><img src="admin.gif" /></td></tr>
		<tr><td class="label">Username:</td> <td><input type='text' name='user' value='admin' /></td></tr>
		<tr><td class="label">Password:</td> <td><input type='password' name='password' /></td></tr>
		<tr><td colspan="2"><hr></td></tr>
		<tr><td colspan="2"><img src="utilities.gif" /></td></tr>
		<tr><td class="label">GhostScript:</td> <td><input type='text' size="55" name='gs' value='gswin32c.exe' /></td></tr>
		<tr><td></td><td class="note">path to the ghostscript executable
		<br/>used to thumbnail pdf's. Download <a href="http://www.ghostscript.com/" target="_new">Here</a>.</td></tr>
		<tr><td class="label">ImageMagick:</td> <td><input type='text' size="55" name='con' value='convert.exe' /></td></tr>
		<tr><td></td><td class="note">full path to ImageMagick's convert executable.<br/>
		used to thumbnail a variety of image types. Download <a href="http://www.imagemagick.org/" target="_new">Here</a><br/>
		</td></tr>
		<tr><td class="label"></td></tr>
	
	
	<?php if(function_exists('ldap_connect')){ ?>
		<tr><td colspan="2"><hr></td></tr>
		<tr><td colspan="2"><img src="ad.gif" /></td></tr>
		<tr><td class="label">Use Active Directory</td> <td style='padding-left:10px'><input class='radio' type='radio' name='uad' value='yes'/>enable<br/><input class='radio' type='radio' name='uad' value='no' checked/>disable</td></tr>
		<tr><td>&nbsp;</td></tr>
		<tr><td class="label">Active Diretory Server:</td> <td><input type='text' name='ads' value='localhost' /></td></tr>
		<tr><td></td><td class="note">this is the ip address or DNS name of you domain controller</td></tr>
		<tr><td class="label">DC:</td> <td><input type='text' name='dc' value='' /></td></tr>
		<tr><td></td><td class="note">this is the suffix at the end of your login name. e.g. <em>mydomain.com</em></td></tr>
		<tr><td class="label">Active Directory<br>Test Login:</td> <td><input type='text' name='adu' /></td></tr>
		<tr><td></td><td class="note">required to verify Active Diretory functionality</td></tr>
		<tr><td class="label">Active Directory<br>Test Password:</td> <td><input type='password' name='adp' /></td></tr>
	
	<?php } else{ ?>
		<tr><td colspan="2"><hr></td></tr>
		<tr><td colspan="2"><img src="ad.gif" /></td></tr>
		<tr><td class="label" colspan="2">Active Directory authentication functions cannot be enables because php is not configured to use ldapm, but that is ok because Relay can use its own authentication system.</td></tr>

	<?php } ?>
			<tr><td colspan="2"><hr /></td></tr>
		<tr><td style="padding-top:20px; text-align:center;"><input type='hidden' name='stage2' value='true'><input class="submit" type='submit' /></td></tr>
	</table>
	
	
	</form>
	</div>

</div>


</body>
</html>

<?php
    }
?>