<?php
// error_reporting(2);

if(!isset($resource))$resource = "0";
// session initilization
session_start();

include_once("conf.inc.php");

if($resource != true){
	if(isset($_GET['relay'])){$_POST['relay']=$_GET['relay'];}
	if(isset($_POST['relay'])){
		switch($_POST['relay']){
			case "userLogin":
				if(isset($_POST['username'],$_POST['password'])){
					userLogin($_POST['username'],$_POST['password']);
				}else{
					error("username and password are both required");
				}
				break;
			case "userLogoff":
				userLogoff();
				break;
			case "checkLogin":
				checkLogin();
				break;
			case "search":
				if(isset($_POST['terms'])){
					search($_POST['terms']);
				}
				break;
			case "getFolder":
				if(isset($_POST['path'])){
					getFolder($_POST['path']);
				}
				break;
			case "getFile":
				if(isset($_GET['fileid'])){
					getFile($_GET['fileid']);
				}
				break;
			case "getFilePackage":
				if(isset($_GET['fileid'])){
					getFilePackage($_GET['fileid']);
				}
				break;
			case "emailFilePackage":
				if(isset($_GET['fileid'],$_GET['to'],$_GET['from'],$_GET['message'])){
					emailFilePackage($_GET['fileid'],$_GET['to'],$_GET['from'],$_GET['message']);
				}
				break;	
			case "getMeta":
				if(isset($_POST['fileid'])){
					getMeta($_POST['fileid']);
				}
				break;
			case "getFolderMeta":
				if(isset($_POST['path'])){
					getFolderMeta($_POST['path']);
				}
				break;
			case "setMeta":
				if(isset($_POST['fileid'],$_POST['filename'],$_POST['description'],$_POST['flags'])){
					setMeta($_POST['fileid'],$_POST['filename'],$_POST['description'],$_POST['flags']);
				}
				break;
			case "fileRename":
				if(isset($_POST['fileid'],$_POST['filename'])){
					fileRename($_POST['fileid'],$_POST['filename']);
				}
				break;
			case "fileMove":
				if(isset($_POST['fileid'],$_POST['path'])){
					fileMove($_POST['fileid'],$_POST['path']);
				}
				break;
			case "fileDelete":
				if(isset($_POST['fileid'])){
					fileDelete($_POST['fileid']);
				}
				break;
			case "folderRename":
				if(isset($_POST['path'],$_POST['name'],$_POST['newname'])){
					folderRename($_POST['path'],$_POST['name'],$_POST['newname']);
				}
				break;
			case "folderMove":
				if(isset($_POST['name'],$_POST['path'],$_POST['newpath'])){
					folderMove($_POST['name'],$_POST['path'],$_POST['newpath']);
				}
				break;
			case "folderDelete":
				if(isset($_POST['folder'])){
					folderDelete($_POST['folder']);
				}
				break;
			case "newFolder":
				if(isset($_POST['name'],$_POST['path'])){
					newFolder($_POST['name'],$_POST['path']);
				}
				break;
			case "fileUpload":
				if(isset($_POST['path'])){
					uploadFiles($_POST['path']);
				}
				break;
			case "upload":
				if(isset($_POST['dir'])){
					upload($_POST['dir']);
				}	
				break;
			case "uploadSmart":
				uploadSmart();
				break;
			case "uploadAuth":
				if(isset($_POST['path'])){
					uploadAuth($_POST['path']);
				}
				break;
			case "thumbnail":
				if(isset($_POST['fileid'])){
					thumbnail($_POST['fileid']);
				}
				break;
			case "newPassword":
				if(isset($_POST['currentPassword'],$_POST['newPassword'])){
					newPassword($_POST['currentPassword'],$_POST['newPassword']);
				}
				break;
			case "getThumb":
				if(isset($_GET['fileid'])){
				getThumb($_GET['fileid']);
				}
				break;
		}
	}
}



function search($terms){
	global $database,$dateFormat,$fileinfo;
	jsonStart();
	$terms = mysql_escape_string($terms);
	foreach(getUserPaths() as $path){
		if(isset($where))$where .= " or ";
		$where .= "path like \"$path%\"";
	}
	#$query = "select *,date_format(`date`,\"$dateFormat\") as `dateformatted` from filesystem where ($where) and match(filename,description) against(\"$terms\")";
	$query = "select *,date_format(`date`,\"$dateFormat\") as `dateformatted`,match(filename,description,rpath) against(\"$terms\") as `rank` from $GLOBALS[tablePrefix]filesystem where ($where) and (match(filename,description,rpath) against(\"$terms\") or (filename like \"%$terms%\" or rpath like \"%$terms%\" or description like \"%$terms%\")) and status='found' order by rank desc";
	#echo $query;
	$resourceq = mysql_query($query,$database) ;
	#echo $resourceq;
	$toprank = 0.000001;
	while($files = mysql_fetch_assoc($resourceq)) {
		if($toprank == 0.000001 and $files['rank'] != 0)$toprank = $files['rank'];
		$myrank = round(($files['rank']/$toprank)*3)+2;
		getFileInfo($files['id']);
		jsonAdd("\"rank\":\"$myrank\",\"type\": \"file\", \"path\": \"$fileinfo[virtualpath]\",\"name\": \"$files[filename]\",\"date\":\"$files[dateformatted]\", \"id\": \"$files[id]\",\"flags\": \"$files[flags]\"");
		$results ++;
	}
	if($results > 0)
		echo jsonReturn('search');
}

function getFile($fileid){
	global $database,$filepath,$fileinfo;
	if(getFileInfo($fileid)){
		if(getUserAuth('download',$fileinfo['virtualpath'])){
			logAction('get',$fileid);
			$query = "update $GLOBALS[tablePrefix]filesystem set downloads=downloads+1 where id=$fileid";
			$result = mysql_query($query,$database);
			header("Pragma: public"); 
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false); 
			header("Content-type: $fileinfo[type]");
			header("Content-Transfer-Encoding: Binary");
			header("Content-length: ".filesize($filepath));
			header("Content-disposition: attachment; filename=\"".basename($filepath)."\"");
			readfile("$filepath");
		}else{
			error("access denied to $fileid");
		}
	}else{
		error ('access denied');
	}
}

