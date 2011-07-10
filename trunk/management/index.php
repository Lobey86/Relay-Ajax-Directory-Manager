<?php

/**
 *
 *
 * @version $Id$
 * @copyright 2006
 */


include_once("../conf.inc.php");

$key=$passwordKey;

// session initilization
session_start();

if($_SESSION['userid'] == ''){
	echo "You must login";
	exit;
}

function auth($cid=''){
	if($_SESSION['admin'] ==1){
		#echo "a1";
		return true;
	}
	
	if($cid == ''){
		if(count($_SESSION["admin.cid"]) > 0){
			#echo "a2";
			return true;
		}else{
			print_r($_SESSION["admin.cid"]);
			#echo "f1";
			return false;
		}
	}else{
		if($_SESSION["auth.$cid.admin"] == 1){
			#echo "a3";
			return true;
		}else{
			return false;
		}
	}
}



if(isset($_POST)){
	$_GET = array_merge($_GET,$_POST);
}


if(isset($_GET['page'])){
	$options['page'] = mysql_escape_string($_GET['page']);
	$link['page'] = $_GET['page'];
}else{
	$options['page'] = 'default';
}
if(isset($_GET['module'])){
	$options['module'] = mysql_escape_string($_GET['module']);
	$link['module'] = $_GET['module'];
}
if(isset($_GET['action'])){
	$options['action'] = mysql_escape_string($_GET['action']);
	$link['action'] = $_GET['action'];
}
if(isset($_GET['uid'])){
	$options['uid'] = mysql_escape_string($_GET['uid']);
	$link['uid'] = $_GET['uid'];
}
if(isset($_GET['cid'])){
	$options['cid'] = mysql_escape_string($_GET['cid']);
	$link['cid'] = $_GET['cid'];
}
if(isset($_GET['pid'])){
	$options['pid'] = mysql_escape_string($_GET['pid']);
	$link['pid'] = $_GET['pid'];
}
if(isset($_GET['scheme'])){
	$options['scheme'] = mysql_escape_string($_GET['scheme']);
	$link['scheme'] = $_GET['scheme'];
}

function makeLink($options=''){
	global $link;
	if(isset($options) and is_array($options))
		foreach($options as $name=>$value){
			$link[$name]=$value;
		}
	foreach($link as $name=>$value){
		$result .= "$name=$value&";
	}
	return $result;
}


function makeForm($options){
	global $link;
	if(isset($options))
		foreach($options as $name=>$value){
			$link[$name]=$value;
		}
	foreach($link as $name=>$value){
		if($value !='none')$result .= "<input type='hidden' name='$name' value='$value'/>";
	}
	return $result;
}


function display($options){

	/* options = named array

	page:
		default
		manage

	*/

	switch($options['page']){
		case "default":
			#echo "<a href='index.php'>Home</a>";
			defaultpage();
			break;
		case "manage":
			#echo "<a href='index.php'>Home</a>";
			manage($options);
			break;
		case "stats":
			if($_SESSION['admin'] == 1){stats($options);}
			break;
	}

}

function manage($options){

	/*
	options = named array

	module:
		clients
		employees


	module::clients:
		action:
			new
			list
			edit
			delete
		wizzard:
			start

	module::users
		action:
			new
			list
			edit
			delete

	*/
	switch($options['module']){
		case "clients":
			#echo " / <a href='index.php?page=manage&module=clients&action=list'>Clients</a>";
			if(auth($options['cid']))clients($options);
			break;
		case "users":
			#echo " / <a href='index.php?page=manage&module=users&action=list'>Users</a>";
			if($_SESSION['admin'] == 1)users($options);
			break;
	}
}

