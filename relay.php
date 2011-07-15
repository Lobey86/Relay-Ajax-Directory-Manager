<?php
// error_reporting(2);

if(!isset($resource))$resource = "0";
// session initilization
session_start();
include_once("conf.inc.php");
 
// Routing
// ***************************************************************************
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
			case "regenerateThumbs":
				regenerateThumbs();
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
				if(isset($_GET['path'])){
					getFile($_GET['path']);
				}
				break;
			case "folderIsDeletable": 
				if(isset($_POST['path'])){
					folderIsDeletable($_POST['path']);
				}
				break;
			case "getFilePackage":
				if(isset($_GET['paths'])){
					getFilePackage($_GET['paths']);
				}
				break;
			case "emailFilePackage":
				if(isset($_GET['fileid'],$_GET['to'],$_GET['from'],$_GET['message'])){
					emailFilePackage($_GET['fileid'],$_GET['to'],$_GET['from'],$_GET['message']);
				}
				break;	
			case "getMeta":
				if(isset($_POST['path'], $_POST['filename'], $_POST['id'])){
					getMeta($_POST['path'], $_POST['filename'], $_POST['id']);
				}
				break;
			case "getFolderMeta":
				if(isset($_POST['path'])){
					getFolderMeta($_POST['path']);
				} 
				break;
				case "folderIsVirtual":
					if(isset($_POST['path'])){
						folderIsVirtual($_POST['path']);
					} 
					break;
			case "fileIsWritable":
				if(isset($_POST['path'])){
					fileIsWritable($_POST['path']);
				} 
				break;	
			case "setMeta":
				if(isset($_POST['path'],$_POST['filename'],$_POST['id'],$_POST['description'],$_POST['flags'])){
					setMeta($_POST['path'],$_POST['filename'],$_POST['id'],$_POST['description'],$_POST['flags']);
				}
				break;
			case "fileRename":
				if(isset($_POST['path'], $_POST['filename'], $_POST['id'], $_POST['newName'])){
					fileRename($_POST['path'], $_POST['filename'], $_POST['id'], $_POST['newName']);
				}
				break;
			case "fileMove":
				if(isset($_POST['path'],$_POST['filename'], $_POST['id'], $_POST['where'])){
					fileMove($_POST['path'],$_POST['filename'], $_POST['id'], $_POST['where']);
				}				
				break;
			case "fileDelete":			
				if(isset($_POST['path'], $_POST['filename'], $_POST['id'])){
					fileDelete($_POST['path'], $_POST['filename'], $_POST['id']);
				}
				break;
			case "folderRename":
				if(isset($_POST['path'],$_POST['name'],$_POST['newname'])){
					folderRename($_POST['path'],$_POST['name'],$_POST['newname']);
				}
				break;
			case "folderMove":
				if(isset($_POST['name'], $_POST['path'],$_POST['where'])){
					folderMove($_POST['name'], $_POST['path'],$_POST['where']);
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
			case "fileExists":
				if(isset($_POST['path'])){
					fileExists($_POST['path']);
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
				if(isset($_GET['path'], $_GET['filename'])){
				getThumb($_GET['path'], $_GET['filename']);
				}
				break;
		}
	}
}



// Utility Methods
// ***************************************************************************

// Takes an array of paths and returns a condensed array of paths, removing 
// any nested folders
//
// example:
// condensePathsArray( array('c/d/e','c/d','c/x/y') )
// returns ('c/d', 'c/x/y')
function condensePathsArray($arr){
	// Remove items with same beginnings
	for ($i=0; $i < count($arr); $i++) {
	$whoimcheckingfor = $arr[$i];

		for($j=0; $j < count($arr); $j++){
			$whoimchecking = $arr[$j];
			if( startsWith($whoimchecking, $whoimcheckingfor) && $whoimchecking != $whoimcheckingfor){
				unset($arr[$j]);
			}
		}

	}

	//Remove identical items
	return array_unique($arr);
}

// Checks existance of file/folder
function fileExists($path){
	logAction('exists', $path);
	
	jsonStart();
	
	if( permForPath($path, 'view') ){

		if( file_exists( $path ) ){
			jsonAdd("\"exists\": true  ");
		}else{
			jsonAdd("\"exists\": false  ");
		}

		echo jsonReturn('exists');		
		
	}else{
		error('You do not have permission for this action.');
	}
	
}

// Legacy Method
function deleteDir($dir){
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

// Legacy Method
function get_size($path){
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

// Legacy Method
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

// Legacy Method
function getUserPath($folderPath){
	global $database;
	if(isset($_SESSION['userid'])){
		$dirStructure = preg_split("/\//",$folderPath);
		$rootPath = (isset($dirStructure[1]))?$dirStructure[1]:'';
		$rootPath = mysql_escape_string($rootPath);
		if($rootPath==''){return '';}
		$query = "select * from $GLOBALS[tablePrefix]clients inner join $GLOBALS[tablePrefix]permissions on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id and $GLOBALS[tablePrefix]permissions.userid=$_SESSION[userid] where name=\"$rootPath\"";
		$result = mysql_query($query,$database) or die(mysql_error());
		// TODO: Query
		
		$file = mysql_fetch_assoc($result);
		return mysql_escape_string($file['path']);
	}
}

// Checks if string starts with another string. Returns bool
// Example startsWith('.htaccess', '.') // returns true
function startsWith($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

// Returns a path sans preceding slash if one exists.
function localizePath($path){
	if( startsWith($path, '/') ){
		$length = (strlen($path) -1);
		return ( substr($path, -$length) );
	}else{
		return $path;
	}
}

// Write to log table
function logAction($type,$details){
	global $database;
	$type = mysql_escape_string($type);
	$details = mysql_escape_string($details);
	$query = "insert into $GLOBALS[tablePrefix]log set user=\"$_SESSION[user]\",ip=\"$_SERVER[REMOTE_ADDR]\",type=\"$type\",details=\"$details\"";
	$result = mysql_query($query,$database);
}

// Scans a directory(optionally recursively) and returns an array of the 
// contents.
function directoryToArray($directory, $recursive) {
	
	// Output:
	// (
	// 		("type" => "file", "path" => "home/pictures", "name" => "joannaAngel.jpeg"),
	// 		("type" => "directory", "path" => "home/pictures", "name" => "newYears 2008")	
	//	
	//	)
	
    $array_items = array();
    if ( $handle = opendir($directory) ){
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($directory. "/" . $file)) {
                    if($recursive) {
                        $array_items = array_merge($array_items, directoryToArray($directory. "/" . $file, $recursive));
                    }
					$item = array("type" => "directory", "path" => $directory, "name" => $file);
                    $array_items[] = $item;

                } else {
					$item = array("type" => "file", "path" => $directory, "name" => $file);
                    $array_items[] = $item;



                }
            }
        }
        closedir($handle);
    }
    return $array_items;
}