function emailFilePackage($fileids,$to,$from,$message){
	global $fileinfo,$filepath,$database;
	
	$fileids = preg_split("/\,/",$fileids);
	
	$boundary = "DU_" . md5(uniqid(time()));	
	$headers = "From: $from". "\r\n";
	$headers .= "MIME-Version: 1.0"."\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\";". "\r\n";
	$mailMessage = "--$boundary
Content-Type: text/plain; charset=\"iso-8859-1\"
Content-Transfer-Encoding: 7bit

$message
";

	foreach($fileids as $fileid){
		if(getFileInfo($fileid)){
			#echo "$fileinfo[rpath] $fileid";
			if(getUserAuth('download',$fileinfo['virtualpath'])){
				logAction('get',$fileid);
									
				$query = "update $GLOBALS[tablePrefix]filesystem set downloads=downloads+1 where id=$fileid";
				$result = mysql_query($query,$database);
					
				$ct = $fileinfo['type'];
				if($ct=='')$ct = 'application/force-download';
				$mailMessage.= "--$boundary\nContent-Type: $ct\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"$fileinfo[filename]\"\n\n";
				$mailMessage.= chunk_split(base64_encode(file_get_contents($filepath)));
			}
		}
	}
	$mailMessage.= "\n--$boundary--";
	#echo $mailMessage;
	ini_set(SMTP,'mvs5.duarte.com');
	
	if(mail($to,"File from $from",$mailMessage,$headers))
		$status = "Message Sent";
	else
		$status = "ERROR: Message Not Sent";
	#". base64_encode(getFilePackage($fileids,true))."
	#".file_get_contents($filepath)."
	
	jsonStart();
	jsonAdd("\"status\": \"$status\"");
	echo jsonReturn("bindings"); 
}

function getFilePackage($fileids,$returnContent = false){
	global $database,$fileinfo,$filepath;
	
	$fileids = preg_split("/\,/",$fileids);
	include_once("inc/createZip.inc.php");
	$createZip = new createZip;
	$fileCount = 0;
	logAction('getFilePackage',$fileids);
	foreach($fileids as $fileid){
		if(getFileInfo($fileid)){
			if(getUserAuth('download',$fileinfo['virtualpath'])){
				logAction('get',$fileid);
				$query = "update $GLOBALS[tablePrefix]filesystem set downloads=downloads+1 where id=$fileid";
				$result = mysql_query($query,$database);
				
				$createZip -> addFile(file_get_contents($filepath), "$fileinfo[filename]");
				$fileCount++;
			}else{
				// denied
			}
		}else{
			// denied
		}
	}
	
	if($fileCount > 0){
		if($returnContent != true){
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: Binary");
			#header("Content-length: ".strlen($zipped));
			header("Content-disposition: attachment; filename=\"package.zip\"");
			echo $createZip -> getZippedfile();
		}else{
			return $createZip->getZippedfile();
		}
	}else{
		error('no files zipped');
	}

}

function getFolder($path){
	global $database,$resource,$dateFormat;
	userPermissions();
	$output = '';
	jsonStart();
	$path = mysql_escape_string($path);

	// For Virtual Directories
	if($path == '' || $path == '/'){

		$query = "select * from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\" and $GLOBALS[tablePrefix]clients.name =\"$_SESSION[user]\" order by display";
		$result = mysql_query($query,$database);
		while($clients = mysql_fetch_assoc($result))
			$output .=  jsonAdd("\"displayname\":\"$clients[display]\",\"scheme\":\"$clients[scheme]\",\"type\": \"directory\", \"name\": \"$clients[name]\", \"path\": \"/$clients[name]\",\"virtual\":\"true\"");
			
		$query = "select * from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\" and $GLOBALS[tablePrefix]clients.name !=\"$_SESSION[user]\" order by display";
		$result = mysql_query($query,$database);
		$vdcount = mysql_num_rows($result);
		if($vdcount >= 1){  // If user has multiple virtual directorys display them all
			if($vdcount > 2){
				$virtual = "closed";
			}else if($vdcount == 1){
				$virtual = "true";
			}else{
				$virtual = "false";
			}
			
			while($clients = mysql_fetch_assoc($result)) {
			        $output .=  jsonAdd("\"displayname\":\"$clients[display]\",\"scheme\":\"$clients[scheme]\",\"type\": \"directory\", \"name\": \"$clients[name]\", \"path\": \"/$clients[name]\",\"virtual\":\"$virtual\"");
			}
		}
		$output .= jsonReturn('getFolder');
		#else{ // otherwise switch root directory to only virtual directory
		#	$clients = mysql_fetch_assoc($result);
		#	$path="/".$clients['name'];
		#}
	}
	if($output > ''){
		if($resource != true){
			echo $output;
			die;
		}else{
			return $output;
		}
	}	
	
	// Non Virtual Directories
	if(getUserAuth('view',$path)){
		logAction('list',$path);
		$fullpath = getUserPath($path).$path;

		databaseSync($fullpath,$path);

		if (is_dir($fullpath)) {
			if ($dh = opendir($fullpath)) {
			   while (($file = readdir($dh)) !== false) {
			     #echo "$file";
			     if($file != '.' && $file != '..' && filetype($fullpath . '/' . $file) == 'dir'){
			       jsonAdd("\"type\": \"directory\", \"name\": \"$file\", \"path\": \"$path/$file\"");
				     }
			   }
			   closedir($dh);
			}
		}else{
		      error("directory doesnt exist $fullpath");
		}
		$query = "select *,date_format(`date`,\"$dateFormat\") as `dateformatted` from $GLOBALS[tablePrefix]filesystem where path=\"$fullpath\" and status=\"found\" order by `date` desc";
		$result = mysql_query($query,$database);
		while($files = mysql_fetch_assoc($result)) {
			jsonAdd("\"type\": \"file\", \"name\": \"$files[filename]\",\"date\":\"$files[dateformatted]\", \"id\": \"$files[id]\",\"flags\": \"$files[flags]\"");
		}
		$output .= jsonReturn('getFolder');
	      
		if($resource != true)
			echo $output;
		else
			return $output;
	      
	}else{
		error('no auth to view');
	}
}