function stats($options){
	section("Statistics");
	switch($options['module']){
		case "":
			defaultStats();
			break;
		case "downloads":
			switch($options['action']){
				case "latest":
					section("Latest Downloads",2);
					content();
					$query = "select * from $GLOBALS[tablePrefix]log left join $GLOBALS[tablePrefix]filesystem on $GLOBALS[tablePrefix]log.details=$GLOBALS[tablePrefix]filesystem.id where $GLOBALS[tablePrefix]log.type='get' order by timestamp desc limit 25";
					$mylist = new report($query,array('timestamp','user','filename','downloads'));
					$mylist->format(array(	'filename'=>"%rpath%/%filename%"));
					$mylist->display('table');
					contentEnd();
					break;
				case "most":
					section("Most Downloads",2);
					content();
					$query = "select * from $GLOBALS[tablePrefix]log left join $GLOBALS[tablePrefix]filesystem on $GLOBALS[tablePrefix]log.details=$GLOBALS[tablePrefix]filesystem.id where $GLOBALS[tablePrefix]log.type='get' order by downloads desc limit 25";
					$mylist = new report($query,array('timestamp','user','filename','downloads'));
					$mylist->format(array(	'filename'=>"%rpath%/%filename%"));
					$mylist->display('table');
					contentEnd();
					break;				
			}
			break;
		case "users":
			switch($options['action']){
				case "most":
					section("Most Active Users",2);
					content();
					$query = "select *,count(*) as `count` from $GLOBALS[tablePrefix]log where `user` != '' group by `user` order by count desc limit 25";
					$mylist = new report($query,array('user','count'));
					$mylist->display('table');
					contentEnd();
					break;
				case "fails":
					section("Recent Login Failures",2);
					content();
					$query = "select * from $GLOBALS[tablePrefix]log where $GLOBALS[tablePrefix]log.type='loginFail' order by `timestamp` desc limit 25";
					$mylist = new report($query,array('timestamp','details'));
					$mylist->display('table');
					contentEnd();
					break;
				case "list":
					section("User Logins",2);
					content();
					$query = "select *,count(*) as `count` from $GLOBALS[tablePrefix]log where type='login' group by details order by `count` desc";
					$mylist = new report($query,array('details','count'));
					$mylist->display('table');
					contentEnd();
					break;			
			}
			break;
		case "gen":
			switch($options['action']){
				case "latest":
					section("Latest Actions",2);
					content();
					if(isset($_GET['user'])){
						$user = mysql_escape_string($_GET['user']);
						$query = "select * from $GLOBALS[tablePrefix]log where user=\"$user\" order by `timestamp` desc limit 500";
					}else{
						$query = "select * from $GLOBALS[tablePrefix]log order by `timestamp` desc limit 500";
					}
					
					$mylist = new report($query,array('timestamp','ip','user','type','details'));
					$mylist->format(array(	'user'=>"<a href='index.php?".makeLink()."user=%user%'>%user%</a>"));

					$mylist->display('table');
					contentEnd();
					break;
				case "actions":
					section("Most Frequest Action",2);
					content();
					$query = "select *,count(*) as `count` from $GLOBALS[tablePrefix]log group by `type` order by count desc limit 25";
					$mylist = new report($query,array('type','count'));
					$mylist->display('table');
					contentEnd();
					break;
				case "mostip":
					section("Most Frequest IPs",2);
					content();
					$query = "select * ,count(*) as `count`from $GLOBALS[tablePrefix]log group by ip order by `count` desc limit 25";
					$mylist = new report($query,array('ip','count'));
					$mylist->display('table');
					contentEnd();
					break;				
			}
			break;
		
	}
}