// Download & Email Methods
// ***************************************************************************

// Emails zip file from contents of $paths argument.
function emailFilePackage($paths,$to,$from,$message){
	
		$fileids = preg_split("/\,/",$fileids);
	
		$boundary = "DU_" . md5(uniqid(time()));	
		$headers = "From: $from". "\r\n";
		$headers .= "MIME-Version: 1.0"."\r\n";
		$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\";". "\r\n";
		// Don't adjust indenting on this.
	$mailMessage = "--$boundary
Content-Type: text/plain; charset=\"iso-8859-1\"
Content-Transfer-Encoding: 7bit

$message
";

		$filename = 'package.zip';
	
			
		$ct = $fileinfo['type'];
		if($ct=='')$ct = 'application/force-download';
		$mailMessage.= "--$boundary\nContent-Type: $ct\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"$filename\"\n\n";
		$mailMessage.= chunk_split(base64_encode( getFilePackage($paths, true) ));


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

// Creates zip file from contents of $paths argument.
function getFilePackage($paths, $returnContent = false){
	// paths will look like:
	// "/filestore~kristinaRose.jpeg,/filestore/pics~harmonyRose.jpeg"
	
	logAction('getFilePackage', $paths);
	
	$paths = preg_split("/\,/",$paths);
	
	$files = array();
	$fileCount = 0;
	include_once("inc/createZip.inc.php");
	$createZip = new createZip;

	foreach($paths as $path){
		$i = preg_split("/\~/",$path);
		$files[] = array("path" => $i[0], "filename" => $i[1]);
	}

	foreach($files as $file){
		$filePath = $file['path'] . '/' . $file['filename'];
		
		if( file_exists($filePath) ){
			$createZip -> addFile( file_get_contents($filePath), $file['filename']);
			$fileCount++;
		}
	}
	
	if($fileCount > 0){
		if($returnContent != true){
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: Binary");
			header("Content-disposition: attachment; filename=\"package.zip\"");
			echo   $createZip -> getZippedfile();
		}else{
			return $createZip -> getZippedfile();
		}
	}else{
		error('No files zipped.');
	}
}




// Upload Methods
// ***************************************************************************

// Legacy Method
function upload($dir){
	
	logAction('upload','!!!!!!!!!!!!!!!!!!!!!');
	
	if( permForPath($dir, 'write') ){
		
		$userpath = $dir;

		

		$tmp_name = $_FILES["upload"]["tmp_name"];
		$uploadfile = basename($_FILES['upload']['name']);
		$i=1;
		while(file_exists($userpath.'/'.$uploadfile)){
		    $uploadfile = $i . '_' . basename($_FILES['upload']['name']);
		    $i++;
		}

		move_uploaded_file($tmp_name, $userpath.'/'.$uploadfile);


		if(isset($_GET['redir'])){
			header("location: $_GET[redir]");
		}		
		
	}else{
		error('You do not have permission for this action.');
	}
}

// Legacy Method
function uploadAuth($path){
	global $uploadDir;
	$path = mysql_escape_string($path);
	jsonStart();
	
	if( permForPath($path, 'write') ){
		$userpath = $path;
		
		if(is_dir($userpath)){
			
			$_SESSION['uploadPath'] = $path;
			
			if(file_exists($uploadDir."stats_".session_id().".txt")){
				unlink($uploadDir."stats_".session_id().".txt");
			}
			
			if(file_exists($uploadDir."temp_".session_id())){
				unlink($uploadDir."temp_".session_id());
			}
			
			jsonAdd("\"auth\":\"true\",\"sessionid\":\"".session_id()."\"");
		}else{
			jsonAdd("\"auth\":\"false\",\"error\":\"bad directory\"");
		}
		
	}else{
		jsonAdd("\"auth\":\"false\",\"error\":\"Unauthorized\"");
	}
	echo jsonReturn("bindings");
}

// Legacy Method
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

// Legacy Method
function secs_to_string ($secs, $long=false){
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




// JSON Methods
// ***************************************************************************
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

function error($message){
	echo "{\"bindings\": [ {\"error\": \"$message\"} ]}";
	exit;
}



// Thumbnail Methods
// ***************************************************************************


// Retrieve thumbnail, creates one if needed.
function getThumb($path, $filename, $regen = false){
	global $thumbnailPrefix;

	if( !permForPath($path, 'read') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	$srcImagePath = $path.'/'.$filename;
		
	if( file_exists( $srcImagePath ) ){	

		// FIXME: USER AUTH		
		//if( getUserAuth('view', $path) ){
			
			logAction('getThumb', $path . '/' .$filename);
	    
			$file = explode('.', $filename);
			
			if( count($file) === 1 ){ // Handle no extension
				return false;
			}
			
			$extension = strtolower( array_pop($file) );
			$filenameBase = implode('.', $file);
		
			$thumbPath = $path . '/' . $thumbnailPrefix . $filenameBase .'.'. $extension . '.jpg';
								
			if( !file_exists( $thumbPath ) ){
				thumbnail($path,  $filename);
			}
		
			if($regen){
				return;
			}
		
			$thumb = file_get_contents( $thumbPath );
			header("Content-type:image/jpeg");
			echo $thumb;
		 
		//}
	}else{
		// FIXME: error condition
	}
}

// Creates a thumbnail of the provided image and stores it in /thumbnails
function thumbnail($path, $filename){
	
	logAction('thumbnail', $path . '/' .$filename);
	
	
	global $thumbnailPrefix, $ghostScript;
	
	$file = explode('.', $filename);
	$extension = strtolower( array_pop($file) );
	$filenameBase = implode('.', $file);
	
	$srcImagePath = $path.'/'.$filename;
	$thumbPath = $path.'/'. $thumbnailPrefix .$filenameBase .'.'. $extension . '.jpg';
	
	$thumbsize = 192;
	
	// Set up source image
	if($extension == 'jpeg' || $extension == 'jpg'){
		$src_img=imagecreatefromjpeg($srcImagePath); 
	}else if($extension == 'png'){
		$src_img=imagecreatefrompng($srcImagePath);
	}else if($extension == 'gif'){
		$src_img=imagecreatefromgif($srcImagePath);
	}else if($extension == 'pdf'){
		$file1 = $srcImagePath;
		$file2 = $srcImagePath .'temp';
		$code = "$ghostScript -q -dNOPAUSE -dBATCH -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -sOutputFile=\"$file2\" \"$file1\" 2>&1";
		$result1 = @exec($code);
		$src_img=imagecreatefromjpeg($file2);
		$deletefile = $file2;
	}else if($extension == 'x-photoshop' || $extension == 'postscript'){
		$file1 = $srcImagePath;
		$file2 = $srcImagePath . 'temp';
		$code = "$convertpath \"$file1\" -render -flatten -resize ".$thumbsize."x".$thumbsize." \"$file2\"";
		$result1 = @exec($code);
		$src_img=imagecreatefromjpeg($file2);
		$deletefile = $file2;
	}
	
	if( file_exists( $deletefile ) ){
		unlink($deletefile);
	}
	
	// Set thumbnail dimensions	
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
	
	ob_start(); // FIXME: output_handler needed still?
	imagejpeg($dst_img, $thumbPath, 70);
	ob_end_clean(); 
}








// Creates thumbnails for all images, deleting all old thumbs
function regenerateThumbs(){
	global $thumbnailPrefix, $database;
	

	// Get all virt. dirs
	$query = "select * from $GLOBALS[tablePrefix]clients";
	$response = mysql_query($query, $database);
	
	
	// Put all virt. directory paths into an array
	$dirs = array();
	while( $client = mysql_fetch_assoc($response) ){
		$folder = $client['path'].'/'.$client['name'];
		
		$dirs[] = $folder;
	}
	
	// Remove any nested virt. directories 
	$conDirs = condensePathsArray($dirs);

	// Regen thumbs for each virt. directory
	foreach($conDirs as $folder){

		logAction('regen folder', $folder);

		$filestructure = directoryToArray($folder, true);
		foreach ($filestructure as $item) {
 
			$name = $item['name'];
			$type = $item['type'];
			$path = '/' . $item['path'];

			if($type == 'file'){
				// Remove pre-existing thumb
				if( startsWith($name, $thumbnailPrefix) ){
					unlink($path.'/'.$name);
				}

				getThumb($path, $name, true);	
			}
		}
		
	}


		

	
	
	
	
}




// Folder Methods
// ***************************************************************************

// Gets folder contents
function getFolder($path){
	global $database, $resource, $dateFormat, $thumbnailPrefix;

	userPermissions();
	$output = '';
	jsonStart();
	$path = mysql_escape_string($path);


	// Get virtual directories ===============================================
	if($path == '' || $path == '/'){

		// Get permissions for folders?
		$query = "select * from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\" and $GLOBALS[tablePrefix]clients.name =\"$_SESSION[user]\" order by display";
		$result = mysql_query($query,$database);

		// ????
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
				
				
				$displayName = $clients[display];
				$scheme = $clients[scheme];
				$name = $clients[name];
								
				$q = "select * from $GLOBALS[tablePrefix]clients where name=\"$name\" ";
				$res = mysql_query($q, $database);
				
				$path = '';
				
				while( $c = mysql_fetch_assoc($res) ){
					$path = $c['path'];
				}
				
				$output .=  jsonAdd("\"displayname\":\"$displayName\",\"scheme\":\"$scheme\",\"type\": \"directory\", \"name\": \"$name\", \"path\": \"$path\",\"virtual\":\"$virtual\"");

			}
		}
		$output .= jsonReturn('getFolder');
	}
	
	if($output > ''){
		if($resource != true){
			echo $output;
			die;
		}else{
			return $output;
		}
	}	
	
	// Non Virtual Directories ===============================================

		//logAction('list',$path);
		$fullpath = getUserPath($path).$path;

		if (is_dir($fullpath)) {
						
			if( !permForPath($path, 'read') ){
				jsonStart();
				error('You do not have permission for this action.');
				return false;
			}
			
			if ($dh = opendir($fullpath)) {
				while (($file = readdir($dh)) !== false) {

					if($file != '.' && $file != '..'){

						if(filetype($fullpath . '/' . $file) == 'dir'){
							jsonAdd("\"type\": \"directory\", \"name\": \"$file\", \"path\": \"$path\""); 
						}else{
							// Ignore hidden .files
							if (!startsWith($file,'.') && !startsWith($file,$thumbnailPrefix)) {
								
								// id is only for providing the HTML elements a unique id.
								$id = str_replace("/","_",($path . '/' . $file));
								$id = str_replace(".","_",$id);
								// FIXME: inputting bullshit flags
								jsonAdd("\"id\": \"$id\", \"path\":\"$path\", \"type\": \"file\", \"name\": \"$file\",\"date\":\"\",\"flags\": \"normal\"");
							}
						}
						
					}
				}
				closedir($dh); 
			}
		}else{
			error("directory doesnt exist $fullpath");
		}

		$output .= jsonReturn('getFolder');
  
		if($resource != true)
			echo $output;
		else
			return $output;
  
	// }else{
	// 	error('no auth to view');
	// }
}

// Gets metadata for a directory
function getFolderMeta($path){ //=============================================
	jsonStart();
	
	$path = mysql_escape_string($path);
	
	// FIXME: USER AUTH
	//if(getUserAuth('view',$path)){		
		$size = filesize_format(get_size($path));
		$name = basename($path);
		jsonAdd("\"name\": \"$name\", \"size\": \"$size\"");
		echo jsonReturn('getFolderMeta');
	// }else{
	// 	error('access denied');
	// }
}

// Renames directory & upates DB records
function folderRename($path,$name,$newname){
	global $database;

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	$currPath = $path . '/' . $name; 
	$newPath  = $path . '/' . $newname;
	
	if( is_dir($currPath) ){
		
		// FIXME: user auth
	    //if(getUserAuth('folderRename',$path)){

			// Remove original if it exists
			if( is_dir($newPath) ){
				folderDelete($newPath);			
			}

			if( rename($currPath, $newPath) ){
			
				// Handle file metadata records
				$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath like \"$currPath%\"";
				$result = mysql_query($query,$database);
			
				if(mysql_num_rows($result) > 0){

					while ($row = mysql_fetch_assoc($result)) {
						$oldPath = $row['rpath'];
						$oldFilename = $row['filename'];
					
						$currIdStub = str_replace("/","_",$currPath);
						$newIdStub = str_replace("/","_",$newPath);
					
						$newFilename = str_replace($currIdStub, $newIdStub, $oldFilename);
					
						$query = "update $GLOBALS[tablePrefix]filesystem set filename=\"$newFilename\", rpath=\"$newPath\" where rpath=\"$oldPath\" and filename=\"$oldFilename\" ";
						mysql_query($query,$database); 
					}
				}

				logAction('folderRename', $currPath . ' to ' .$newPath);
			}else{
				// FIXME: handle error
				error('Rename failed');
			}
			
		//}
	}else{
		// FIXME: handle error
		error('Directory doesn\'t exist');
	}
}

// Moves folder & updates any DB records
function folderMove($name, $path, $where){
	global $database;

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	$currPath = $path . '/' . $name; 
	$newPath  = $where . '/' . $name;
	
	if( is_dir( $currPath ) ){

		//if(getUserAuth('folderMove',$path)){


			// Remove original if it exists
			if( is_dir($newPath) ){
				folderDelete( $newPath );			
			}

			if( rename($currPath, $newPath) ){
			
				// Handle file metadata records
				$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath like \"$currPath%\"";
				$result = mysql_query($query,$database);
			
				if(mysql_num_rows($result) > 0){

					while ($row = mysql_fetch_assoc($result)) {
						$oldPath = $row['rpath'];
						$oldFilename = $row['filename'];
					
						$currIdStub = str_replace("/","_",$currPath);
						$newIdStub = str_replace("/","_",$newPath);
					
						$newFilename = str_replace($currIdStub, $newIdStub, $oldFilename);
					
						$query = "update $GLOBALS[tablePrefix]filesystem set filename=\"$newFilename\", rpath=\"$newPath\" where rpath=\"$oldPath\" and filename=\"$oldFilename\" ";
						mysql_query($query,$database); 
					}

					logAction('folderMove', $currPath . ' to ' .$newPath); 	
				}
	
			}else{
				error('Move failed');
			}
		
		//}
	}else{
		error('Directory doesn\'t exist');
	}
}

function folderIsVirtual($path, $rtn=false){
	$path = mysql_escape_string($path);	
	global $database;
	
	$tmp = explode('/', $path);
	$name = array_pop($tmp);
	$tPath = implode('/', $tmp);
	
	$query = "select * from $GLOBALS[tablePrefix]clients where path=\"$tPath\" and name=\"$name\" ";	
	$result = mysql_query($query, $database);
	
	$isVirt = false;
	if(mysql_num_rows($result) > 0){
		$isVirt = true;
	}

	if($rtn){
		return $isVirt;
	}else{
		$isVirt = $isVirt ? 'true' : 'false'; 
		jsonStart();
		jsonAdd("\"virtual\": $isVirt ");
		echo jsonReturn('folderIsVirtual');
	}
	
	
}

// Deletes a directory and all files/file records within it
// ===========================================================================
function folderDelete($path){
	global $database;
	$path = mysql_escape_string($path);	

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}
	
	//fileIsWritable($path, $rtn = false)

	if(deleteDir($path)){
		// Remove db records
		$query = "delete from $GLOBALS[tablePrefix]filesystem where rpath like \"$path%\"";
		$result = mysql_query($query,$database);
		logAction('folderDelete', $path);
	}else{
		// FIXME: handle error condition
		echo "oops somethings wrong";
	}

} 

