<?php
$user = getUser();
switch(@$_GET[1])
{
	case 'edit':
		$page = Page::getPage('profile/edit');
		break;
	case 'save':
		$user->email	= $_POST['email'];
		$user->cell		= $_POST['cell'];
		$user->major	= $_POST['major'];
		$user->schoolloc= $_POST['schoolloc'];
		$user->homeaddr	= $_POST['homeaddr'];
		$user->yog		= $_POST['yog'];
		$user->dob		= new DateTime($_POST['dob']);
        $user->fbid     = $_POST['fbid'];
        $user->twitid   = $_POST['twitid'];
		
		try
		{
			if ($user->save())
			{
				header("location: /profile/msg:saved");
			}else{
				header("location: /profile/msg:nosaved");
			}
			$user->start();
		} catch (Exception $e) {
			echo "SQL Error";
		}
		
		exit;
	case 'picsave':
		if ($_FILES["profpic"])
		if ($user->moveNewPhoto($_FILES["profpic"]["tmp_name"]))
		{
			header("location: /profile/msg:saved");
		}else{
			header("location: /profile/msg:nosaved");
		}
		exit;
    case 'changepw':
        $page = Page::getPage('profile/changepassword');
        break;
    case 'setpw':
        if ($_POST['oldpw'] === $_POST['oldpw2']) {
            $q = new Query(sprintf("SELECT * FROM `users` WHERE `ID`=%d AND `password` LIKE '%s' LIMIT 0,1;",getUser()->id,md5($_POST['oldpw'])));
            if ($q->numRows == 1) {
                mysql_query(sprintf("UPDATE `users` SET  `password` = '%s' WHERE `ID` =%d;",md5($_POST['newpw']),getUser()->id));
                header("Location: /profile/msg:pwup");
                exit;
            }
        }
        header("Location: /profile/changepw/msg:pwer");
        exit;
	default:
		$page = new Page("Profile");
		
		$left = new Box('left',"Profile");
		$leftBox = new BCStatic();
		ob_start();
?>
<h3><?php echo $user->getName() ?></h3>
<img src="<?php echo $user->getPhotoPath() ?>" style="width: 200px" />
<?php
		$leftBox->content = ob_get_clean();
		$left->setContent($leftBox);
		$page->addBox($left,'left');
		
		$rightbox = new Box('details','');
		$details = new BCStatic();
		ob_start();
		?>
email: <?php echo $user->email ?><br />
<?php echo isset($user->cell) ? "cell phone: $user->cell<br />" : "";?>
<?php echo isset($user->major) ? "major: $user->major<br />" : "";?>
<?php echo isset($user->schoolloc) ? "school address: $user->schoolloc<br />" : "";?>
home address:<br />
<?php echo isset($user->homeaddr) ? nl2br($user->homeaddr,true) : "unknown";?><br />
year of graduation: <?php echo $user->yog ?><br />
dob: <?php echo $user->dob->format('F jS, Y') ?><br />
		<?php
		$details->content = ob_get_clean();
		$rightbox->setContent($details);
		$page->addBox($rightbox,'double');
		
		switch (@$_GET['msg']) {
			case 'saved':
				$page->setMessage("Your profile has been saved");
				break;
			case 'nosaved':
				$page->setMessage("No information updated");
				break;
            case 'pwup':
                $page->setMessage("Password Updates");
		}
		break;
}

$page->section = "profile";
return $page;
?>
