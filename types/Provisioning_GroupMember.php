<?php
class Provisioning_GroupMember {
	private $memberType;
	private $memberId;
	private $directMember;

	public function __construct($memberId, $memberType = "User", $directMember = true) {
		self::verifyMemberType($memberType);
		
		$this -> memberType = $memberType; 
		$this -> memberId = $memberId;
		$this -> directMember = $directMember ? 'true' : 'false';
	}
	
	public function createXML($groupId = null) {
		if($groupId == null) {
			throw new Exception("Provisioning_GroupMember::createXML() requires a groupId");
		}
		return "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
				"<apps:property name=\"groupId\" value=\"" . ProvisioningApi::escapeXML_Attr($groupId) . "\" />\n" .
				"<apps:property name=\"memberId\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> memberId) . "\" />\n" .
				"</atom:entry>\n";
	}
	
	public function getmemberType() {
		return $this -> memberType;
	}
	
	public function getmemberId() {
		return $this -> memberId;
	}
	
	public function getdirectMember() {
		return $this -> directMember == 'true';
	}
	
	/**
	 * Check for problems with memberType.
	 * 
	 * @param string $memberType
	 * @throws Exception If the member type is invalid
	 */
	private static function verifyMemberType($memberType) {
		if(!($memberType == "User" || $memberType == "Group")) {
			throw new Exception("Unknown member type: $memberType. Must be 'User' or 'Group'.");
		}
	}
}