// Creates a new (physical) directory
// ===========================================================================
function newFolder($name, $path){
	$name = mysql_escape_string($name);
	$path = mysql_escape_string($path);
	$fullPath = $path.'/'.$name;

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	if( is_dir($path) ){


		$i = 1;
		$append = "";

		while( is_dir($fullPath.$append) ){
			$append = " $i";
			$i++;
		}


		if( mkdir($fullPath.$append) ){
			logAction('newFolder', $fullPath);
		}else{
			// FIXME: handle error condition
			error('new folder');
		}
	}
}

// Determines if file writable.
function folderIsDeletable($path, $rtn = false){

	logAction('folderIsDeletable', $path);
	jsonStart();

	if( !permForPath($path, 'write') ){
		jsonStart();
		jsonAdd("\"writable\": false ");
		echo jsonReturn('folderIsDeletable');
		return false;
	}

	if( is_dir($path) ){

		$filestructure = directoryToArray($path, true);
		$writable = true;
	
		foreach ($filestructure as $item) {
			$name = $item['name'];
			$type = $item['type'];
			$path = $item['path'];

			if( !fileIsWritable($path .'/'. $name, true) ){
				$writable = false;
			}
		}
	
		if($rtn){
			return $writable;
		}else{
			if($writable){
				jsonAdd("\"writable\": true ");
			}else{
				jsonAdd("\"writable\": false  ");
			}
		}
		
	}else{
		error('File doesn\'t exist.');
	}

	echo jsonReturn('folderIsDeletable');

}