function clients($options){
	global $defaultFileStore,$key;
	
	switch($options['action']){
		case "list":
			section("Virtual Directories");
			content();
			if($_SESSION['admin'] == 1){
				newClient();
				$query = "select * from $GLOBALS[tablePrefix]clients order by name";
				
			}else if($_SESSION['admin.cid']){
				$admincid = $_SESSION['admin.cid'];
				$where = '';
				for($i=0;$i<count($admincid);$i++){
					
					$where .= "id=$admincid[$i]";
					if($i+1 < count($admincid))
						$where .= " or ";
				}
				
				$query = "select * from $GLOBALS[tablePrefix]clients where ($where) order by name";
			}
			
			if($query){
				$mylist = new report($query,array('name','display','action'));
				$mylist->format(array(	'display'=>"<a href='index.php?".makeLink(array("action"=>"details"))."cid=%id%'>%display%</a>",
							'action'=>"<a href=\"index.php?".makeLink(array("action"=>"deleteClient"))."cid=%id%\">delete</a>"));
				$mylist->display('table');
			}else{
				echo "You do not admin any Virtual Directories";
			}
			contentEnd();			
			break;
		case "details":
			#echo " / Details<br/>";
			section("Details");
			content();
			$query = "select * from $GLOBALS[tablePrefix]clients where id=$options[cid]";
			$mylist = new report($query,array('name','display','path','action'));
			$mylist->format(array('action'=>"<a href=\"index.php?".makeLink(array("action"=>"deleteClient"))."cid=%id%\">delete</a>"));

			$mylist->display('table');
			

			section("User's Permissions for this directory",2);
			newPermissionC();
			$query = "select *,$GLOBALS[tablePrefix]permissions.id as `pid` from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]users on userid=$GLOBALS[tablePrefix]users.id where clientid=$_GET[cid]";
			$per = new report($query,array('name','scheme','action'));
			$per->format(array("action"=>"<a href=\"index.php?".makeLink(array("action"=>"revokePermission"))."pid=%pid%&uid=%userid%\">delete</a>","view"=>'bool',"rename"=>'bool',"download"=>'bool',"metaEdit"=>'bool',"delete"=>'bool',"move"=>'bool',"folderRename"=>'bool',"folderDelete"=>'bool',"folderMove"=>'bool',"newFolder"=>'bool',"upload"=>'bool'));
			$per->display('table');
			contentEnd();
			break;
		case "newPermission":
			if(isset($_GET['uid'],$_GET['scheme'],$_GET['cid'])){
				switch($_GET['scheme']){
					case "read":
						$scheme = "1,0,1,0,0,0,0,0,0,0,0,0";
						break;
					case "write":
						$scheme = "1,1,1,1,1,1,1,1,1,1,1,0";
						break;
					case "admin":
						$scheme = "1,1,1,1,1,1,1,1,1,1,1,1";
						break;
				}
				$query = "insert into $GLOBALS[tablePrefix]permissions (`userid`,`clientid`,`scheme`,`view`,`rename`,`download`,`metaEdit`,`delete`,`move`,`folderRename`,`folderDelete`,`folderMove`,`newFolder`,`upload`,`admin`) values($options[uid],$options[cid],'$options[scheme]',$scheme)";
				#echo $query;
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=clients&action=details&cid=$options[cid]");
			}
			break;
		case "deleteClient":
			if(isset($_GET['cid'])){
				$query = "select path,name from $GLOBALS[tablePrefix]clients where id=$_GET[cid]";
				$result = mysql_query($query);
				if(mysql_num_rows($result) > 0){
					$clientInfo = mysql_fetch_assoc($result);
					
					$query = "delete from $GLOBALS[tablePrefix]filesystem where path like \"$clientInfo[path]/$clientInfo[name]%\"";
					$result = mysql_query($query)||die(mysql_error());
					
					$query = "delete from $GLOBALS[tablePrefix]permissions where clientid=$options[cid]";
					$result = mysql_query($query)||die(mysql_error());
					
					$query = "delete from $GLOBALS[tablePrefix]clients where id=$options[cid]";
					$result = mysql_query($query)||die(mysql_error());
				}
				header("location: index.php?page=manage&module=clients&action=list");
			}
			break;
		case "revokePermission":
			if(isset($_GET['cid'],$_GET['pid'])){
				$query = "delete from $GLOBALS[tablePrefix]permissions where userid=$options[uid] and clientid=$options[cid] and id=$options[pid]";
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=clients&action=details&cid=$_GET[cid]");
			}
			break;
		case "newClient":
			if($_SESSION['admin'] == 1 and isset($_GET['path'])){
				if(is_dir($_GET['path'])){
					$info = pathinfo($_GET['path']);
					$name = mysql_escape_string($info['basename']);
					$path = mysql_escape_string($info['dirname']);
					if(isset($_GET['name'])){
						
						$display = mysql_escape_string(stripslashes($_GET['name']));
					}else{
						$display = $name;
					}
					
					$query = "insert into $GLOBALS[tablePrefix]clients (`path`,`name`,`display`) values('$path','$name','$display')";
					#echo $query;
					$result = mysql_query($query)||die(mysql_error());
					$cid = mysql_insert_id();
					header("location: index.php?page=manage&module=clients&action=details&cid=$cid");

					
				}else{
					error("Directory not found.  This directory must be the full path to the directory on the server.");
				}
			}
			break;
		case "newClientWiz":
			if($_SESSION['admin'] == 1 and isset($_GET['name'])){
				#echo $defaultFileStore.'/'.$_GET['name'];
				if(!is_dir($defaultFileStore.'/'.$_GET['name'])){
					mkdir($defaultFileStore.'/'.$_GET['name']);
					
					$name = mysql_escape_string($_GET['name']);

					if(isset($_GET['display'])){
						$display = mysql_escape_string(stripslashes($_GET['display']));
					}else{
						$display = $name;
					}
					
					$query = "insert into $GLOBALS[tablePrefix]clients (`path`,`name`,`display`) values('$defaultFileStore','$name','$display')";
					#echo $query;
					$result = mysql_query($query)||die(mysql_error());
					$cid = mysql_insert_id();
					header("location: index.php?page=manage&module=clients&action=details&cid=$cid");

					
				}else{
					error("That shorthand name exists!");
				}
			}
			break;
	}
}

