<?php
/**
 * Generic Member of LCA
 *
 * @package default
 * @property DateTime $dob
 * @author  
 */
class Member extends Person {
	/**
    * @var DateTime 
    */
   	public $dob;
	
	public $bigNum, $email,$yog;
	private $initalVars;
	protected $piNum;
	
	
	/**
	 * Return the path to the photo of the person
	 * 
	 * @return string
	 */
	function getPhotoPath()
	{
		return "/mipi/img/portrait/user$this->id.jpg";
	}
	function getPiNum($htmlchar=false)
	{
		return $htmlchar ? "" : NULL;
	}
	/**
	 * Return the Member object of the person's big
	 *
	 * @return Member
	 * @author  
	 */
	function getBig()
	{
		return self::getMember($this->bigNum);
	}
	/**
	 * Return member with the given database ID
	 * 
	 * @param $id int DB id
	 * @return Member
	 */
	static function getMember($id)
	{
		$array = self::getMembersFromQuery("SELECT * FROM users WHERE `ID`= $id");
		if (count($array)==1) {
			return $array[0];
		} else {
			throw new Exception("User not found", 1);
		}
	}
	static function getMemberLogin($username,$password)
	{
		$array = self::getMembersFromQuery("SELECT * FROM users WHERE
`username`='$username' AND
`password`='$password'");
		print_r($array);
		if (count($array)==1) {
			return $array[0];
		} else {
			throw new Exception("Username and Password Incorrect", 1);
		}
		
	}
	/**
	 * Return array of members generated by SQL Query
	 * 
	 * @param $query string
	 * @return array(Member)
	 */
	static function getMembersFromQuery($query)
	{
		$query = new Query($query);
		if ($query->numRows >= 1) {
			$array = array();
			while($row = $query->nextRow())
			{
				$member;
				switch ($row['type']) {
					case 'AM':
						$member = new AM();
						break;
					case 'BROTHER':
						$member = new Brother();
						break;
					case 'ALUM':
						$member = new Alumni();
						break;
					default:
						throw new Exception();
				}
				
				$member->id		= $row['ID'];
				$member->first	= $row['nameFirst'];
				$member->last	= $row['nameLast'];
				$member->email	= $row['email'];
				$member->yog	= $row['yog'];
				$member->dob	= new DateTime($row['dob']);
				$member->updateFromDataField($row['data']);
				
				$member->start();
				
				$array[] = $member;
				unset($member);
			}
			return $array;
		} else {
			throw new Exception();
		}
	}
	const QueryAll = "SELECT *,pi IS NULL AS isnull FROM users ORDER BY isnull ASC, pi ASC, nameLast ASC";
	const QueryAllBrothers = "SELECT *,pi IS NULL AS isnull FROM users WHERE `type`='BROTHER' ORDER BY isnull ASC, pi ASC, nameLast ASC";
	const QueryAllAlum = "SELECT * FROM users WHERE `type`='ALUM' ORDER BY pi ASC";
	
	public function save()
	{
		return Query::update('users', "`ID`=".$this->id, $this->initalVars, $this->getDBArray());
	}
	public function start()
	{
		//$this->initalVars = get_object_vars($this);
		$this->initalVars = $this->getDBArray();
	}
	public function getSerializedDataField()
	{
		return serialize($this->hiddenData());
	}
	public function updateFromDataField($data)
	{
		$this->hiddenData(unserialize($data));
	}
	protected function getDBArray()
	{
		return array(
			"nameFirst"	=>$this->first,
			"nameLast"	=>$this->last,
			"email"		=>$this->email,
			"data"		=>$this->getSerializedDataField()
			);
	}
} // END
?>