// File Methods
// ***************************************************************************

// Determines if file writable.
function fileIsWritable($path, $rtn = false){
	
	logAction('fileIsWritable', $path);
	
	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}
	
	jsonStart();
	
	if( file_exists($path) ){
		
		$permissions = substr( decoct( fileperms( $path ) ), -3);
		$permissions += 0; // Cast as int
		$writable = ($permissions > 640)? true: false; 

		if($rtn){
			return $writable;
		}else{
			if($writable){
				jsonAdd("\"writable\": true ");
				jsonAdd("\"perm\": $permissions ");
			}else{
				jsonAdd("\"writable\": false  ");
				jsonAdd("\"perm\": $permissions ");
			}
		}
	}else{
		error('File doesn\'t exist.');
	}
	
	echo jsonReturn('fileIsWritable');
}


// Renames a file and any associated thumbnail or db records
function fileRename($path, $filename, $id, $newName){
	global $database, $thumbnailPrefix;

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	$path = str_replace("//","/",$path);
	$path = str_replace("..","",$path);
	$newName = str_replace("//","/",$newName);
	$newName = str_replace("//","/",$newName);
	$filename = str_replace("//","/",$filename);
	$filename = str_replace("//","/",$filename);

	$filePath = $path . '/' . $filename;
	$newPath = $path . '/' . $newName;
	
	if( file_exists( $filePath ) ){
	
		// FIXME: user auth
		//if( getUserAuth('rename',$path) ){
	
			// Remove original if it exists
			if( file_exists( $newPath ) ){
				$id = $newPath;
				$id = str_replace("/","_",$id);
				$id = str_replace(".","_",$id);
				fileDelete($path, $newName, $id);			
			}
	
			// Image
			rename($filePath, $newPath);

			// Thumbnail
			$tmp = explode('.', $filename);
			
			
			if( count($tmp) > 1 ){ // Handle rename w/o an extension
				$oldImageExtension = array_pop($tmp);
				$oldImageBasename = implode('.', $tmp);
			}else{
				$oldImageBasename = implode('.', $tmp);
			} 
			
			$oldThumbPath = $path . '/' .$thumbnailPrefix.  $oldImageBasename .'.'.$oldImageExtension.'.jpg';
			$newThumbPath = $path . '/' .$thumbnailPrefix.  $newImageBasename .'.'.$oldImageExtension.'.jpg';

			if( file_exists( $oldThumbPath ) ){
				
				// Remove any existing thumbnail by same name
				if( file_exists($newThumbPath) ){
					fileDelete($newThumbPath);
				}
				
				rename($oldThumbPath, $newThumbPath);
			}

			// Handle any DB records
			$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\"";
			$result = mysql_query($query,$database);
					
			if(mysql_num_rows($result) > 0){
				$newId = str_replace("/","_",$newPath);
				$newId = str_replace(".","_",$newId);
			
				$query = "update $GLOBALS[tablePrefix]filesystem set filename=\"$newId\" where rpath=\"$path\" and filename=\"$id\" ";
				mysql_query($query,$database);
				logAction('fileRename', 'Updating metadata');
			}

			// Rename file
			rename($filePath, $newPath);
			logAction('fileRename', $filePath . ' to ' . $newPath);

		//}
	}
}

