<?php
class Provisioning_OrganizationUser {
	private $orgUserEmail;
	private $orgUnitPath;
	
	/* Track updated orgUnitPath */
	private $changes;
	
	public function __construct($orgUserEmail, $orgUnitPath) {
		$this -> orgUserEmail = $orgUserEmail;
		$this -> orgUnitPath = $orgUnitPath;
		
		$this -> changes = array();
	}
	
	public function getorgUserEmail() {
		return $this -> orgUserEmail;
	}
	
	public function getorgUnitPath() {
		return $this -> orgUnitPath;
	}
	
	public function setorgUnitPath($orgUnitPath) {
		$this -> orgUnitPath = $orgUnitPath;
		$this -> changes['orgUnitPath'] = $orgUnitPath;
	}
	
	/**
	 * Generate XML to move this organizationUser to another unit.
	 * https://developers.google.com/google-apps/provisioning/#updating_an_organization_user
	 */
	public function modifyXML() {
		$str = "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n";
		foreach($this -> changes as $name => $value) {
			$str .= "<apps:property name=\"" . ProvisioningApi::escapeXML_Attr($name) . "\" value=\"".ProvisioningApi::escapeXML_Attr($value)."\"/>\n";
		}
		return $str . "</atom:entry>\n";
	}
}