function users($options){
	global $key;
	switch($options['action']){
		case "list":
			section("Users");
			content();
			newUser();
			$query = "select id,name,username from $GLOBALS[tablePrefix]users order by username";
			$mylist = new report($query,array('username','name','action'));
			$mylist->format(array(	'name'=>"<a href='index.php?".makeLink(array("action"=>"details"))."uid=%id%'>%name%</a>",
						'action'=>"<a href='index.php?".makeLink(array("action"=>"deleteUser"))."uid=%id%'>delete</a>"));
			$mylist->display('table');
			
			contentEnd();
			break;
		case "details":
			#echo " / Details<br/>";
			section("Details");
			content();
			$query = "select * from $GLOBALS[tablePrefix]users where id=$options[uid]";
			$mylist = new report($query,array('username','name','email','action','ADauth'));
			$mylist->format(array('action'=>"<a href='index.php?".makeLink(array("action"=>"deleteUser"))."uid=%id%'>delete</a>"));
			
			$mylist->display('table');
			newPassword();
			newEmail();
			
			section("User's Permissions",2);
			information("To modify a users permission delete it and then re-add it");
			newPermission();
			$query = "select *,$GLOBALS[tablePrefix]permissions.id as `pid` from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on clientid=$GLOBALS[tablePrefix]clients.id where userid=$_GET[uid]";
			$per = new report($query,array('name','scheme','action'));
			$per->format(array("action"=>"<a href=\"index.php?".makeLink(array("action"=>"revokePermission"))."pid=%pid%&cid=%clientid%\">delete</a>","view"=>'bool',"rename"=>'bool',"download"=>'bool',"metaEdit"=>'bool',"delete"=>'bool',"move"=>'bool',"folderRename"=>'bool',"folderDelete"=>'bool',"folderMove"=>'bool',"newFolder"=>'bool',"upload"=>'bool'));
			$per->display('table');
			contentEnd();
			break;
		case "newPermission":
			if(isset($_GET['uid'],$_GET['scheme'],$_GET['clientid'])){
				switch($_GET['scheme']){
					case "read":
						$scheme = "1,0,1,0,0,0,0,0,0,0,0,0";
						break;
					case "write":
						$scheme = "1,1,1,1,1,1,1,1,1,1,1,0";
						break;
					case "admin":
						$scheme = "1,1,1,1,1,1,1,1,1,1,1,1";
						break;
				}
				$query = "insert into $GLOBALS[tablePrefix]permissions (`userid`,`clientid`,`scheme`,`view`,`rename`,`download`,`metaEdit`,`delete`,`move`,`folderRename`,`folderDelete`,`folderMove`,`newFolder`,`upload`,`admin`) values($_GET[uid],$_GET[clientid],'$_GET[scheme]',$scheme)";
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=users&action=details&uid=$_GET[uid]");
			}
			break;
		case "revokePermission":
			if(isset($_GET['uid'],$_GET['pid'])){
				$query = "delete from $GLOBALS[tablePrefix]permissions where userid=$options[uid] and clientid=$options[cid] and id=$options[pid]";
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=users&action=details&uid=$_GET[uid]");
			}
			break;
		case "deleteUser":
			if(isset($_GET['uid'])){
				$query = "delete from $GLOBALS[tablePrefix]permissions where userid=$options[uid]";
				$result = mysql_query($query)||die(mysql_error());
					
				$query = "delete from $GLOBALS[tablePrefix]users where id=$options[uid]";
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=users&action=list");
			}
			break;
		case "newUser":
			if(isset($_GET['username'],$_GET['password']) and $_GET['username'] != '' and ($_GET['password'] != '' or $_GET['ad'] == 'on' )){
				
				$username = mysql_escape_string(stripcslashes($_GET['username']));
				$password = mysql_escape_string(stripcslashes($_GET['password']));
				
				if(isset($_GET['display']) and $_GET['display'] != ''){
					$display = mysql_escape_string(stripcslashes($_GET['display']));
				}else{
					$display = $username;
				}
				if(isset($_GET['email'])){
					$email = mysql_escape_string(stripcslashes($_GET['email']));
				}
				
				if($_GET['ad'] == 'on'){
					$adAuth = 1;
				}else{
					$adAuth = 0;
				}
				
				$query = "insert into $GLOBALS[tablePrefix]users (`username`,`password`,`email`,`name`,`adAuth`) values(\"$username\",md5(\"$key$password\"),\"$email\",\"$display\",\"$adAuth\")";
				$result = mysql_query($query)||die(mysql_error());
				#echo $query;
				$uid = mysql_insert_id();
				header("location: index.php?page=manage&module=users&action=details&uid=$uid");
			}else{
				error("Username and Password cannot be blank");
			}
			break;
		case "newPassword":
			if(isset($_GET['uid'],$_GET['pass'],$_GET['passconf']) && $_GET['pass'] == $_GET['passconf'] && $_GET['pass'] != ''){
				$pass = mysql_escape_string($_GET['pass']);
				$query = "update $GLOBALS[tablePrefix]users set `password`=md5(\"$key"."$pass\") where id=$options[uid]";
				#echo $query;
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=users&action=details&uid=$_GET[uid]");
			}else{
				error("The password you specified was blank or did not match");
			}

			break;
		case "ad":
			if(isset($_GET['uid'])){
				$query = "select * from $GLOBALS[tablePrefix]users where id=$options[uid]";
				$q = mysql_query($query) or die(mysql_error());
				$user = mysql_fetch_assoc($q);
				
				include_once("../inc/adLDAP.php");
				$ADconn = new adLDAP;
	
				$userinfo = $ADconn->existingUser($user['username']);
				$query = "update $GLOBALS[tablePrefix]users set `ADauth`=1, `name`=\"$userinfo[0]\", `email`=\"$userinfo[1]\" where id=$options[uid]";
				mysql_query($query);
				#echo $query;
				header("location: index.php?page=manage&module=users&action=details&uid=$_GET[uid]");
			}else{
				error("The password you specified was blank or did not match");
			}

			break;
		
		case "setEmail":
			if(isset($_GET['uid'],$_GET['email'])){
				$email = mysql_escape_string($_GET['email']);
				$query = "update $GLOBALS[tablePrefix]users set `email`=\"$email\" where id=$options[uid]";
				#echo $query;
				$result = mysql_query($query)||die(mysql_error());
				header("location: index.php?page=manage&module=users&action=details&uid=$_GET[uid]");
			}

			break;
	}
}


