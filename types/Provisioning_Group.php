<?php
class Provisioning_Group {
	private $groupId;
	private $groupName;
	private $emailPermission;
	private $permissionPreset;
	private $description;
	
	private $changes;
	
	public function __construct($groupId, $groupName, $description = "", $emailPermission = "Domain", $permissionPreset = "TeamDomain") {
		self::verifyEmailPermission($emailPermission);
		
		$this -> groupId = $groupId;
		$this -> groupName = $groupName;
		$this -> emailPermission = $emailPermission;
		$this -> permissionPreset = $permissionPreset;
		$this -> description = $description;
		
		$this -> changes = array();
	}
	
	public function createXML() {
		return "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
				"<apps:property name=\"groupId\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> groupId) . "\" />\n" .
				"<apps:property name=\"groupName\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> groupName) . "\" />\n" .
				"<apps:property name=\"emailPermission\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> emailPermission) . "\" />\n" .
				"<apps:property name=\"description\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> description) . "\" />\n" .
				"</atom:entry>\n";
	}
	
	public function modifyXML() {
		$str = "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n";
		foreach($this -> changes as $name => $value) {
			$str .= "<apps:property name=\"" . ProvisioningApi::escapeXML_Attr($name) . "\" value=\"".ProvisioningApi::escapeXML_Attr($value)."\"/>\n";
		}
		return $str . "</atom:entry>\n";
	}
	
	public function getgroupId() {
		return $this -> groupId;
	}
	
	public function getgroupName() {
		return $this -> groupName;
	}
	
	public function setgroupName($groupName) {
		$this -> groupName = $groupName;
		$this -> changes['groupName'] = $this -> groupName;
	}
	
	public function getemailPermission() {
		return $this -> emailPermission;
	}
	
	public function setemailPermission($emailPermission) {
		self::verifyEmailPermission($emailPermission);
		$this -> emailPermission = $emailPermission;
		$this -> changes['emailPermission'] = $this -> emailPermission;
	}
	
	public function getpermissionPreset() {
		return $this -> permissionPreset;
	}
	
	public function setpermissionPreset($permissionPreset) {
		$this -> permissionPreset = $permissionPreset;
		$this -> changes['permissionPreset'] = $this -> permissionPreset;
	}
	
	public function getdescription() {
		return $this -> description;
	}
	
	public function setdescription($description) {
		$this -> description = $description;
		$this -> changes['description'] = $this -> description;
	}
	
	/**
	 * Verify that an email permission is valid
	 * 
	 * @param string $emailPermission
	 * @throws Exception if the permission is invalid
	 */
	private static function verifyEmailPermission($emailPermission) {
		if($emailPermission == null) {
			return;
		}
		$permissions = array("Owner", "Member", "Domain", "Anyone");
		if(!in_array($emailPermission, $permissions, true)) {
			throw new Exception("Invalid emailPermission $emailPermission. Must be one of " . implode(' ', $permissions));
		}
	}
}