function getFolderMeta($path){
  jsonStart();
  $path = mysql_escape_string($path);
  if(getUserAuth('view',$path)){
	logAction('getFolderMeta',$path);
    $fullpath = getUserPath($path).$path;
    $size = filesize_format(get_size($fullpath));
    $name = basename($fullpath);
    $modified = '';
    $created ='';

    jsonAdd("\"name\": \"$name\", \"size\": \"$size\"");
    echo jsonReturn('getFolderMeta');
  }else{
    error('access denied');
  }
}

function getMeta($fileid){
  global $fileinfo;

	if(getFileInfo($fileid)){
		if(getUserAuth('view',$fileinfo['virtualpath'])){
			jsonStart();
			logAction('getMeta',$fileid);
			if(getUserAuth('metaEdit',$fileinfo['virtualpath']))
			{
				jsonAdd("\"edit\": \"true\"");
			}else{
				jsonAdd("\"edit\": \"false\"");
			}
		    
			if($fileinfo['type'] > '')
			  $type = $fileinfo['type'];
			    else
			  $type = "document";
		    
			jsonAdd("\"filename\": \"$fileinfo[filename]\",\"path\": \"$fileinfo[virtualpath]\",\"image\":$fileinfo[image],\"type\": \"$type\", \"date\": \"$fileinfo[date]\", \"downloads\": \"$fileinfo[downloads]\", \"description\": \"$fileinfo[description]\", \"flags\": \"$fileinfo[flags]\", \"type\": \"$fileinfo[type]\", \"size\": \"$fileinfo[size]\"");
			      if($type == "image/jpeg"){
				      if(function_exists("exif_read_data")){
					      $exif = exif_read_data($fileinfo['path'].'/'.$fileinfo['filename']);
				      }
			      }
		}else{
		  error('access denied2');
		}
	}else{
	  error('access denied1');
	}
	echo jsonReturn('getMeta');
}

function setMeta($fileid,$filename,$description,$flags){
  global $database,$fileinfo;

  $fileid = mysql_escape_string($fileid);
  $filename = mysql_escape_string($filename);
  $description = mysql_escape_string($description);
  $flags = mysql_escape_string($flags);

  if(getFileInfo($fileid)){
    if(getUserAuth('metaEdit',$fileinfo['virtualpath'])){
	  logAction('metaEdit',$fileid);
	  if($filename != $fileinfo['filename']){
	    fileRename($fileid,$filename);
	  }else{
	    $filename = $fileinfo['filename'];
	  }

      $query = "update $GLOBALS[tablePrefix]filesystem set description=\"$description\",flags=\"$flags\" where id=$fileid";
	  $result = mysql_query($query,$database);
	  echo "done";
	}else{error('access denied');}
  }else{error('access denied');}
}

function fileRename($fileid,$filename){
  global $database,$fileinfo;

  $fileid = mysql_escape_string($fileid);
  $filename = mysql_escape_string($filename);
  $filename = str_replace("\\","",$filename);
  $filename = str_replace("/","",$filename);

  if(getFileInfo($fileid)){
    if(getUserAuth('rename',$fileinfo['virtualpath'])){
      logAction('rename',$fileid);
	  $query = "update $GLOBALS[tablePrefix]filesystem set filename=\"$filename\" where id=$fileid";
	  $result = mysql_query($query,$database);
	  rename($fileinfo['path'].'/'.$fileinfo['filename'],$fileinfo['path'].'/'.$filename);
	}  else{
	  error('rename denied');
	}
  }else{
    error('rename denied');
  }
}

function fileDelete($fileid){
  global $database,$fileinfo;

  $fileid = mysql_escape_string($fileid);

  if(getFileInfo($fileid)){
    if(getUserAuth('delete',$fileinfo['virtualpath'])){
	  logAction('delete',$fileid);
	  $query = "delete from $GLOBALS[tablePrefix]filesystem where id=$fileid";
	  $result = mysql_query($query,$database);
	  unlink($fileinfo['path'].'/'.$fileinfo['filename']) || error('file error');
	  echo "done";
	}else{error('file access denied');}
  }else{
    error('access denied');
  }
}

function fileMove($fileid,$path){
	global $database,$fileinfo;
      
	$fileid = mysql_escape_string($fileid);
	
	$path = str_replace("//","/",$path);
	$path = str_replace("..","",$path);
	
	$path = mysql_escape_string($path);
      
	if(getFileInfo($fileid)){
	  if(getUserAuth('move',$path) && getUserAuth('move',$fileinfo['virtualpath'])){
	    $newPath = getUserPath($path).$path;
	    if(is_dir($newPath)){
		  logAction('move',$fileid);
		  $query = "update $GLOBALS[tablePrefix]filesystem set path=\"$newPath\",rpath=\"$path\" where id=$fileid";
		  $result = mysql_query($query,$database);
		  rename($fileinfo['path'].'/'.$fileinfo['filename'],$newPath.'/'.$fileinfo['filename']);
		  echo "done";
		}else{
		  error('new directory doesnt exist');
		}
	      }else{
		error('file move denied');
	      }
	}else{
	  error('move denied');
	}
}