// Downloads file (not zipped)
function getFile($path){
	logAction('getFile',$path);


	if( !permForPath($path, 'read') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	// FIXME: user auth

	if( file_exists($path) ){
		//if( getUserAuth('view',$path) ){		
		
		
			header("Pragma: public"); 
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false); 
			//header("Content-type: $fileinfo[type]");
			header("Content-Transfer-Encoding: Binary");
			header("Content-length: ".filesize($path));
			header("Content-disposition: attachment; filename=\"".basename($path)."\"");
			readfile("$path");
		
		//}
	}
}

// Deletes a file and any associated thumbnail or db records
function fileDelete($path, $filename, $id){
	global $database, $thumbnailPrefix;

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	$filePath = $path . '/' . $filename;
	
	if( file_exists($filePath) ){

		// FIXME: user auth
		//if( getUserAuth('view',$path) ){
				
			logAction('fileDelete', $filePath);
		
			// Image
			unlink($filePath);
		
			$tmp = explode('.', $filename);
			$imageExtension = array_pop($tmp);
			$imageBasename = implode('.', $tmp);
		
			// Thumbnail
			$thumbPath = $path . '/' .$thumbnailPrefix. $imageBasename.'.'.$imageExtension. '.jpg';
			if( file_exists( $thumbPath) ){
				unlink( $thumbPath );
			}

			// Database
			$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\"";
			$result = mysql_query($query,$database);
				
			if(mysql_num_rows($result) > 0){
				$query = "delete from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\" ";
				mysql_query($query,$database);
				logAction('fileDelete', 'Deleting metadata: ' . $filePath);
			}
		//}
	}
}

// Moves file and updates any database records
function fileMove($path, $filename, $id, $where){
	global $database, $thumbnailPrefix;

	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}

	$path = str_replace("//","/",$path);
	$path = str_replace("..","",$path);
	$where = str_replace("//","/",$where);
	$where = str_replace("//","/",$where);
	$filename = str_replace("//","/",$filename);
	$filename = str_replace("//","/",$filename);

	$filePath = $path . '/' . $filename;
	$newPath = $where . '/' . $filename;
	
	if( file_exists($filePath) ){

		if( is_dir($where) ){

			//Remove original if it exists
			if( file_exists($newPath) ){
				$id = $newPath;
				$id = str_replace("/","_",$id);
				$id = str_replace(".","_",$id);
				fileDelete($where, $filename, $id);			
			}

			// Handle any DB records
			$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\"";
			$result = mysql_query($query,$database);
					
			if(mysql_num_rows($result) > 0){
				$newId = str_replace("/","_",$newPath);
				$newId = str_replace(".","_",$newId);

				$query = "update $GLOBALS[tablePrefix]filesystem set rpath=\"$where\", filename=\"$newId\" where rpath=\"$path\" and filename=\"$id\" ";
				mysql_query($query,$database);
				logAction('fileMove', 'Updating metadata');
			}

			// Rename file
			rename($filePath, $newPath);
			
			// Thumbnail
			$tmp = explode('.', $filename);
			$oldImageExtension = array_pop($tmp);
			$oldImageBasename = implode('.', $tmp);

			$tmp = explode('.', $newName);
			$newImageExtension = array_pop($tmp);
			$newImageBasename = implode('.', $tmp);

			$oldThumbPath = $path . '/'.$thumbnailPrefix.$oldImageBasename.'.'.$oldImageExtension.'.jpg';
			$newThumbPath = $path . '/'.$thumbnailPrefix.$newImageBasename.'.'.$oldImageExtension.'.jpg';

			if( file_exists($oldThumbPath) ){
				
				// Remove any exising thumbnail by the same name
				if( file_exists($newThumbPath) ){
					fileDelete($newThumbPath);
				}
				
				rename($oldThumbPath, $newThumbPath);
			}

		}else{
			error('New directory doesn\'t exist');
		}
	}
}