function content(){
	//echo "<div id='content'>";
}
function contentEnd(){
	//echo "</div>";
}

function section($text,$level = 1){

	
	if($level ==1){
		?>
	
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Relay</title>

	<script src="../js/jquery-1.6.2.min.js" type="text/javascript"> </script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#regenerate').click(function(){
				$.ajax({
					url: "../relay.php?relay=regenerateThumbs",
					success: function(){
						alert('Thumbnails have been regenerated!');
					}
				});

				return false;
			});
		})
	</script>






<style type="text/css">
	* {margin:0; padding:0;}
	body { margin:0; padding:0; font-size:70%; background:white url(../images/headerbar.png) left -30px repeat-x; font-family:Verdana, Arial, sans-serif;}
		p { margin:0; padding:0;}
		h1 { color:#606060; font-size:18pt; font-family:helvetica, arial; margin:0 0 5px; }
		h2 { color:#606060; font-size:12pt; font-family:helvetica, arial; margin:20px 0 0; }
		hr {border:0; height:1px; margin:20px 0; background:#7ecc1b; }
		a {color:#ffaa06; text-decoration:none;}
		
		input.border {
			font-family:Verdana, arial, sans-serif;
			font-size:10pt;
			padding:3px;
			background:white;
			margin:0 10px 0;
			border-left:1px solid #83a5c7; 
			border-top:1px solid #83a5c7; 
			border-bottom:1px solid #d3e1ee;  
			border-right:1px solid #d3e1ee; 
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
		
#head {
	
	width:865px;
	height:100px;
	position:absolute;
	top:8px;
	left:16px;
}
img.logo {
	position:absolute;

	left:35px;
}
#nav {
	position:absolute;
	top:49px;
	right:35px;
	list-style:none;
	margin:0;
	padding:0;
}
#nav li {
	float:left;
	padding:0 0 0 14px;
}

#nav a {
	color:white;
}
#nav a:hover {
	color:#cbe8ff;
}
#nav li {
	color:rgb(149,149,149);
}
#content {
	padding:12px;
	position:absolute;
	top: 120px;
	left: 100px;
	width:600px;