function folderRename($path,$name,$newname){
  global $database;

  $newname = mysql_escape_string($newname);
  $name = mysql_escape_string($name);
  $path = mysql_escape_string($path);

  if(getUserAuth('folderRename',$path)){

    $currentPath = getUserPath($path).$path.'/'.$name;
    $newPath = getUserPath($path).$path.'/'.$newname;

    if(is_dir($currentPath) && !is_dir($newPath)){
	  logAction('folderRename',$newPath);
	  if(rename($currentPath,$newPath)){
	    $query = "update $GLOBALS[tablePrefix]filesystem set path=\"$newPath\",rpath=\"$path/$newname\" where path=\"$currentPath\"";
	    $result = mysql_query($query,$database);
	    echo "done";
	  }else{
	    echo "error";
	  }

	}else{
	  error('old name doesnt exist or new name already exists');
	}

  }else{
    error('rename denied');
  }
}

function folderMove($name,$path,$newpath){
	global $database;
      
	$name = mysql_escape_string($name);
	$path = mysql_escape_string($path);

	$newpath = str_replace("..","",$newpath);
 	$newpath = mysql_escape_string($newpath);     
      
	if(getUserAuth('folderMove',$path) && getUserAuth('folderMove',$newpath)){
      
	  $userPath = getUserPath($path).$path.'/'.$name;
	  $userNewPath = getUserPath($newpath).$newpath.'/'.$name;
      
	  if(is_dir($userPath) && !is_dir($userNewPath)){
		logAction('folderMove',$userNewPath);
		if(rename($userPath,$userNewPath)){
		  $query = "update $GLOBALS[tablePrefix]filesystem set path=\"$userNewPath\",rpath=\"$newpath/$name\" where path=\"$userPath\"";
		  $result = mysql_query($query,$database);
		  echo "done";
		}else{
		  echo "error";
		}
      
	      }else{
		error('old name doesnt exist or new name already exists');
	      }
      
	}else{
	  error('move denied');
	}
}


function folderDelete($folder){
	global $database;

	$folder = mysql_escape_string($folder);

	if(getUserAuth('folderDelete',$folder)){

		$deleteDir = getUserPath($folder).$folder;
		logAction('folderDelete',$deleteDir);
	
		if(deleteDir($deleteDir)){
			$query = "delete from $GLOBALS[tablePrefix]filesystem where path like \"$deleteDir\%\"";
			$result = mysql_query($query,$database);
			echo "ok";
		}else{
			echo "oops somethings wrong";
		}
	}else{
		error('delete denied');
	}
}

function newFolder($name,$path){
	global $database;

	$name = mysql_escape_string($name);
	$path = mysql_escape_string($path);

	$fullpath = getUserPath($path).$path.'/'.$name;

	if(getUserAuth('newFolder',$path)){
	logAction('newFolder',$path.'/'.$name);

	$i = 1;
	$append = "";

	while(is_dir($fullpath.$append)){
		$append = " $i";
		$i++;
	}

	if(mkdir($fullpath.$append)){
		echo "ok";
	}else{
		echo "oops somethings wrong";
	}

	}else{
		error('new folder');
	}
}

function checkLogin(){
  	jsonStart();
	logAction('checkLogin',$_SESSION['user']);
  	if(isset($_SESSION['userid'])){
	    jsonAdd("\"login\": \"true\",\"name\": \"$_SESSION[name]\"");
	}else{
	    jsonAdd("\"login\": \"false\"");
	}
	echo jsonReturn('userLogin');
}

function newPassword($current,$new){
	$query = "select * from $GLOBALS[tablePrefix]users where id=$_SESSION[userid] and password=md5(\"G8,rMzw6BrBApLU9$current\")";
	$result = mysql_query($query);
	
	if(mysql_num_rows($result) == 1){
		logAction('newPassword',$_SESSION['user']);
		$pass = mysql_escape_string($_GET['pass']);
		$query = "update $GLOBALS[tablePrefix]users set `password`=md5(\"G8,rMzw6BrBApLU9$new\") where id=$_SESSION[userid]";
		$result = mysql_query($query)||die(mysql_error());
	}else{
		error("bad current password");
	}
}


function userLogoff(){
	 session_destroy();
	 header('Location:index.php');
	exit;
}

function userLogin($username,$password){
	session_start();
	$_SESSION['userid'] = NULL;
	
	include_once("inc/adLDAP.php");

	global $database,$passwordKey;
	$username = mysql_escape_string($username);
	$password = mysql_escape_string($password);
	
	#ADauth check
	$query = "select * from $GLOBALS[tablePrefix]users where username=\"$username\"";
	$result = mysql_query($query);
	$userinfo = mysql_fetch_assoc($result);

	if($userinfo['ADauth'] == 1){
		$ADconn = new adLDAP;
		if($ADconn->authenticate($username,$password)){
			#success
			$loginSuccess = true;
		}else{
			$loginSuccess = false;
		}
	}else{
		$query = "select * from $GLOBALS[tablePrefix]users where username=\"$username\" and password=md5(\"$passwordKey$password\")";
		$result = mysql_query($query,$database);
		if($userinfo = mysql_fetch_assoc($result)){
			$loginSuccess = true;
		}
		
	}

	if($loginSuccess == true) {
		$_SESSION['userid']=$userinfo['id'];
		$_SESSION['user']=$username;
		$_SESSION['name']=$userinfo['name'];
		$_SESSION['path']=array();
		$_SESSION['admin']=$userinfo['admin'];
		userPermissions();

		logAction('login',$username);
		if($GLOBALS['resource'] != true)checkLogin();
	}else{
		logAction('loginFail',$username);
		if($GLOBALS['resource'] != true)checkLogin();
	}
}