// Gets metadata for a file
function getMeta($path, $filename, $id, $rtn = false){
	global $database;

	$filePath = $path. '/' . $filename;

	if( file_exists( $filePath ) ){
		
		if(!$rtn){
			jsonStart();
		}

		$stats = stat($filePath);
		$date = date('M/j/Y h:i', $stats[9]);

		if($date == 'Dec/31/1969 07:00'){
			$date = 'unknown';
		}

		$size = round( ($stats[7])/1000, 2 ) . 'KB';
		$flag = null;
		$description = null;
		$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\"";
		$result = mysql_query($query,$database);
		if(mysql_num_rows($result) > 0){			
			while ($row = mysql_fetch_assoc($result)) {
				$flag = $row['flags'];
				$description = $row['description'];
			}
		}

		$file = explode('.', $filename);
		if( count($file) > 1 ){ // Handle no extension
			$extension = strtolower( array_pop($file) );

			if( $extension === 'jpg' || 
					$extension === 'jpeg' || 
					$extension === 'gif' || 
					$extension === 'png' || 
					$extension === 'pdf' ){
				$image = true;
			}
		}

		$extension = array_pop($file);
		$filenameBase = implode('.', $file);

		if($rtn){
			return array('filename'=>$filename, 'date'=>$date, 'size'=>$size, 'path'=>$path, 'image'=>$image, 'flag'=>$flag, 'description'=>$description);
		}

		jsonAdd("\"edit\": true"); // FIXME: ???
		jsonAdd("\"filename\": \"$filename\", \"date\": \"$date\", \"size\": \"$size\", \"path\": \"$path\", \"image\": \"$image\", \"flag\": \"$flag\", \"description\": \"$description\"");
		echo jsonReturn('getMeta');
		
	}else{
		error('File doesn\'t exist: ' . $filePath);
	}
}

// Sets metadata for a file
function setMeta($path, $filename, $id, $description, $flags){
	global $database;
	
	if( !permForPath($path, 'write') ){
		jsonStart();
		error('You do not have permission for this action.');
		return false;
	}
	
	$filename 	 = mysql_escape_string($filename);
	$path    	 = mysql_escape_string($path);
	$description = mysql_escape_string($description);
	$flags 		 = mysql_escape_string($flags);
	$id 		 = mysql_escape_string($id);
	
	$filePath = $path. '/' . $filename;
	if( file_exists($filePath) ){
			$query = "select * from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\"";
			$result = mysql_query($query,$database);

			// Don't keep records of files without metadata
			$deleteRecord = false;
			if( empty($flags) && empty($description) ){
				$deleteRecord = true;
			}

			if(mysql_num_rows($result) > 0){	
				// Delete record		
				if($deleteRecord){
					logAction('setMeta', 'Deleting metadata: ' . $filePath);
					$query = "delete from $GLOBALS[tablePrefix]filesystem where rpath=\"$path\" and filename=\"$id\"";
					$result = mysql_query($query,$database);
				// Update record
				}else{
					logAction('setMeta', 'Updating metadata: ' . $filePath);
					$query = "update $GLOBALS[tablePrefix]filesystem set description=\"$description\",flags=\"$flags\" where rpath=\"$path\" and filename=\"$id\"";
					mysql_query($query,$database);
				}

			}else if(!$deleteRecord){
				// Create record
				logAction('setMeta', 'Creating metadata: ' . $filePath);
				$query = "insert into $GLOBALS[tablePrefix]filesystem set filename=\"$id\",path=\"\",rpath=\"$path\",type=\"\",size=\"\", status=\"\", description=\"$description\",flags=\"$flags\"";
				mysql_query($query,$database);
			}
			
	}
}





// Misc Methods
// ***************************************************************************

// Searches file & folder names for partial matches of search terms
function search($terms){
	// TODO: add metadata description search too.
	global $thumbnailPrefix, $database;
	$terms = mysql_escape_string($terms);
	$terms = explode(' ', $terms);
	
	jsonStart();
	
	$results = 0;
			
	// Get all virt. dirs 
	$query = "select * from $GLOBALS[tablePrefix]clients";
	$response = mysql_query($query, $database);
	
	// Add virt. dir paths to an array
	$dirs = array();
	while( $client = mysql_fetch_assoc($response) ){
		$folder = $client['path'].'/'.$client['name'];
		
		// Only provide virtual dirs we have access to.
		if( permForPath($folder, 'read') ){
			$dirs[] = $folder;
		}	
	}
	
	// Remove any nested virt. directories 
	$conDirs = condensePathsArray($dirs);

	// Each virt. directory
	foreach($conDirs as $folder){
		
		// For every file in virt. dir	
		$filestructure = directoryToArray($folder, true);
		foreach ($filestructure as $item) {
	
			$name = $item['name'];
			$type = $item['type'];
			$path = $item['path'];
			$image = false;
	
			// Check if any of the terms fall into the file name
			$present = false;
			foreach($terms as $term){
				$pos = strpos(strtolower($name), strtolower($term));
				if($pos !== false){
					$present = true;
				}
			}
	
			// Check if file is hidden (.*)
			$hidden = false;
			if( startsWith($name, '.') ||  startsWith($name, $thumbnailPrefix)){
				$hidden = true;
			}
	
			// Add files to JSON
			if($present && !$hidden){
				$id = $path . '/' . $name;
				$id = str_replace("/","_",$id);
				$id = str_replace(".","_",$id);
	
				$metadata = getMeta($path, $name, $id, true);
				$image = ($metadata['image'] == 1)?true:false;
				$date = $metadata['date'];
				$description = $metadata['description'];
				$flags = ($metadata['flags'] == '')?'normal':$metadata['flags'];

				jsonAdd("\"rank\":\"$results\",\"image\": \"$image\",\"type\": \"$type\", \"path\": \"$path\",\"description\": \"$description\",\"name\": \"$name\",\"date\":\"$date\", \"id\": \"$id\",\"flags\": \"$flags\" ");
				$results ++;
			}
		}
	}

	if($results > 0){
		echo jsonReturn('search');
	}
}




// Login & Permissions Methods
// ***************************************************************************

function sortByLength($a,$b){
	// Longest first
	return strlen($b)-strlen($a);
}

// Returns array of virtual directories
function getVirtualDirs(){
	global $database;

	$query = "select * from $GLOBALS[tablePrefix]clients";
	$response = mysql_query($query, $database);
		
	$dirs = array();
	while( $client = mysql_fetch_assoc($response) ){
		$folder = $client['path'].'/'.$client['name'];
		$dirs[] = $folder;
	}
	
	return $dirs;
}

