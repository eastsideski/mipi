<?php
$page = new Page("Event Description");
$event = Event::getEvent($_GET[2]);

$box = new Box('description',$event->name);
$box->setContent(new BCStatic(""));
$page->addBox($box,'double');

$guestlist = new Box('list',"Guestlist");
ob_start();
if ($event->hasGL){
    echo new Hyperlink("View/Edit Guestlist","/events/list/".$event->id);
    echo "<h3>Stats</h3>";
    $stats = new Query("SELECT
        (SELECT COUNT(*) FROM `guests` WHERE `event`=2) as num,
        (SELECT COUNT(*) FROM `guests` WHERE `event`=2 AND `sex`='MALE') as guys,
        (SELECT COUNT(*) FROM `guests` WHERE `event`=2 AND `sex`='FEMALE') as girls,
        ((SELECT COUNT(*) FROM `guests` WHERE `event`=2 AND `sex`='FEMALE')/(SELECT COUNT(*) FROM `guests` WHERE `event`=2)) as ratio,
        (SELECT AVG(a.num) FROM (SELECT COUNT(*) as num  FROM `guests` WHERE `event`=2 GROUP BY `owner`) as a) as guestsperbro");
    echo 'Guests: '.$stats->getField('num').'<br />';
    echo 'Guys: '.$stats->getField('guys').'<br />';
    echo 'Girls: '.$stats->getField('girls').'<br />';
    echo 'Ratio: '.$stats->getField('ratio').'<br />';
    echo 'Guests per brother: '.$stats->getField('guestsperbro').'<br />';
    
}else {
    echo "This event does not have a guest list";
}
$guestlist->setContent(new BCStatic(ob_get_clean()));
$page->addBox($guestlist,'right');

return $page;
?>
