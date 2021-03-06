<?php
/**
 * Class containing
 *
 * @package default
 * @author  
 */
class GuestList {
	
	public $event;
	public $guests = array();
	public $guestsByOwner = array(); 
	private $pointer =0;
    public $guestsPerPerson;
    public $listUnlocks;
    private $maxid = 0;
	
	public function __construct($event)
	{
		$this->event = Event::getEvent($event);
	    $this->listUnlocks = $this->event->unlock;
        $this->listCloses = $this->event->close;
        $this->guestsPerPerson = $this->event->guestsPerPerson;
        
		$query = new Query("SELECT `ID`, `event`, `owner`, `first`, `last`, (sex+5) AS sex FROM `guests` WHERE `event`=".$this->event->id." ORDER BY `last`;");
		
		while ($row = $query->nextRow()) {
			$guest = new Person($row['first'],$row['last'],$row['ID'],$row['sex']);
			$this->guests[] = array("guest"=>$guest, "owner"=>$row['owner']);
			$this->guestsByOwner[$row['owner']][/*$row['order']*/] = $guest;
            if ($row['ID']>$this->maxid) {
                $this->maxid = $row['ID'];
            }
		}
		
	}
    public function updateList(Member $user,$list)
    {
        $insertstrings = array();
        foreach ($list as $person) {
            /* @var $person Person */
           $sex = $person->sex == Person::FEMALE ? 'FEMALE' : 'MALE';
           $insertstrings[] = sprintf("('%d','%d','%s','%s','%s')",$this->event->id,$user->id,$sex,mysql_escape_string($person->first),mysql_escape_string($person->last));
        }
        $insertstring = implode(',', $insertstrings);
        
        require_once("control/mysql.php");
        
        mysql_query("DELETE FROM `guests` WHERE `event`=".$this->event->id." AND `owner`=$user->id;");
        mysql_query("INSERT IGNORE INTO `guests` (`event` ,`owner` ,`sex` ,`first` ,`last`) VALUES $insertstring;");
    }
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author  
	 */
	function updateUserList(Member $user, $males, $females) {
		foreach ($males as $order => $person) {
			$this->updateUser($user, $order, $person, 'MALE');
		}
		foreach ($females as $order => $person) {
			$this->updateUser($user, $order, $person, 'FEMALE');
		}
	}
	private function updateUser(Member $user,$order,Person $person,$sex)
	{
		$count = new Query("SELECT count(*) as c FROM `guests` WHERE owner=$user->id AND `order`=$order AND `sex`='$sex'");
		if ($count->getField('c')>=1) {
			mysql_query("UPDATE `guests` SET `first` = '$person->first', `last`='$person->last' WHERE `owner`=$user->id AND `order`=$order AND `sex`='$sex'");
		} elseif($person->first !='' || $person->last!='') {
			mysql_query("INSERT INTO `guests` (`event`,`owner`,`order`,`first`,`last`,`sex`) VALUES ($this->event,$user->id,$order,'$person->first','$person->last','$sex')");
		}
		
	}
    /**
     * undocumented function
     *
     * @return int
     * @author  
     */
    function getGuestsAllowed() {
        if ($this->listUnlocks && $this->listUnlocks < new DateTime()){
            $numq = new Query("SELECT (
                (SELECT COUNT(*)FROM  `users` WHERE `type`= 'BROTHER' OR `type`= 'AM')
                *(SELECT `guestsperperson` FROM `eventsX` WHERE `ID`=".$this->event->id.")
                - (SELECT COUNT(*) FROM `guests` WHERE event=".$this->event->id.")) as num;");
            return $numq->getField('num');
        }
        return $this->guestsPerPerson;
    }
	/**
	 * Get the ratio for an event
	 *
	 * @return void
	 * @author  
	 */
	public function getRatio($order=1,$cached=true,$numresults=5) {
	    $filename = 'data/'.sprintf('ratio_e%d_o%d_n%d_m%d.txt',$this->event->id,$order,$numresults,$this->maxid);
	    if ($cached and file_exists($filename)) {
	        header('X-cache: used');
			return unserialize(file_get_contents($filename));
		} else {
            $order = ($order==self::BEST) ? "DESC" : "ASC";
            $query = new Query("SELECT event,owner,
                IFNULL((SELECT count(*) FROM guests WHERE owner=g1.owner AND sex='MALE' AND event=".$this->event->id." GROUP BY owner),0) as male,
                IFNULL((SELECT count(*) FROM guests WHERE owner=g1.owner AND sex='FEMALE' AND event=".$this->event->id." GROUP BY owner),0) as female,
                IFNULL((SELECT count(*) FROM guests WHERE owner=g1.owner AND sex='FEMALE' AND event=".$this->event->id." GROUP BY owner),0)/count(*) as ratio
                FROM guests as g1 WHERE event=".$this->event->id." GROUP BY owner ORDER BY ratio $order, female $order LIMIT 0,$numresults");
            file_put_contents($filename, serialize($query->rows));
            return $query->rows;
		}
		

	}
	
	const BEST = 1;
	const WORST = 2;
	/**
	 * Return member at current pointer
	 *
	 * @return Member
	 * @author  
	 */
	function getCurrentOwner() {
		return Member::getMember($this->guests[$this->pointer]['owner']);
	}
	/**
	 * Return guest at current pointer
	 *
	 * @return Person
	 * @author  
	 */
	function getCurrentGuest() {
		return array_key_exists($this->pointer, $this->guests) ? $this->guests[$this->pointer]['guest'] : NULL;
	}
	/**
	 * Advance Guest Pointer
	 *
	 * @return void
	 * @author  
	 */
	function advance() {
		$this->pointer++;
	}
	/**
	 * undocumented function
	 *
	 * @return boolean
	 * @author  
	 */
	function listOpen() {
	    return !$this->listCloses or ($this->listCloses > new DateTime());
	}
	/**
	 * Returns a list of frequent guests of the user
	 *
	 * @return void
	 * @author  
	 */
	static function getFrequentGuests(Member $user) {
	    $q = new Query(sprintf('SELECT * , COUNT(*) AS count
	        FROM  `guests` 
            WHERE  `owner` =%d
            GROUP BY `owner` , `first` , `last` 
            HAVING  `count` >1
            ORDER BY  `count` DESC 
            LIMIT 0 , 30',$user->id));
        $array = array();
        while ($row = $q->nextRow()){
            $array[] = new Person($row['first'],$row['last'],0,$row['sex']);
        }
        return $array;
	}
} // END
?>