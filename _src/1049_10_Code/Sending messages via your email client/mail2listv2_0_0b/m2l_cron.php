<?php
	/**
	 * EMAIL TO PHPLIST v2.0.0b
	 * This is a plugin for phplist
	 * Created by SaWey (C) 2007
	 *
	 * This page is made to enable cron with the EMAIL TO PHPLIST plugin.
	 * 
	 * 
	 */

	//First we need to include some files
	include_once "./config/config.php"; 
	require_once "./admin/commonlib/lib/magic_quotes.php"; 
	require_once "./admin/init.php"; 
	require_once "./admin/".$GLOBALS["database_module"]; 
	require_once "./admin/defaultconfig.inc"; 
	require_once "./texts/dutch.inc";
	include_once "./texts/".$GLOBALS["language_module"];
	require_once "./admin/connect.php"; 
	include_once "./admin/languages.php"; 
	include_once "./admin/lib.php"; 
	include_once "./admin/defaultconfig.inc"; 
	include_once "./admin/mysql.inc"; 
	include('./admin/' . PLUGIN_ROOTDIR ."/mailtolist/get_email.class.php");

	
	$full_page = "";
	
	$page = "<h1>Mail to List Processing page </h1>";
	$page .= "<p>Mail to List is trying to process all the new messages</p>";
	$page2  = "<br /><br /><br />______________________________________________<br /><br /><br />";
	$page2 .="<br /><br />";
	$page2 .= "<a href='./?page=processqueue'>Click to process queue</a>";

	$full_page .= $page;
	//get available lists
	$accounts = Sql_Num_Rows($GLOBALS['table_prefix']."mail2list_popaccounts");
	$sql = Sql_Query("SELECT * FROM ". $GLOBALS['table_prefix']."mail2list_popaccounts");
	$i = 0;
	while ($row = Sql_Fetch_Array($sql)) {
	  $popaccounts[$i] = $row;
	  $i++;
    }
	$i -=1;
	for ($i ; $i>=0; $i--){
		$sql = Sql_Query("SELECT * FROM ".$GLOBALS['table_prefix']."mail2list_list, ".$GLOBALS['table_prefix']."list WHERE ".$GLOBALS['table_prefix']."mail2list_list.listid=".$GLOBALS['table_prefix']."list.id AND listid='".$popaccounts[$i]['listid']."'");
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

		
		$listname = Sql_Query(sprintf('SELECT '.$GLOBALS['table_prefix'].'list.name FROM ' . $GLOBALS['table_prefix'].'mail2list_list, '.$GLOBALS['table_prefix'].'list WHERE '.$GLOBALS['table_prefix'].'list.id='.$GLOBALS['table_prefix'].'mail2list_list.listid AND '.$GLOBALS['table_prefix'].'mail2list_list.listid='.$phplist_listid));
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
?>