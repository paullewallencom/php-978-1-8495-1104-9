<?php
/**
 * EMAIL TO PHPLIST v2.0.0b
 * This is a plugin for phplist
 * Created by SaWey (C) 2007
 *
 *
 * The only thing you have to do is
 * Put the files in your plugin directory
 * And change the file path and URL to the
 * attachments dir in get_email.class.php
 * All other config happens in the webgui
 */



$action = $_POST['action'];

if ($action == ""){
	main();
}elseif ($action == "Configure Mail To List"){
	configure();
}elseif ($action == "Process new mails"){
	process();
}elseif ($action == "Edit list"){
	editlist();
}elseif ($action == "Apply settings"){
	editlist();
}elseif ($action == "Delete settings"){
	editlist();
}elseif ($action == "Edit users"){
	editwhitelist();
}elseif ($action == "Add user"){
	editwhitelist();
}elseif ($action == "Import users" || $action == "Import"){
	import_users();
}elseif ($action == "Delete user"){
	editwhitelist();
}elseif ($action == "Proceed"){
	process();
}elseif ($action == "Settings overview"){
	overview();
}



//Functions

#########################
##      MAIN()         ##
#########################
function main(){
	//first of all, check if DB is up to date.
	$check = Sql_Check_For_Table($GLOBALS[table_prefix]."mail2list_allowsend");
	if ($check == ""){
		//Add new tables

		//create tableprefix_mail2list_list
		$sqllist = 'CREATE TABLE `'.$GLOBALS[table_prefix].'mail2list_list` (`listid` INT( 10 ) NOT NULL PRIMARY KEY, `toemail` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL, `template` INT( 10 ) NOT NULL , `footer` TEXT NOT NULL);';
		Sql_Query($sqllist);

		//create tableprefix_mail2list_allowsend
		$sqlallowsend = "CREATE TABLE `".$GLOBALS[table_prefix]."mail2list_allowsend` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,`email` VARCHAR( 255 ) NOT NULL ,`name` VARCHAR( 255 ) NOT NULL ,`sent` INT( 50 ) NOT NULL DEFAULT '0');";
		Sql_Query($sqlallowsend);

		//create tableprefix_mail2list_popaccounts
		$sqlpop = "CREATE TABLE `".$GLOBALS[table_prefix]."mail2list_popaccounts` (`listid` INT( 10 ) NOT NULL PRIMARY KEY, `host` VARCHAR( 255 ) NOT NULL ,`port` VARCHAR( 255 ) NOT NULL ,`login` VARCHAR( 50 ) NOT NULL ,		`pass` VARCHAR( 50 ) NOT NULL, `mail_status` VARCHAR( 10 ) NOT NULL DEFAULT '0/0', `queue` VARCHAR( 3 ) NOT NULL DEFAULT 'yes', `del_message` VARCHAR( 3 ) NOT NULL DEFAULT 'yes' );";
		Sql_Query($sqlpop);

		$page  = "<h6>Database had to be updated, please reload the page. <br />";
		$page .= "By the way, everything went fine.</h6>";
		$page .= "<input type='button' value='Click to reload' onClick='window.location.reload()'>";

	}else{
		$page = "<h1>Mail to List Main page </h1>";
		$page .= "<p>What do you want to do?</p>";
		$page .= "<br /><br /><form method='post'><input type=submit name='action' value='Configure Mail To List'><br /><br />";
		$page .= "<input type=submit name='action' value='Process new mails'><br />";
		$page .= "</form>";
		$page .= "<br /><br />______________________________________________<br /><br /><br />";
	}
	print($page);
}