function userPermissions(){
	global $database;
	if(isset($_SESSION['userid'])){
		$perQuery = "select $GLOBALS[tablePrefix]permissions.*,$GLOBALS[tablePrefix]clients.name as `cname`,$GLOBALS[tablePrefix]clients.path as `cpath`,$GLOBALS[tablePrefix]clients.id as `cid` from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\"";
		$permissions = mysql_query($perQuery,$database) or die(mysql_error());
		$_SESSION["admin.cid"]='';
		$_SESSION["path"]='';
		if(mysql_num_rows($permissions) > 0)
			while($userPermissions = mysql_fetch_assoc($permissions)) {
				#print_r($userPermissions);
				$thispath = $userPermissions['cpath'].'/'.$userPermissions['cname'];
				$_SESSION['path'][]=$thispath;
				$thispath = $userPermissions['cname'];
				$admin   = $userPermissions['admin'];
				$_SESSION["auth.$thispath.view"]=$userPermissions['view'];
				$_SESSION["auth.$thispath.rename"]=$userPermissions['rename'];
				$_SESSION["auth.$thispath.download"]=$userPermissions['download'];
				$_SESSION["auth.$thispath.metaEdit"]=$userPermissions['metaEdit'];
				$_SESSION["auth.$thispath.delete"]=$userPermissions['delete'];
				$_SESSION["auth.$thispath.move"]=$userPermissions['move'];
				$_SESSION["auth.$thispath.folderRename"]=$userPermissions['folderRename'];
				$_SESSION["auth.$thispath.folderDelete"]=$userPermissions['folderDelete'];
				$_SESSION["auth.$thispath.folderMove"]=$userPermissions['folderMove'];
				$_SESSION["auth.$thispath.newFolder"]=$userPermissions['newFolder'];
				$_SESSION["auth.$thispath.upload"]=$userPermissions['upload'];
				if($admin==1){
					$cid = $userPermissions['cid'];
					$_SESSION["auth.$cid.admin"]=1;
					$_SESSION["admin.cid"][]=$cid;
				}
			}
	}
}

// internal functions //

function logAction($type,$details){
	global $database;
	$type = mysql_escape_string($type);
	$details = mysql_escape_string($details);
	$query = "insert into $GLOBALS[tablePrefix]log set user=\"$_SESSION[user]\",ip=\"$_SERVER[REMOTE_ADDR]\",type=\"$type\",details=\"$details\"";
	$result = mysql_query($query,$database);
}

function getUserAuth($type,$path){
	if(isset($_SESSION['userid'])){
		
	
		$paths = preg_split("/\//", $path); // isolate virtual directory name

		return (isset($_SESSION['auth.'.$paths[1].'.'.$type]))?$_SESSION['auth.'.$paths[1].'.'.$type]:false;
	}
}

function getFileInfo($fileid){
	global $database,$filepath,$fileinfo,$imageTypes;
	
	$fileid=mysql_escape_string($fileid);
	$query = "select * from $GLOBALS[tablePrefix]filesystem where id=$fileid";
	$result = mysql_query($query,$database);
	if(mysql_num_rows($result) == 0){
		error('bad fileid');
	}
	
	$file = mysql_fetch_assoc($result);
	
	$fileinfo['filename'] 		= $file['filename'];
	$fileinfo['date'] 		= $file['date'];
	$fileinfo['description'] 	= $file['description'];
	$fileinfo['downloads']		= $file['downloads'];
	$fileinfo['flags']		= $file['flags'];
	$fileinfo['type']		= $file['type'];
	$fileinfo['uploader']		= $file['uploader'];
	$fileinfo['path']		= $file['path'];
	$fileinfo['virtualpath']	= $file['rpath'];
	$fileinfo['size']		= filesize_format($file['size']);
	
	if(preg_match("$imageTypes",$fileinfo['type'])){
	      $fileinfo['image'] = 1;
	}else{
	      $fileinfo['image'] = 0;
	}

	$filePath = $fileinfo['path'];
	/*
	if(isset($_SESSION['path']))
		foreach($_SESSION['path'] as $checkPath){
			#echo "$filePath $checkPath<br>";
			if(substr( $filePath, 0, strlen( $checkPath ) ) == $checkPath){
				$path = substr($filePath,strlen($checkPath));
				$checkPathArray = preg_split("/\//",$checkPath);
				$virtualPath = $checkPathArray[count($checkPathArray)-1];
				$fileinfo['virtualpath'] = "/$virtualPath$path";
	
			}
		}
	*/


	$filepath = $file['path'] . '/' . $file['filename'];
	$userpath = getUserPath($fileinfo['path']); // replaces / with \/ from preg_match

	if(preg_match("/$userpath/i",$filepath)){
		return true;
	}else{
		return false;
	}
}

function getUserPaths(){
	global $database;
	$paths='';
	$query = "select * from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\"";
	$result = mysql_query($query,$database);

	while($clients = mysql_fetch_assoc($result)) {
		$paths[] = $clients['path'].'/'.$clients['name'];
	}
	
	return $paths;
}


function getUserPath($folderPath){
	global $database;
	if(isset($_SESSION['userid'])){
		$dirStructure = preg_split("/\//",$folderPath);
		$rootPath = (isset($dirStructure[1]))?$dirStructure[1]:'';
		$rootPath = mysql_escape_string($rootPath);
		if($rootPath==''){return '';}
		$query = "select * from $GLOBALS[tablePrefix]clients inner join $GLOBALS[tablePrefix]permissions on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id and $GLOBALS[tablePrefix]permissions.userid=$_SESSION[userid] where name=\"$rootPath\"";
		$result = mysql_query($query,$database) or die(mysql_error());
		$file = mysql_fetch_assoc($result);
		return mysql_escape_string($file['path']);
	}
}