xbackground:white url(../images/body-grad.png) left top repeat-x;
	height:200px;
}


#content a { font-weight:bold; }

table { margin-bottom:15px; border:1px solid #e6e6e6; }
table table { width:auto; }
table td { padding: 5px 10px; vertical-align:top;}




</style>

</head>

<body>
<div id="head">
		<img src="../images/relay.png" class="logo" alt="Relay Beta" />

	
		<ul id="nav">

<?
		echo "<li><a href='index.php'>Management</a></li>
		<li><a id='regenerate' href='#'>Regenerate Thumbnails</a></li>
		<li>|</li>
		<li><a href='index.php?page=manage&module=clients&action=list'>Virtual Directories</a></li>";
		
		if($_SESSION['admin']==1){
			echo "
			<li><a href='index.php?page=manage&module=users&action=list'>Users</a></li>
			<li><a href='index.php?page=stats'>Stats</a></li>";
		}
		echo "<li>|</li><li><a href='../relay.html'>Relay</a></li><li><a href='../relay.php?relay=userLogoff'>Logout</a></li>";
		?>

		</ul>

	
	
	
</div>

<div id="content">

<?
	}

	

	echo "<h$level>$text</h$level>";
}

function error($text){
	echo "$text";
	exit;
}
function information($text){
	echo "<em>$text</em><br/>\n";
}
function options($options){
	section("Options",2);
		echo "<ul>";
	foreach($options as $optionName=>$optionValue){
		echo "<li><a href=\"index.php?".makeLink(array("action"=>$optionValue))."\">$optionName</a></li>";
	}
		echo "</ul>";
}

function defaultpage(){
	if(auth()){
		section("Management");
		content();
		echo "
		<a href='index.php?page=manage&module=clients&action=list'>Virtual Directories</a><br />";
		
		if($_SESSION['admin'])echo "
		<a href='index.php?page=manage&module=users&action=list'>Users</a><br />
		<a href='index.php?page=stats'>Stats</a><br />
		";
		contentEnd();
	}else{
		echo "You are not an admin on any client.";
	}
}