#########################
##     CONFIGURE()     ##
#########################
function configure(){
// get available lists, add an email addres to it and edit.
	$page = "<h1>Mail to List Configuration page </h1>";
	$page .= "<h6>Choose a list to configure.</h6>";
	$page .= "<form method='post'>";
	$page .= "<select name='list'>";
	$req = Sql_Query(sprintf('select * from ' . $GLOBALS[table_prefix].'list'));
    while ($row = Sql_Fetch_Array($req)) {
	  $page .= " <option value=" . $row["id"] . ">" . $row["name"] . "</option>";
    }
	$page .= "</select><br /><br /><input type=submit name='action' value='Edit list'></form>";
	$page .= "<br /><br />______________________________________________<br /><br /><br />";
	$page .= "<h6>Click below to edit users on the whitelist</h6><br />";
	$page .= "<form method='post'><input type=submit name='action' value='Edit users'></form>";
	$page .= "<br /><br />______________________________________________<br /><br /><br />";
	$page .= "<form method='post'><input type=submit name='action' value='Settings overview'></form>";
	print($page);
}


#########################
##     PROCESS()       ##
#########################
function process(){
	include($GLOBALS[coderoot] . "plugins/mailtolist/get_email.class.php");
	
	$full_page = "";
	
	$page = "<h1>Mail to List Processing page </h1>";
	$page .= "<p>Mail to List is trying to process all the new messages</p>";
	$page2  = "<br /><br /><br />______________________________________________<br /><br /><br />";
	$page2 .="<br /><br />";
	$page2 .= "<a href='./?page=processqueue'>Click to process queue</a>";

	$full_page .= $page;
	//get available lists
	$accounts = Sql_Num_Rows($GLOBALS[table_prefix]."mail2list_popaccounts");
	$sql = Sql_Query("SELECT * FROM ". $GLOBALS[table_prefix]."mail2list_popaccounts");
	$i = 0;
	while ($row = Sql_Fetch_Array($sql)) {
	  $popaccounts[$i] = $row;
	  $i++;
    }
	$i -=1;
	for ($i ; $i>=0; $i--){
		$sql = Sql_Query("SELECT * FROM ".$GLOBALS[table_prefix]."mail2list_list, ".$GLOBALS[table_prefix]."list WHERE ".$GLOBALS[table_prefix]."mail2list_list.listid=".$GLOBALS[table_prefix]."list.id AND listid='".$popaccounts[$i]['listid']."'");
		$listresult = Sql_Fetch_Array($sql);
		$popaccounts[$i]['owner'] = $listresult['owner'];
		$popaccounts[$i]['template'] = $listresult['template'];
		$popaccounts[$i]['footer'] = $listresult['footer'];
		$popaccounts[$i]['toemail'] = $listresult['toemail'];

		//process pop3 boxes
		$phplist_template = $popaccounts[$i]['template'];
		$phplist_listowner = $popaccounts[$i]['owner'];
		$phplist_listid = $popaccounts[$i]['listid'];
		$phplist_toemail = $popaccounts[$i]['toemail'];
		$phplist_queue = $popaccounts[$i]['queue'];
		$phplist_del_message = $popaccounts[$i]['del_message'];

		
		$listname = Sql_Query(sprintf('SELECT '.$GLOBALS[table_prefix].'list.name FROM ' . $GLOBALS[table_prefix].'mail2list_list, '.$GLOBALS[table_prefix].'list WHERE '.$GLOBALS[table_prefix].'list.id='.$GLOBALS[table_prefix].'mail2list_list.listid AND '.$GLOBALS[table_prefix].'mail2list_list.listid='.$phplist_listid));
  		$listname = Sql_Fetch_Array($listname);
 		$listname = "<b>'" . $listname['name'] . "'</b>";
		$full_page .= "<br /><br />______________________________________________<br /><br />";
		$full_page .= "Processing list ".$listname . "<br />";
		$full_page .= "---------------";
		for($iline=0; $iline<=strlen($listname); $iline++){
			$full_page .= "-";
		}
		$full_page .= "<br />";

		$edb = new EMAIL_TO_DB();
		$test2 = $edb->connect($popaccounts[$i]['host'], $popaccounts[$i]['port'], $popaccounts[$i]['login'], $popaccounts[$i]['pass']);
		$full_page .= "<br />Status of pop3 connection: <font color='red'><b>" . $edb->status . "</b><br />" . $edb->imap_error."</font><br /><br />";
		$edb->do_action($phplist_template, $phplist_listowner, $phplist_listid, $phplist_toemail, $listname, $phplist_queue);
		foreach($edb->processed_message as $id => $msg){
			$full_page .= $msg;
		}
	}
	print($full_page);
}