function getVirtualDirID($path){
	global $database;
	
	logAction('vid q in', $path);
	
	
	$candidates = array();
	$virDirs = getVirtualDirs();
	
	foreach($virDirs as $virPath){
		if( startsWith($path, $virPath) ){
			$candidates[] = $virPath;
			
		}
	}

	// Longest virtual dir 
	usort($candidates,'sortByLength');

	$virtualFullPath = $candidates[0];
	$virtualFullPath = explode('/', $virtualFullPath); 	// /var/www/pics
	$virtualName = array_pop($virtualFullPath); 		// pics
	$virtualPath = implode('/', $virtualFullPath); 		// /var/www

	$query = "select * from $GLOBALS[tablePrefix]clients where path=\"$virtualPath\" and name=\"$virtualName\" ";

	logAction('vid query', $query);

	$response = mysql_query($query, $database);
		
	$id = 0;
	
	while( $client = mysql_fetch_assoc($response) ){
		$id = $client['id'];
	}
	
	return $id;
}

function permForPath($path, $action){
	global $database;
	
	$virtualDirID = getVirtualDirID($path);
	
	logAction('perm_vID',$virtualDirID);
	
	$userID = $_SESSION[userid];
	
	logAction('perm_uID',$userID);
	
	
	$query = "select * from $GLOBALS[tablePrefix]permissions where userid=\"$userID\" and clientid=\"$virtualDirID\" ";
	$response = mysql_query($query, $database);
		
	$scheme = '';
	
	if (!$response){

	}else{
		while( $client = mysql_fetch_assoc($response) ){
			$scheme = $client['scheme'];
		}		
	}
	
	logAction('perm_scheme',$scheme);
	
	
	if($action == 'write'){
		if($scheme == 'admin' || $scheme == 'write'){
			return true;
		}
	}else if($action == 'read'){
		if($scheme == 'admin' || $scheme == 'write' || $scheme == 'read'){
			return true;
		}
	}
		
	return false;
}












// Legacy Method
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

// Legacy Method
function newPassword($current,$new){
	$query = "select * from $GLOBALS[tablePrefix]users where id=$_SESSION[userid] and password=md5(\"G8,rMzw6BrBApLU9$current\")";
	$result = mysql_query($query);
	// TODO: Query
	
	if(mysql_num_rows($result) == 1){
		logAction('newPassword',$_SESSION['user']);
		$pass = mysql_escape_string($_GET['pass']);
		$query = "update $GLOBALS[tablePrefix]users set `password`=md5(\"G8,rMzw6BrBApLU9$new\") where id=$_SESSION[userid]";
		$result = mysql_query($query)||die(mysql_error());
		// TODO: Query
		
	}else{
		error("bad current password");
	}
}

// Legacy Method
function userLogoff(){
	 session_destroy();
	 header('Location:index.php');
	exit;
}

// Legacy Method
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
	// TODO: Query
	
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
		// TODO: Query
		
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