function databaseSync($folderpath,$realitivePath=''){
  global $database;
  // get files from $folderpath and put them in array
  if (is_dir($folderpath)) {
    if ($dh = opendir($folderpath)) {
       while (($file = readdir($dh)) !== false) {
         #echo "$file";
         if($file != '.' && $file != '..' && filetype($folderpath . '/' . $file) == 'file' && substr($file,0,1) != '.'){
           $fileid = fileid($folderpath,$file);
		   $files[$file] = array($fileid,'exist');
		   #echo "1 $file<br>";
		 }
       }
       closedir($dh);
    }
  }



  // get files from database
  $query = "select * from $GLOBALS[tablePrefix]filesystem where path=\"".mysql_escape_string($folderpath)."\" and status=\"found\"";
  $result = mysql_query($query,$database);
  while($dirinfo = mysql_fetch_assoc($result)) {
    $filename = $dirinfo['filename'];
	$fileid =   $dirinfo['id'];

	if(isset($files[$filename]) && $files[$filename][0] == $dirinfo['id']){
		$files[$filename][1]='done';
	}else{
		databaseLost($fileid);
	}
  }
  if(isset($files)){
    $ak = array_keys($files);
	for($i=0;$i<sizeof($ak);$i++){
	  $filename = $ak[$i];
	  if($files[$filename][1]!='done'){
	  	#echo "$filename to search<br>";
		if(databaseSearch($folderpath , $filename)){
		  databaseUpdate($folderpath,$filename,$realitivePath);
		}else{
		  databaseAdd($folderpath,$filename,$realitivePath);
		}
	  }
	}
  }
}

function databaseLost($fileid){
  global $database;
  $query = "update $GLOBALS[tablePrefix]filesystem set status=\"lost\" where id=$fileid";
  #echo $query;
  $result = mysql_query($query,$database) or die(mysql_error());
}

function databaseSearch($folderpath,$filename){
  global $database;

  $fileid = fileid($folderpath,$filename);
  $query = "select * from $GLOBALS[tablePrefix]filesystem where id=$fileid";
  $result = mysql_query($query,$database) or die(mysql_error());
  if($fileinfo = mysql_fetch_assoc($result)) {
    if(file_exists($fileinfo['path'].'/'.$fileinfo['filename'])){

	  if($fileinfo['path'] == $folderpath && $fileinfo['filename'] == $filename){
	  	return true;        // file was restored to origional location
	  }else{
	    return false;       // exact file still exists somewhere else
	  }
	}else{
	  // file must have been moved
	  return true;

	}
  }else{
    // file is new
  	return false;
  }
}

function databaseUpdate($folderpath,$filename,$realitivePath){
	global $database,$finfo;
	$fileid = fileid($folderpath,$filename);
	$query = "update $GLOBALS[tablePrefix]filesystem set filename=\"$filename\",path=\"$folderpath\",rpath=\"$realitivePath\",status=\"found\" where id=$fileid";
	$result = mysql_query($query,$database);
}

function databaseAdd($folderpath,$filename,$realitivePath){
	global $database,$rootpath;

	if(function_exists('finfo')){
		$finfo = new finfo( FILEINFO_MIME,"$rootpath/inc/magic" );
		$type = $finfo->file( "$folderpath/$filename" );
	}else if(function_exists('mime_content_type') && mime_content_type("relay.php") != ""){
		$type = mime_content_type("$folderpath/$filename");
	}else{
		if(!$GLOBALS['mime']){
			include_once("inc/mimetypehandler.class.php");
			$GLOBALS['mime'] = new MimetypeHandler();
		}
		$type =  $GLOBALS['mime']->getMimetype("$filename");
	}

	$size = get_size($folderpath.'/'.$filename);
	
	$fileid = fileid($folderpath,$filename);
	
	while(!checkId($fileid)){
		$fileid++;
	}
	
	$query = "insert into $GLOBALS[tablePrefix]filesystem set id=\"$fileid\",filename=\"$filename\",path=\"$folderpath\",rpath=\"$realitivePath\",type=\"$type\",size=\"$size\"";
	$result = mysql_query($query,$database) or die(mysql_error());

	chmod($folderpath . '/' . $filename,0755);
	touch($folderpath . '/' . $filename,$fileid);
}

function checkId($id){
	$query = "select id from $GLOBALS[tablePrefix]filesystem where id=$id";
	$result = mysql_query($query);
	if(mysql_num_rows($result) == 0){
		return true;
	}else{
		return false;
	}
}
function fileid($folderpath,$filename){
	$fileid = stat($folderpath . '/' . $filename);
	return $fileid[9];
}

function error($message){
	echo "{\"bindings\": [ {'error': \"$message\"} ]}";
	exit;
}

/*

THUMBNAIL

*/

function output_handler($in){
  	global $output;
	$output="$in";
}

function getThumb($fileid){
	global $database,$fileinfo;
	
	if(getFileInfo($fileid)){ // if a file type we want to deal with
		if(!checkThumb($fileid)){
			thumbnail($fileid);
		}
		
		$query = "select thumb from $GLOBALS[tablePrefix]filesystem where id=\"".mysql_escape_string($fileid)."\"";
		$result = mysql_query($query,$database);
		
		$fileThumb = mysql_fetch_assoc($result);
		header("Content-type:image/jpeg");
		echo $fileThumb['thumb'];
	}

}

function checkThumb($fileid){
	global $database;
	$query = "select id from $GLOBALS[tablePrefix]filesystem where id=\"".mysql_escape_string($fileid)."\" and thumb !=''";
	$result = mysql_query($query,$database);
	if(mysql_num_rows($result) == 0)
		return false;
	else
		return true;
}