#########################
##     EDITLIST()      ##
#########################
function editlist(){
echo $id;
	$id = $_POST['list'];

	####################
	//Apply POP settings
	if ($_POST['action'] == "Apply settings"){

		//first check for existing email adresses
		$req1 = Sql_Query("SELECT * FROM " . $GLOBALS[table_prefix]."mail2list_list");
		$count = 0;
		while ($row1 = Sql_Fetch_Array($req1)){
			if ($row1['listid']!=$id){
				if ($row1['toemail']==$_POST['toemail']){
					$count++;
				}
			}
		}

		if ($count>=1){
		//email is already in use by another list
		$page2 = "<h6 style='color:red'>E-mail address is already in use by another list.<br />Please enter another address.</h6>";

		}else{
		//write config to db
		
		//check for table mail2list_list existance
		$req = Sql_Query("SELECT * FROM " . $GLOBALS[table_prefix]."mail2list_list WHERE listid=".$id);
		$row = Sql_Fetch_Array($req);
		
		if ($row['listid'] == ""){
			//table doesn't exist
			Sql_Query("INSERT INTO " . $GLOBALS[table_prefix]."mail2list_list (`listid`, `toemail`, `template`) VALUES ('" . $id . "', '" . $_POST['toemail'] . "', '"  . $_POST['template'] . "')");
		}else{
			//table exists
			Sql_Query("UPDATE " . $GLOBALS[table_prefix]."mail2list_list SET toemail='" . $_POST['toemail'] . "', template='" . $_POST['template'] . "' WHERE listid="  . $id);
		}
		
		//check for table mail2list_popaccounts existance
		$req = Sql_Query("SELECT * FROM " . $GLOBALS[table_prefix]."mail2list_popaccounts WHERE listid=".$id);
		$row = Sql_Fetch_Array($req);
		
		if ($row['listid'] == ""){
			//table doesn't exist
			Sql_Query("INSERT INTO `" . $GLOBALS[table_prefix]."mail2list_popaccounts` (`host` , `port` , `login` , `pass`, `listid`, `queue`, `del_message` ) VALUES ('" . $_POST['pophost'] . "', '" . $_POST['popport'] . "', '" . $_POST['poplogin'] . "', '" . $_POST['poppass'] . "', '" . $id . "', '" . $_POST['queue'] . "', '" . $_POST['del_message'] . "');");
		}else{
			//table exists
			Sql_Query("UPDATE `" . $GLOBALS[table_prefix]."mail2list_popaccounts` SET `host` = '" . $_POST['pophost'] . "',`port` = '" . $_POST['popport'] . "',`login` = '" . $_POST['poplogin'] . "',`pass` = '" . $_POST['poppass'] . "', `queue`='" . $_POST['queue'] . "', `del_message`='" . $_POST['del_message'] . "' WHERE `" . $GLOBALS[table_prefix]."mail2list_popaccounts`.`listid` =".$id);


		}

		$page2 = "<h6 style='color:red'>Settings have been saved</h6>";
		}
	}elseif($_POST['action']=="Delete settings"){
		$sql = "DELETE FROM `" . $GLOBALS[table_prefix]."mail2list_popaccounts` WHERE listid=".$id;
		Sql_Query($sql);
		$sql2 = "DELETE FROM `" . $GLOBALS[table_prefix]."mail2list_list` WHERE listid="  . $id;
		Sql_Query($sql2);
		$page2 = "<h6 style='color:red'>Settings have been removed</h6>";
	}

	//retrieve existing email addres
	$sql = Sql_Query("SELECT * FROM ".$GLOBALS[table_prefix]."mail2list_list, ".$GLOBALS[table_prefix]."list WHERE ".$GLOBALS[table_prefix]."mail2list_list.listid=".$GLOBALS[table_prefix]."list.id AND listid='".$id."'");
	$row2 = Sql_Fetch_Array($sql);
	$popsql = Sql_Query("SELECT * FROM " . $GLOBALS[table_prefix]."mail2list_popaccounts WHERE listid=".$id);
	$poprow = Sql_Fetch_Array($popsql);

	$q = '"';
	$page  = "<h1>Mail to List Configuration page </h1>";
	$page .= "<h6>Insert the email addres for list '". $row2['name'] ."'</h6>";
	$page .= "<form method='post'  onSubmit=".$q."return confirm('Are you sure?')".$q.">";
	$page .= "<input type=text name='toemail' value='".$row2['toemail']."'><br /><br />";
	$page .= "Select a template to use for sending out your mail<br />";
	$page .= "<select name='template'>";
	$page .= "<option value=0>No Template</option>";
	$req = Sql_Query(sprintf('SELECT * FROM ' . $GLOBALS[table_prefix].'template'));
    while ($row = Sql_Fetch_Array($req)) {
		if($row2['template']==$row['id']){
			$page .= " <option selected value=" . $row["id"] . ">" . $row["title"] . "</option>";
		}else{
	  		$page .= " <option value=" . $row["id"] . ">" . $row["title"] . "</option>";
	  	}
    }
	$page .= "</select><br /><br />";
	$page .= "______________________________________________";
	$page .= "<table><tr><td colspan='2'><h6>POP3 settings:</h6></td></tr>";
	$page .= "<tr><td>Host: </td><td><input type=text name='pophost' value='".$poprow['host']."'>  (mail.yourdomain.com)</td></tr><tr><br />";
	$page .= "<td>Port: </td><td><input type=text name='popport' value='".$poprow['port']."'>  (/pop3:110/notls)</td></tr><tr><br />";
	$page .= "<td>Login: </td><td><input type=text name='poplogin' value='".$poprow['login']."'>  (email@yourdomain.com)</td></tr><tr><br />";
	$page .= "<td>Password:  </td><td><input type=text name='poppass' value='".$poprow['pass']."'></td></tr></table><br /><br />";
	$page .= "*Check with your host if you are not sure<br /><br />";
	$page .= "______________________________________________<br /><br />";
	$page .= "<table><tr><td><h6>Extra settings:</h6></td></tr>";
	$page .= "<tr><td>Queue messages sent to this address?</td>";
	$page .= "<td><select name='queue'>";
	if($poprow['queue']=='no'){
		$page .= "<option value='yes'>Yes</option>";
		$page .= "<option selected value='no'>No</option>";
	}else{
		$page .= "<option selected value='yes'>Yes</option>";
		$page .= "<option value='no'>No</option>";
	}
	$page .= "</td></tr>";
//	$page .= "<tr><td>Leave processed messages in mailbox?<br />(only fetch new mails)</td>";
//	$page .= "<td><select name='del_message'>";
//	if($poprow['del_message']=='no'){
//		$page .= "<option value='yes'>Yes</option>";
//		$page .= "<option selected value='no'>No</option>";
//	}else{
//		$page .= "<option selected value='yes'>Yes</option>";
//		$page .= "<option value='no'>No</option></td></tr>";
//	}
	$page .= "</table>";
	$page .= "<input type=hidden name='list' value='" . $id . "'><br /><br />";
	$page .= "<input type=submit name='action' value='Apply settings'><br /><br />";
	$page .= "<input type=submit name='action' value='Delete settings'>";
	$page .= "</form>";

	print($page2 . $page);
}


