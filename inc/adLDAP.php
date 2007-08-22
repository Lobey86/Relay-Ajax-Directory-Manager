<?php
/*
 
	Modified 6.19.2006 by David Barshow for RelayADM
	
	http://www.ecosmear.com/relay
	
	
	LDAP FUNCTIONS FOR MANIPULATING ACTIVE DIRECTORY
	Version 1.4

	Maintained by Scott Barnett
	email: scott@wiggumworld.com
	http://adldap.sourceforge.net/

	Works with both PHP 4 and PHP 5

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.


*/


class adLDAP {

	var $_account_suffix="@";
	var $_base_dn = "";
	
	var $_domain_controllers;
	var $_real_primarygroup=false;
	
	var $_user_dn;
	var $_user_pass;
	var $_conn;
	var $_bind;

	// default constructor
	function adLDAP(){
		global $activeDirectoryDC,$activeDirectoryServer;
			
		$first = true;
		foreach($activeDirectoryDC as $dc){
			if(!$first){
				$this->_account_suffix .= ".";
				$this->_base_dn .= ",";
			}
			
			$this->_account_suffix .= $dc;
			$this->_base_dn .= "DC=$dc";
			
			$first = false;
		}
		
		$this->_domain_controllers = array ("$activeDirectoryServer");
		
		//connect to the LDAP server as the username/password
		$this->_conn = ldap_connect($this->random_controller()) or die("LDAP connect failed check host");
		ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 0); //disable plain text passwords
		return true;
	}

	function existingUser($user){
		global $activeDirectoryUser,$activeDirectoryPass;
		$this->_bind = @ldap_bind($this->_conn,"$activeDirectoryUser".$this->_account_suffix,"$activeDirectoryPass");
			
		$sr = ldap_search($this->_conn,$this->_base_dn,"samaccountname=$user",array("samaccountname","mail","displayname")) or die(ldap_error($this->_conn));
		$entries = ldap_get_entries($this->_conn, $sr);
		
		if(count($entries) > 0){

			return array($entries[0]["displayname"][0],$entries[0]["mail"][0]);
		}else{
			return false;
		}

	}
	
	// default destructor
	function __destruct(){ ldap_close ($this->_conn); }

	function random_controller(){
		//select a random domain controller
		mt_srand(doubleval(microtime()) * 100000000);
		return ($this->_domain_controllers[array_rand($this->_domain_controllers)]);
	}

	// authenticate($username,$password)
	//	Authenticate to the directory with a specific username and password
	//	Extremely useful for validating login credentials
	function authenticate($username,$password){
		//validate a users login credentials
		$returnval=false;
		
		if ($username!=NULL && $password!=NULL){ //prevent null bind
			$this->_user_dn=$username.$this->_account_suffix;
			$this->_user_pass=$password;
			
			$this->_bind = @ldap_bind($this->_conn,$this->_user_dn,$this->_user_pass);
			
			if ($this->_bind){ $returnval=true; }
		}
		return ($returnval);
	}
	
	
} // End class

?>