// Legacy Method
function userPermissions(){
	global $database;
	if(isset($_SESSION['userid'])){
		$perQuery = "select $GLOBALS[tablePrefix]permissions.*,$GLOBALS[tablePrefix]clients.name as `cname`,$GLOBALS[tablePrefix]clients.path as `cpath`,$GLOBALS[tablePrefix]clients.id as `cid` from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\"";
		$permissions = mysql_query($perQuery,$database) or die(mysql_error());
		// TODO: Query
		
		$_SESSION["admin.cid"]='';
		$_SESSION["path"]='';
		if(mysql_num_rows($permissions) > 0)
			while($userPermissions = mysql_fetch_assoc($permissions)) {
				#print_r($userPermissions);
				$thispath = $userPermissions['cpath'].'/'.$userPermissions['cname'];
				$_SESSION['path'][]=$thispath;
				//logAction('userP 1348', $thispath);
				$thispath = $userPermissions['cname'];
				$admin   = $userPermissions['admin'];
				$_SESSION["auth.$thispath.view"]=$userPermissions['view'];
				$_SESSION["auth.$thispath.rename"]=$userPermissions['rename'];//
				$_SESSION["auth.$thispath.download"]=$userPermissions['download'];
				$_SESSION["auth.$thispath.metaEdit"]=$userPermissions['metaEdit'];//
				$_SESSION["auth.$thispath.delete"]=$userPermissions['delete'];
				$_SESSION["auth.$thispath.move"]=$userPermissions['move'];
				$_SESSION["auth.$thispath.folderRename"]=$userPermissions['folderRename'];//
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

// Legacy Method
function getUserAuth($type,$path){
	// logAction('getUserAuth','');	
	// logAction($type,$path);
	
	if(isset($_SESSION['userid'])){
		
	
		$paths = preg_split("/\//", $path); // isolate virtual directory name
		
		return (isset($_SESSION['auth.'.$paths[1].'.'.$type]))?$_SESSION['auth.'.$paths[1].'.'.$type]:false;
	}
}




// ***************************************************************************
// Below is legacy code that isn't* used anymore.
// Delete after testing
// ***************************************************************************

// function getFileInfo($fileid){
// 	global $database,$filepath,$fileinfo,$imageTypes;
// 	
// 	$fileid=mysql_escape_string($fileid);
// 	// $query = "select * from $GLOBALS[tablePrefix]filesystem where id=$fileid";
// 	// 	$result = mysql_query($query,$database);
// 	// TODO: Query
// 	
// 	// if(mysql_num_rows($result) == 0){
// 	// 		error('bad fileid');
// 	// 	}
// 	
// 	$file = mysql_fetch_assoc($result);
// 	
// 	$fileinfo['filename'] 		= $file['filename'];
// 	$fileinfo['date'] 		= $file['date'];
// 	$fileinfo['description'] 	= $file['description'];
// 	$fileinfo['downloads']		= $file['downloads'];
// 	$fileinfo['flags']		= $file['flags'];
// 	$fileinfo['type']		= $file['type'];
// 	$fileinfo['uploader']		= $file['uploader'];
// 	$fileinfo['path']		= $file['path'];
// 	$fileinfo['virtualpath']	= $file['rpath'];
// 	$fileinfo['size']		= filesize_format($file['size']);
// 	
// 	if(preg_match("$imageTypes",$fileinfo['type'])){
// 	      $fileinfo['image'] = 1;
// 	}else{
// 	      $fileinfo['image'] = 0;
// 	}
// 
// 	$filePath = $fileinfo['path'];
// 
// 	$filepath = $file['path'] . '/' . $file['filename'];
// 	$userpath = getUserPath($fileinfo['path']); // replaces / with \/ from preg_match
// 
// 	if(preg_match("/$userpath/i",$filepath)){
// 		return true;
// 	}else{
// 		return false;
// 	}
// }

// function getUserPaths(){
// 	global $database;
// 	$paths='';
// 	$query = "select * from $GLOBALS[tablePrefix]permissions inner join $GLOBALS[tablePrefix]clients on $GLOBALS[tablePrefix]permissions.clientid=$GLOBALS[tablePrefix]clients.id where userid=\"$_SESSION[userid]\"";
// 	$result = mysql_query($query,$database);
// 	// TODO: Query
// 
// 	while($clients = mysql_fetch_assoc($result)) {
// 		$paths[] = $clients['path'].'/'.$clients['name'];
// 	}
// 	
// 	return $paths;
// }

// function databaseSync($folderpath,$realitivePath=''){
//   global $database;
//   // get files from $folderpath and put them in array
//   if (is_dir($folderpath)) {
//     if ($dh = opendir($folderpath)) {
//        while (($file = readdir($dh)) !== false) {
//          #echo "$file";
//          if($file != '.' && $file != '..' && filetype($folderpath . '/' . $file) == 'file' && substr($file,0,1) != '.'){
//            $fileid = fileid($folderpath,$file);
// 		   $files[$file] = array($fileid,'exist');
// 		   #echo "1 $file<br>";
// 		 }
//        }
//        closedir($dh);
//     }
//   }
// 
// 
// 
//   // get files from database
//   $query = "select * from $GLOBALS[tablePrefix]filesystem where path=\"".mysql_escape_string($folderpath)."\" and status=\"found\"";
//   $result = mysql_query($query,$database);
// 	// TODO: Query
// 
//   while($dirinfo = mysql_fetch_assoc($result)) {
//     $filename = $dirinfo['filename'];
// 	$fileid =   $dirinfo['id'];
// 
// 	if(isset($files[$filename]) && $files[$filename][0] == $dirinfo['id']){
// 		$files[$filename][1]='done';
// 	}else{
// 		databaseLost($fileid);
// 	}
//   }
//   if(isset($files)){
//     $ak = array_keys($files);
// 	for($i=0;$i<sizeof($ak);$i++){
// 	  $filename = $ak[$i];
// 	  if($files[$filename][1]!='done'){
// 	  	#echo "$filename to search<br>";
// 		if(databaseSearch($folderpath , $filename)){
// 		  databaseUpdate($folderpath,$filename,$realitivePath);
// 		}else{
// 		  databaseAdd($folderpath,$filename,$realitivePath);
// 		}
// 	  }
// 	}
//   }
// }

// function databaseLost($fileid){
//   global $database;
//   $query = "update $GLOBALS[tablePrefix]filesystem set status=\"lost\" where id=$fileid";
//   #echo $query;
//   $result = mysql_query($query,$database) or die(mysql_error());
// 	// TODO: Query
// 
// }

// function databaseSearch($folderpath,$filename){
//   global $database;
// 
//   $fileid = fileid($folderpath,$filename);
//   $query = "select * from $GLOBALS[tablePrefix]filesystem where id=$fileid";
//   $result = mysql_query($query,$database) or die(mysql_error());
// 	// TODO: Query
// 
//   if($fileinfo = mysql_fetch_assoc($result)) {
//     if(file_exists($fileinfo['path'].'/'.$fileinfo['filename'])){
// 
// 	  if($fileinfo['path'] == $folderpath && $fileinfo['filename'] == $filename){
// 	  	return true;        // file was restored to origional location
// 	  }else{
// 	    return false;       // exact file still exists somewhere else
// 	  }
// 	}else{
// 	  // file must have been moved
// 	  return true;
// 
// 	}
//   }else{
//     // file is new
//   	return false;
//   }
// }

// function databaseUpdate($folderpath,$filename,$realitivePath){
// 	global $database,$finfo;
// 	$fileid = fileid($folderpath,$filename);
// 	$query = "update $GLOBALS[tablePrefix]filesystem set filename=\"$filename\",path=\"$folderpath\",rpath=\"$realitivePath\",status=\"found\" where id=$fileid";
// 	$result = mysql_query($query,$database);
// 	// TODO: Query
// 	
// }

// function databaseAdd($folderpath,$filename,$realitivePath){
// 	global $database,$rootpath;
// 
// 	if(function_exists('finfo')){
// 		$finfo = new finfo( FILEINFO_MIME,"$rootpath/inc/magic" );
// 		$type = $finfo->file( "$folderpath/$filename" );
// 	}else if(function_exists('mime_content_type') && mime_content_type("relay.php") != ""){
// 		$type = mime_content_type("$folderpath/$filename");
// 	}else{
// 		if(!$GLOBALS['mime']){
// 			include_once("inc/mimetypehandler.class.php");
// 			$GLOBALS['mime'] = new MimetypeHandler();
// 		}
// 		$type =  $GLOBALS['mime']->getMimetype("$filename");
// 	}
// 
// 	$size = get_size($folderpath.'/'.$filename);
// 	
// 	//$fileid = fileid($folderpath,$filename);
// 	
// 	// while(!checkId($fileid)){
// 	// 	$fileid++;
// 	// }
// 
// //	$query = "insert into $GLOBALS[tablePrefix]filesystem set id=\"$fileid\",filename=\"$filename\",path=\"$folderpath\",rpath=\"$realitivePath\",type=\"$type\",size=\"$size\"";
// 	
// 	$query = "insert into $GLOBALS[tablePrefix]filesystem set filename=\"$filename\",path=\"$folderpath\",rpath=\"$realitivePath\",type=\"$type\",size=\"$size\"";
// 	$result = mysql_query($query,$database) or die(mysql_error());
// 	// TODO: Query
// 	
// 
// 	chmod($folderpath . '/' . $filename,0755);
// 	touch($folderpath . '/' . $filename,$fileid);
// }

// function checkId($id){
// 	$query = "select id from $GLOBALS[tablePrefix]filesystem where id=$id";
// 	$result = mysql_query($query);
// 	// TODO: Query
// 	
// 	if(mysql_num_rows($result) == 0){
// 		return true; 
// 	}else{
// 		return false;
// 	}
// }

// function fileid($folderpath,$filename){
// 	$fileid = stat($folderpath . '/' . $filename);
// 	return $fileid[9]; //$fileid[9] + 
// }



?>