#########################
##   EDITWHITELIST()   ##
#########################
function editwhitelist(){

	####################
	//Add User
	if ($_POST['action'] == "Add user"){

	//Validate first
		if (validateMail($_POST['email']) && $_POST['name']!=""){
			Sql_Query("INSERT INTO `" . $GLOBALS[table_prefix]."mail2list_allowsend` (`id` , `email` , `name`) VALUES (NULL, '" . $_POST['email'] . "', '" . $_POST['name'] . "');");
			$page2 = "User " . $_POST['name'] . " has been added";
		}else{
			$page2 = "Addres '" . $_POST['email'] . "' and/or name '" . $_POST['name'] . "' are/is not valid";
		}
	}

	####################
	//Delete User
	if ($_POST['action'] == "Delete user"){

		//Validate first
		if ($_POST['userid'] == ""){
			$page4 = "No user available to delete.";
		}else{
			Sql_Query("DELETE FROM `" . $GLOBALS[table_prefix]."mail2list_allowsend` WHERE id=".$_POST['userid']);
			$page4 = "User " . $_POST['userid'] . " has been deleted";
		}
	}


	$page  = "<h1>Mail to List Configuration page </h1>";
	$page .= "<h6>Add user to whitelist:</h6>";
	$page .= "<form method='post'>";
	$page .= "Name: <input type=text name='name'><br /><br />";
	$page .= "Email: <input type=text name='email'><br /><br />";
	$page .= "<input type=submit name='action' value='Add user'><br /><br />";
	$page .= "<input type=submit name='action' value='Import users'>";
	$page .= "</form>";
	$page3 .= "<br /><br />______________________________________________<br /><br /><br />";
	$page3 .= "<h6>Delete user from whitelist:</h6>";
	$page3 .= "<form method='post'>";
	$page3 .= "<select name='userid'>";
	$req = Sql_Query(sprintf('select * from ' . $GLOBALS[table_prefix].'mail2list_allowsend'));
    while ($row = Sql_Fetch_Array($req)) {
	  $page3 .= " <option value=" . $row["id"] . ">" . $row["name"] . " " . $row["email"] . "</option>";
    }
	$page3 .= "</select><br /><br /><input type=submit name='action' value='Delete user'></form>";


	print($page . $page2 . $page3 . $page4);
}