function defaultStats(){
	content();
	section("Downloads",2);
	echo "
	&nbsp;&nbsp;<a href='index.php?page=stats&module=downloads&action=latest'>Latest Downloads</a><br/>
	&nbsp;&nbsp;<a href='index.php?page=stats&module=downloads&action=most'>Most Downloads</a><br/>
	";
	section("Users",2);
	echo "&nbsp;&nbsp;<a href='index.php?page=stats&module=users&action=most'>Most Active</a><br/>
	&nbsp;&nbsp;<a href='index.php?page=stats&module=users&action=fails'>Recent Login Fails</a><br/>
	";
	
	section("General",2);
	echo "&nbsp;&nbsp;<a href='index.php?page=stats&module=gen&action=actions'>Most Frequent Action</a><br/>
	&nbsp;&nbsp;<a href='index.php?page=stats&module=gen&action=mostip'>Most Frequent IP</a><br/>
	&nbsp;&nbsp;<a href='index.php?page=stats&module=gen&action=latest'>Last Actions</a><br/>
	
	";
	contentEnd();
}
function newClient(){
	global $defaultFileStore;
	
	echo "
	<div id='box'>
	";
	section("New Virtual Directory",2);
	echo "
	<table width='100%'><tr><td width=50%>
	<b>Start New Virtual Directory Wizzard</b><br>Creates a folder for data storage and sets up the directory<br/>
	<form>
	";
	echo makeForm(array("action"=>"newClientWiz"));
	echo "
	<table>
		<tr><td>Display Name</td><td><input type='text' class='border' name='display' value=''/> - optional</td></tr>	
		<tr><td>Short Name</td><td><input type='text' class='border' name='name'/></td></tr>
		<tr><td colspan=2><input type='submit'></td></tr>
	</table>
	</form>
	";
	
	echo "
	</td><td width=50%>
	<b>Make a Virtual Directory from an existing folder</b>
	<form>
	";
	echo makeForm(array("action"=>"newClient"));
	echo "
	<table>
		<tr><td>Name</td><td><input type='text' class='border' name='name' value=''/> - optional</td></tr>	
		<tr><td>Path</td><td><input type='text' class='border' name='path' value='$defaultFileStore'/></td></tr>
		<tr><td colspan=2><input type='submit'></td></tr>
	</table>
	</form>
	</td></tr></table>
	</div>
	";
}


function newUser(){
	global $activeDirectoryServer;
	echo "
	<div id='box'>
	<form>
	";
	section("Create User",2);
	echo makeForm(array("action"=>"newUser"));
	echo "
	<table>
		<tr><td>Username</td><td><input type='text' class='border' name='username'/></td></tr>
		<tr><td>Password</td><td><input type='password' class='border' name='password'>";
		if($activeDirectoryServer){
			echo "or <input type='checkbox' name='ad'> Active Directory Authentication";
		}
		echo "</td></tr>
		<tr><td>Display Name</td><td><input type='text' class='border' name='display'/></td></tr>
		<tr><td>Email Address</td><td><input type='text' class='border' name='email'></td></tr>
		
		<tr><td colspan=2><input type='submit'></td></tr>
	</table>
	</form>
	</div>
	";
}

function newPassword(){
	global $activeDirectoryServer,$options;
	$query = "select * from $GLOBALS[tablePrefix]users where id=$options[uid]";
	$q = mysql_query($query) or die(mysql_error());
	$user = mysql_fetch_assoc($q);
	
	if($user['ADauth'] == 0){
		section("Reset Password",2);
		echo "
		<form>
		";
		echo makeForm(array("action"=>"newPassword"));
		echo "
		<table>
			<tr><td>Password</td><td><input type='password' class='border' name='pass'/></td></tr>
			<tr><td>Confirm Password</td><td><input type='password' class='border' name='passconf'></td></tr>
			<tr><td colspan=2><input type='submit'></td></tr>
		</table>
		</form>
		";
	
		if(isset($activeDirectoryServer)){
			include_once("../inc/adLDAP.php");
			$ADconn = new adLDAP;
			
			
			if($ADconn->existingUser($user['username'])){
				section("Active Directory",2);
				content();
				echo "<a href='?page=manage&module=users&action=ad&uid=$options[uid]'>Switch</a> $user[username] to an Active Directory Account";
				contentEnd();
			}
		}
	}else{
		section("Active Directory",2);
		content();
		echo "This is an active directory user, to update this users name or email from the Active Directory click <a href='?page=manage&module=users&action=ad&uid=$options[uid]'>here</a>. ";
		contentEnd();
	}
	
}
function newEmail(){
	global $options;
	$query = "select * from $GLOBALS[tablePrefix]users where id=$options[uid]";
	$q = mysql_query($query) or die(mysql_error());
	$user = mysql_fetch_assoc($q);
	
	if($user['ADauth'] == 0){
			
		section("Set Email",2);
		echo "
		<form>
		";
		echo makeForm(array("action"=>"setEmail"));
		echo "
		<table>
			<tr><td>Email</td><td><input type='text' class='border' name='email'/></td></tr>
			<tr><td colspan=2><input type='submit'></td></tr>
		</table>
		</form>
		";
	}
}