function thumbnail($fileid){

	$thumbsize = 192;
	global $convertpath, $database,$fileinfo,$output,$imageTypes,$resource,$ghostScript;
	$fileid=mysql_escape_string($fileid);
	if(getFileInfo($fileid) && preg_match("$imageTypes",$fileinfo['type']) ){
  		$deletefile = '';
		if (preg_match("/image\/jpeg/",$fileinfo['type'])){$src_img=imagecreatefromjpeg($fileinfo['path'].'/'.$fileinfo['filename']);}
		elseif (preg_match("/image\/png/",$fileinfo['type'])){$src_img=imagecreatefrompng($fileinfo['path'].'/'.$fileinfo['filename']);}
		elseif (preg_match("/application\/pdf/",$fileinfo['type'])){
			$file1 = $fileinfo['path'].'/'.$fileinfo['filename'];
			$file2 = $fileinfo['path'].'/'.$fileinfo['filename'] .'temp';
			#echo "E:/duarte.com/relay/supportapps/gs/gs8.50/bin/gswin32c.exe -q -dNOPAUSE -dBATCH -sDEVICE=jpeg -sOutputFile=\"$file2\" \"$file1\" 2>&1";
			
			$code = "$ghostScript -q -dNOPAUSE -dBATCH -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -sOutputFile=\"$file2\" \"$file1\" 2>&1";
			#if($resource == true)echo "$code";
			$result1 = @exec($code);
			$src_img=imagecreatefromjpeg($file2);
			$deletefile = $file2;
		}elseif(preg_match("/image\/x-photoshop|image\/|application\/postscript/",$fileinfo['type'])){
			#image magic coolthings

			$file1 = $fileinfo['path'].'/'.$fileinfo['filename'];
			$file2 = $fileinfo['path']."/thumb_$fileid.jpg";

			$code = "$convertpath \"$file1\" -render -flatten -resize ".$thumbsize."x".$thumbsize." \"$file2\"";
			#echo "$code";

			$result1 = @exec($code);
			$src_img=imagecreatefromjpeg($file2);
			$deletefile = $file2;
		}
		
		
		$old_x=imageSX($src_img);
		$old_y=imageSY($src_img);
		if ($old_x > $old_y)
		{
			$thumb_w=$thumbsize;
			$thumb_h=$old_y*($thumbsize/$old_x);
		}
		if ($old_x < $old_y)
		{
			$thumb_w=$old_x*($thumbsize/$old_y);
			$thumb_h=$thumbsize;
		}
		if ($old_x == $old_y)
		{
			$thumb_w=$thumbsize;
			$thumb_h=$thumbsize;
		}
		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);

		ob_start("output_handler");
		imagejpeg($dst_img,'',70);
		ob_end_clean();

		$thumb = mysql_escape_string($output);
  		$query = "update $GLOBALS[tablePrefix]filesystem set thumb=\"$thumb\" where id=\"$fileid\"";

		#echo $query;

  		$result = mysql_query($query,$database) || die("angry death");

		if ($deletefile > ''){
			unlink($deletefile);
		}
		#imagedestroy($dst_img);
		#imagedestroy($src_img);
	}
}

/*
UPLOAD
*/

function upload($dir){

        if(getUserAuth('upload',$dir)){
            $userpath = getUserPath($dir).$dir;
    
            $tmp_name = $_FILES["upload"]["tmp_name"];
            $uploadfile = basename($_FILES['upload']['name']);
            $i=1;
            while(file_exists($userpath.'/'.$uploadfile)){
                $uploadfile = $i . '_' . basename($_FILES['upload']['name']);
                $i++;
            }
            
            move_uploaded_file($tmp_name, $userpath.'/'.$uploadfile);
	}
	if(isset($_GET['redir'])){
		header("location: $_GET[redir]");
	}
    	
}

function uploadAuth($path){
	global $uploadDir;
	$path = mysql_escape_string($path);
	jsonStart();
	
	if(getUserAuth('upload',$path)){
		$userpath = getUserPath($path).$path;
		if(is_dir($userpath)){
			$_SESSION['uploadPath'] = $path;
		if(file_exists($uploadDir."stats_".session_id().".txt"))
			unlink($uploadDir."stats_".session_id().".txt");
		if(file_exists($uploadDir."temp_".session_id()))
			unlink($uploadDir."temp_".session_id());
			jsonAdd("\"auth\":\"true\",\"sessionid\":\"".session_id()."\"");
		}else{
			jsonAdd("\"auth\":\"false\",\"error\":\"bad directory\"");
		}
		
	}else{
		jsonAdd("\"auth\":\"false\",\"error\":\"Unauthorized\"");
	}
	echo jsonReturn("bindings");
}

function uploadSmart(){
	global $uploadDir;


	if(!file_exists($uploadDir."stats_".session_id().".txt")){
		jsonStart();
		jsonAdd("\"percent\": 0, \"percentSec\": 0, \"speed\": \"0\", \"secondsLeft\": \"0\", \"done\": \"false\"");
		echo jsonReturn("bindings");
		exit();
	}


	$lines = file($uploadDir."stats_".session_id().".txt");
	jsonStart();

	$percent	=round(($lines[0]/100),3);
	$percentSec	=round($lines[1]/100,4);
	$speed		=filesize_format($lines[2]).'s';

	$secondsLeft	=secs_to_string(round($lines[3]));
	
	$size		=filesize_format($lines[4]).'s';

	

	if($percent == 1){
		// cleanup time
		if(isset($_SESSION['uploadPath'])){

			$path = $_SESSION['uploadPath'];
			$userpath = getUserPath($path).$path;

			$sessionid = session_id();

			$dh = opendir($uploadDir);
		    while (($file = readdir($dh)) !== false) {

		    	$sessionlen = strlen(session_id());
		    	if(substr($file,0,$sessionlen)==session_id()){
		    		$filename = substr($file,$sessionlen+1);
					$uploadfile=$filename;
					$i=1;
					while(file_exists($userpath.'/'.$uploadfile)){
					  $uploadfile = $i . '_' . $filename;
					  $i++;
			        }

					if(file_exists("$uploadDir$file") && !rename("$uploadDir$file","$userpath/$uploadfile")){
						echo "Error";
					}
				}
				
		    }closedir($dh);

		if(file_exists($uploadDir."stats_".session_id().".txt"))
		    	unlink($uploadDir."stats_".session_id().".txt");
		    if(file_exists($uploadDir."temp_".session_id()))
		    	unlink($uploadDir."temp_".session_id());

		}
		$done = "true";
	}else{
		$done = "false";
	}

	jsonAdd("\"percent\": $percent, \"size\": \"$size\",\"percentSec\": $percentSec, \"speed\": \"$speed\", \"secondsLeft\": \"$secondsLeft\", \"done\": \"$done\"");
	echo jsonReturn("bindings");
}


