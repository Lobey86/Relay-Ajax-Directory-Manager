<?php

/**
 * Mimetype handler
 *
 * @author Tom Reitsma <treitsma@rse.nl>
 * @version 0.5
 *
 * @modified 06.28.06 for Relay
 * @author2 David Barshow
 * 
 */
Class MimetypeHandler
{

	var $mimeTypes = array();
	var $mime_ini_location = "mime_types.ini";

	function __construct()
	{
		global $rootpath;
		$this->mimeTypes = parse_ini_file($rootpath ."inc/" . $this->mime_ini_location, false);
	}

	function getMimetype($filename=false)
	{
		if(count($this->mimeTypes) == 0)
		{
			$this->__construct();
		}
		
		if($filename == false || !is_string($filename))
		{
			die("No input specified.");
		}

		$exploded = explode(".", $filename);
		$ext = $exploded[count($exploded)-1];
		$ext = strtolower($ext);
		
		if(!$this->mimetypeExists($ext))
		{
		    return 'text/plain';
		}
		else
		{
		    return $this->mimeTypes[$ext];
		}
	}
	
	function mimetypeExists($ext)
	{
		return isset($this->mimeTypes[$ext])?true:false;
	}

}

?>