#########################
##   VALIDATEMAIL()    ##
#########################
function validateMail($email){
	$result = false;
	if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email)) {
		$result = false;
	}else{
		$result = true;
	}
	return $result;
}


#########################
##     OVERVIEW()      ##
#########################
function overview(){
	$page  = "<h1>Mail to List Configuration Overview </h1>";
	$req = Sql_Query('SELECT * FROM ' . $GLOBALS[table_prefix].'mail2list_popaccounts');
    while ($row = Sql_Fetch_Array($req)) {
		$req2 = Sql_Query("SELECT * FROM " . $GLOBALS[table_prefix]."mail2list_list WHERE listid='".$row['listid']."'");
		while ($row2 = Sql_Fetch_Array($req2)) {
			$page .= "<h4>Configuration for list '".$row2['name']."'</h4>";
			$page .= "<b>POP settings:</b><br /><br />";
			$page .= "<table bgcolor='#F9F9F9'><tr><td width='160'>Email address:</td><td>".$row2['toemail']."</td>";
			$page .= "<tr bgcolor='#EBEBEB'><td>Host:</td><td>".$row['host']."</td>";
			$page .= "<tr><td>Port:</td><td>".$row['port']."</td>";
			$page .= "<tr bgcolor='#EBEBEB'><td>Login:</td><td>".$row['login']."</td>";
			$page .= "<tr><td>Password:</td><td>".$row['pass']."</td>";
			$page .= "</table><br />";
			$page .= "<b>Other settings:</b><br /><br />";
			$page .= "<table bgcolor='#F9F9F9'><tr><td width='160'>Template id:</td><td>".$row2['template']."</td>";
			$page .= "<tr bgcolor='#EBEBEB'><td>Store, not queue:</td><td>" . $row['queue'] . "</td>";
			$page .= "<tr><td>Leave message on server:</td><td>" . $row['del_message'] . "</td></tr></table>";
			$page .= "<form method='post'><input type=hidden name='list' value='".$row['listid']."'><input type=submit name='action' value='Edit list'></form>";
			$page .= "______________________________________________<br />";

		}
    }
	print($page);
}