/*
function uploadFiles($path){
  $path = mysql_escape_string($path);
  if(getUserAuth('upload',$path)){
    $userpath = getUserPath($path).$path;
    if(is_dir($userpath)){
		foreach ($_FILES["file"]["error"] as $key => $error) {
		  if ($error == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES["file"]["tmp_name"][$key];
			$uploadfile = basename($_FILES['file']['name'][$key]);
			$i=1;


			while(file_exists($userpath.'/'.$uploadfile)){
			  $uploadfile = $i . '_' . basename($_FILES['file']['name'][$key]);
			  $i++;
	        }

	        move_uploaded_file($tmp_name, $userpath.'/'.$uploadfile);
	        databaseAdd($userpath,$uploadfile);
	        echo "<script>history.go(-1);</script>";
		  }
		}
	}else{
	  error('directory doesnt exist');
	}
  }else{
    error('no auth');
  }
}
*/


/*
functions to do simple things
simple simple simple simple simple simple simple simple simple simple simple
simple simple simple simple simple simple simple simple simple simple simple
simple simple simple simple simple simple simple simple simple simple simple
simple simple simple simple simple simple simple simple simple simple simple

*/


function deleteDir($dir)
{
   if (substr($dir, strlen($dir)-1, 1) != '/')
       $dir .= '/';
   if (is_dir($dir) && $handle = opendir($dir)){
       while ($obj = readdir($handle)){
           if ($obj != '.' && $obj != '..'){
               if (is_dir($dir.$obj)){
                   if (!deleteDir($dir.$obj))
                       return false;
               }
               elseif (is_file($dir.$obj)){
                   if (!unlink($dir.$obj))
                       return false;
               }
           }
       }
       closedir($handle);
       if (!@rmdir($dir))
           return false;
       return true;
   }
   return false;
}

function get_size($path)
   {
   if(!is_dir($path)) return filesize($path);
   if ($handle = opendir("$path")) {
       $size = 0;
       while (false !== ($file = readdir($handle))) {
           if($file!='.' && $file!='..'){
               $size += get_size($path.'/'.$file);
           }
       }
       closedir($handle);
       return $size;
   }
}

function filesize_format($size){

    if( is_null($size) || $size === FALSE || $size == 0 )
    return $size;

  if( $size > 1024*1024*1024 )
    $size = sprintf( "%.1f GB", $size / (1024*1024*1024) );
  elseif( $size > 1024*1024 )
    $size = sprintf( "%.1f MB", $size / (1024*1024) );
  elseif( $size > 1024 )
    $size = sprintf( "%.1f kB", $size / 1024 );
  elseif( $size < 0 )
    $size = '&nbsp;';
  else
    $size = sprintf( "%d B", $size );

  return $size;

}

function secs_to_string ($secs, $long=false)
{
	$initsecs = $secs;
  // reset hours, mins, and secs we'll be using
  $hours = 0;
  $mins = 0;
  $secs = intval ($secs);
  $t = array(); // hold all 3 time periods to return as string
  
  // take care of mins and left-over secs
  if ($secs >= 60) {
    $mins += (int) floor ($secs / 60);
    $secs = (int) $secs % 60;
        
    // now handle hours and left-over mins    
    if ($mins >= 60) {
      $hours += (int) floor ($mins / 60);
      $mins = $mins % 60;
    }
    // we're done! now save time periods into our array
    $t['hours'] = (intval($hours) < 10) ? "" . $hours : $hours;
    $t['mins'] = (intval($mins) < 10) ? "" . $mins : $mins;
  }

  // what's the final amount of secs?
  $t['secs'] = (intval ($secs) < 10) ? "" . $secs : $secs;
  
  // decide how we should name hours, mins, sec
  $str_hours = ($long) ? "hour" : "hour";
  $str_mins = ($long) ? "minute" : "min";
  $str_secs = ($long) ? "second" : "sec";

  // build the pretty time string in an ugly way
  $time_string = "";
  
  
  $time_string .= ($t['hours'] > 0) ? $t['hours'] . " $str_hours" . ((intval($t['hours']) == 1) ? " " : "s ") : "";
  #$time_string .= ($t['mins']) ? (($t['hours']) ? ", " : "") : "";
  $time_string .= ($t['mins']) ? $t['mins'] . " $str_mins" . ((intval($t['mins']) == 1) ? " " : "s ") : "";
  #$time_string .= ($t['hours'] || $t['mins']) ? (($t['secs'] > 0) ? ", " : "") : "";
  
  if($initsecs < 120){
	  $time_string .= ($t['secs']) ? $t['secs'] . " $str_secs" . ((intval($t['secs']) == 1) ? "" : "s ") : " ";
  }else{
    if($secs > 30){
		$pre = ">";
	}else{
		$pre = "about";
	}
  	$time_string = "$pre $time_string";
  }
  
  return empty($time_string) ? 0 : $time_string;
}


/*
JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF
JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF
JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF
JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF JSON STUFF
*/

function jsonStart(){
  global $json;
  $json = '';
}

function jsonAdd($jsonLine){
  global $json;
  if($json != '')
    $json .= ",";
  $json .= "{ $jsonLine }";
}

function jsonReturn($variableName){
  global $json;
  return "{\"bindings\": [ $json ]}";
}

?>