function newPermissionC(){
	global $options;
	$query = "select * from $GLOBALS[tablePrefix]users";
	
	if(substr(mysql_get_server_info(),0,3) != "4.0"){
		$query .= " where id not in (select userid from $GLOBALS[tablePrefix]permissions where clientid=$options[cid])";
	}
	
	$query .= " order by name";

	#print_r( mysql_get_server_info());
	$result = mysql_query($query) or die("Query failed: $query<br/>" . mysql_error());
	if(mysql_num_rows($result) > 0){
		section("Add Permissions",3);
		echo "<form>";
		echo makeForm(array("action"=>"newPermission","uid"=>"none"));
		echo "<select name='uid'>";
		
		while($row = mysql_fetch_assoc($result)){
			echo "<option value='$row[id]'>$row[name]($row[username])</option>";	
		}
		
		echo "
		</select>
		
		<select name='scheme'>
			<option>read</option>
			<option selected>write</option>
			<option>admin</option>
		</select>
		<input type='submit'>
		</form>
		
		";

	}
}


function newPermission(){
	global $options;
	
	$query = "select * from $GLOBALS[tablePrefix]clients";
	
	if(substr(mysql_get_server_info(),0,3) != "4.0"){
		$query .= " where id not in (select clientid from $GLOBALS[tablePrefix]permissions where userid=$options[uid])";
	}
	
	$query .= " order by name";
	
	$result = mysql_query($query) or die("Query failed: $query<br/>" . mysql_error());
	if(mysql_num_rows($result) > 0){
		section("Add Permissions",3);
		echo "<form>";
		echo makeForm(array("action"=>"newPermission"));
		echo "<select name='clientid'>";
		
		while($row = mysql_fetch_assoc($result)){
			echo "<option value='$row[id]'>$row[name]</option>";	
		}
		
		echo "
		</select>
		
		<select name='scheme'>
			<option>read</option>
			<option>write</option>
			<option>admin</option>
		</select>
		<input type='submit'>
		</form>
		
		";

	}
}

class report{
	var $data;
	var $format;
	var $row;
	var $allColumns;
	var $columns;
	var $result;
	
	
	function report($query,$columns){
		global $database;
		$this->columns = $columns;
		$this->result = mysql_query($query) or die("Query failed: $query<br/>" . mysql_error());
	}

	function format($options){
		$this->format = $options;
	}

	function display($type){

		    if(mysql_num_rows($this->result) > 0){
	    		$i = 0;
			  	while($row = mysql_fetch_assoc($this->result)) {
			  		if($i==0){foreach($row as $fieldname=>$fieldvalue)$this->allColumns[]=$fieldname;}
					foreach($this->columns as $col){
						if($this->format[$col]){
							if($this->format[$col] != 'bool'){
							 	$thisresult = $this->format[$col];
								foreach($this->allColumns as $columnName){
							  		$thisresult = str_replace("%$columnName%",$row[$columnName],$thisresult);
								}
								$results[$col] = $thisresult;
							}else{
								if($row[$col] == 1){
									$thisresult = "True";
								}else{
									$thisresult = "False";
								}
							}
							$results[$col] = $thisresult;
					  	}else{
							$results[$col] = $row[$col];
					  	}
					}
			  		$this->data[] = $results;
			  		$i++;
			  	}
			}
			switch($type){
				case "list":
					foreach($this->data as $row){
						echo "<li>";
						foreach($row as $fieldname=>$fieldvalue){
							echo $fieldvalue;
						}
						echo "</li>";
					}
					break;
				case "table":
					if(count($this->data)){
						echo "<table>";
						$i=0;
						//print_r($this->data);
						
						foreach($this->data as $row){
							echo "<tr>";
							if($i==0)
								foreach($row as $fieldname=>$fieldvalue){
									echo "<td><b>$fieldname</b></td>";
								}
							echo "</tr>";
							echo "<tr>";
							foreach($row as $fieldname=>$fieldvalue){
								echo "<td>$fieldvalue</td>";
							}
							echo "</tr>";
							$i++;
						}
						echo "</table>";
					}
					break;
			}
	}
}


display($options);

?>
</div>

</body>
</html>


