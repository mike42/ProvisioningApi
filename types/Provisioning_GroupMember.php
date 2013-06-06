<?php
class Provisioning_GroupMember {
	private $memberType;
	private $memberId;
	private $directMember;

	public function __construct($memberType, $memberId, $directMember = true) {
		self::verifyMemberType($memberType);
		
		$this -> memberType = $memberType; 
		$this -> memberId = $memberId;
		$this -> directMember = $directMember ? 'true' : 'false';
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