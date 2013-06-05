<?php
class Provisioning_OrganizationUnit {
	private $name;
	private $description;
	private $orgUnitPath;
	private $parentOrgUnitPath;
	private $blockInheritance;

	/* Track updated fields */
	private $changes;

	public function __construct($name, $description, $orgUnitPath, $parentOrgUnitPath, $blockInheritance = false) {
		$this -> name = $name;
		$this -> description = $description;
		$this -> orgUnitPath = $orgUnitPath;
		$this -> parentOrgUnitPath = $parentOrgUnitPath;
		$this -> blockInheritance = $blockInheritance ? 'true' : 'false';

		$this -> changes = array();
	}

	/**
	 * Return XML to create this unit
	 * https://developers.google.com/google-apps/provisioning/#creating_an_organization_unit
	 */
	public function createXML($customerId = null) {
		if($customerId == null) {
			throw new Exception("Provisioning_OrganizationUnit::createXML() requires a customerId");
		}
		$id = ProvisioningApi::base . "customer/2.0/" . urlencode($customerId);
		return "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
				"<id>" . ProvisioningApi::escapeXML_ElementContent($id) . "</id>\n" .
				"<apps:property name=\"name\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> name) . "\" />\n" .
				"<apps:property name=\"description\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> description) . "\" />\n" .
				"<apps:property name=\"parentOrgUnitPath\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> parentOrgUnitPath) . "\" />\n" .
				"<apps:property name=\"blockInheritance\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> blockInheritance) . "\" />\n" .
				"</atom:entry>\n";
	}

	/**
	 * Return XML to update this unit
	 * https://developers.google.com/google-apps/provisioning/#updating_an_organization_unit
	 */
	public function modifyXML($customerId = null) {
		if($customerId == null) {
			throw new Exception("Provisioning_OrganizationUnit::modifyXML() requires a customerId");
		}
		$id = ProvisioningApi::base . "customer/2.0/" . urlencode($customerId);
		$str = "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
				"<id>" . ProvisioningApi::escapeXML_ElementContent($id) . "</id>\n";
		foreach($this -> changes as $name => $value) {
			$str .= "<apps:property name=\"" . ProvisioningApi::escapeXML_Attr($name) . "\" value=\"".ProvisioningApi::escapeXML_Attr($value)."\"/>\n";
		}
		return $str . "</atom:entry>\n";
	}
	
	public function getname() {
		return $this -> name;
	}
	
	public function setname($name) {
		$this -> name = $name;
		$this -> changes['name'] = $name;
	}
	
	public function getdescription() {
		return $this -> description;
	}
	
	public function setdescription($description) {
		$this -> description = $description;
		$this -> changes['description'] = $description;
	}
	
	public function getorgUnitPath() {
		return $this -> orgUnitPath;
	}
	
	public function setorgUnitPath($orgUnitPath) {
		$this -> orgUnitPath = $orgUnitPath;
		$this -> changes['orgUnitPath'] = $orgUnitPath;
	}
	
	public function getparentOrgUnitPath() {
		return $this -> parentOrgUnitPath;
	}
	
	public function setparentOrgUnitPath($parentOrgUnitPath) {
		$this -> parentOrgUnitPath = $parentOrgUnitPath;
		$this -> changes['parentOrgUnitPath'] = $parentOrgUnitPath;
	}
	
	public function getblockInheritance() {
		return $this -> blockInheritance == 'true';
	}
	
	public function setblockInheritance($blockInheritance) {
		$this -> blockInheritance = (boolean)$blockInheritance? 'true' : 'false';
		$this -> changes['blockInheritance'] = $this -> blockInheritance;
	}	
}