#########################
##   IMPORT_USERS()    ##
#########################
function import_users(){
	//if import: import the users
	if($_POST['action']=='Import'){
		if(!isset($_POST['lists'])){
			$page .= "<font color='red'>Please select at least one list</font>";
		}else{
			//get the users from the selected lists
			//extra options:
			$second = false;
			$options = "(";
			foreach($_POST['lists'] as $list => $on){
				if($second){
					$options .= ' OR listusers.listid = ' . $list;
				}else{
					$options .= ' listusers.listid = ' . $list;
					$second = true;
				}
				
			}
			$options .= ")";
			if(isset($_POST['confirmed'])){
				$options .= " AND users.confirmed = 1";
			}
			$use_att = false;
			$count = 0;
			if(substr($_POST['username'], 0, 5) == "ATT=>"){
				$att = trim(substr($_POST['username'], 5));
				$req = Sql_Query('SELECT id FROM ' . $GLOBALS[table_prefix].'user_attribute WHERE name="' . $att . '"');
				$att_id = Sql_Fetch_Row($req);
				$att_id = ' AND att.attributeid =' . $att_id[0];
				$att1 = ", att.value";
				$att2 = 'INNER JOIN ' . $GLOBALS[table_prefix] .'user_user_attribute AS att ON att.userid = users.id';
				$use_att = true;
			}
			
			//build the user query
			$req = Sql_Query('SELECT users.email' . $att1 . ' FROM ' . $GLOBALS[table_prefix] . 'user_user AS users INNER JOIN ' . $GLOBALS[table_prefix] . 'listuser AS listusers ON users.id = listusers.userid ' . $att2 . ' WHERE' . $options . $att_id);
			while ($row = Sql_Fetch_Row($req)) {
				//add user to whitelist
				if($use_att){
					$insert = 'INSERT INTO ' . $GLOBALS[table_prefix] . 'mail2list_allowsend (email, name) VALUES("' . $row[0] . '","' . $row[1] . '")';
				}else{
					$insert = 'INSERT INTO ' . $GLOBALS[table_prefix] . 'mail2list_allowsend (email, name) VALUES("' . $row[0] . '","' . $_POST['username'] . '")';
				}
				Sql_Query($insert);
				$count ++;
			}
			//remove duplicate entries
			$req = Sql_Query('SELECT email, COUNT(email) AS Num FROM ' . $GLOBALS[table_prefix] . 'mail2list_allowsend GROUP BY email HAVING ( COUNT(email) > 1 )');
			while ($row = Sql_Fetch_Row($req)) {
				Sql_Query('DELETE FROM ' . $GLOBALS[table_prefix] . 'mail2list_allowsend WHERE email="' . $row[0] . '" LIMIT 1');
			}
			
			$page .= "<font color='red'>" . $count . " users have been added to the whitelist.</font>";
		}
	}
	
	
	
	
	
	
	//get list of lists to get users from
	$req = Sql_Query('SELECT lists.id, lists.name FROM ' . $GLOBALS[table_prefix] . 'list AS lists INNER JOIN ' . $GLOBALS[table_prefix] . 'mail2list_list AS mail2lists ON lists.id=mail2lists.listid');
	$lists = "";
	while ($row = Sql_Fetch_Array($req)) {
		$lists .= "<input type='checkbox' name='lists[" . $row['id'] . "]' >" . $row['name'] . "<br />";
	}

	$page .= "<h1>Mail to List Configuration</h1>";
	$page .= "<h6>Import users to whitelist:</h6>";
	$page .= "<form method='post'>";
	$page .= "Get users from these/this list(s):<br />";
	$page .= $lists;
	$page .= "<br /><br /><br />";
	$page .= "User attributes<br /><br />";
	$page .= "<input type='checkbox' name='confirmed' checked='checked' />User has to be confirmed?<br />";
	$page .= "Users name : <input type='text' name='username'/><br />";
	$page .= "(use 'ATT=>name' to get the attribute 'name' as their name)<br /><br />";
	$page .= "<br /><input type=submit name='action' value='Import'>";
	$page .= "</form>";
	
	print($page);
	
}















print("<br /><br /><br /><br /><br /><h6>Email To List By SaWey &copy; 2007</h6>");
?>
