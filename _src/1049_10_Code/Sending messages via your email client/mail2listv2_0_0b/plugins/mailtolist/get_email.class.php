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
	* All other config happens in the web-GUI
	*
	* Modified by SaWey to fit into phplist
	* Comments: EMAIL TO PHPLIST
	* Orig Author: Ernest Wojciuk
	* Orig name: email to db
	*/
	
	#ini_set('max_execution_time', 3000);
	#ini_set('default_socket_timeout', 3000);
	#ini_set('memory_limit','512M');
	
	
	class EMAIL_TO_DB {
		###################################################################################################################
		###################################################################################################################
		##     Edit the URL to your phplist attachment directory (http://www.yourdomain.com/mailinglist/attachments/)    ##
		###################################################################################################################
		 var $attach_url = "http://www.yourdomain.com/mailinglist/attachments/";
		###################################################################################################################
		##	   Edit the path to your phplist attachment directory (/var/www/mailinglist/attachments/)                    ##
		###################################################################################################################
		 var $file_path = "C:/wamp/www/phplist test/lists/tmp/";
		###################################################################################################################
		###################################################################################################################
		
		var $IMAP_host;
		var $IMAP_port;
		var $IMAP_login;
		var $IMAP_pass;
		var $link;
		var $error = array();
		var $imap_error;
		var $status;
		var $max_headers = 'max';
		var $filestore;
		var $partsarray = array();
		var $msgid = 1;
		var $newid;				
		var $file = array(); #File in multpart message
		var $sendformat = "";
		var $queue_message = "yes";
		var $del_message = "no";
		var $processed_message = array();
		var $testmode = false; #when set on true, the messages in the mailbox won't be deleted and will be added EVERY TIME you run this script.
		 
		function connect($host, $port, $login, $pass){
		
			$this->IMAP_host = $host;
			$this->IMAP_login = $login;
			
			$this->link = imap_open("{". $host . $port."}INBOX", $login, $pass);
			
			if($this->link) {
				$this->status = 'Connected'; 
			} else {
				$this->imap_error = imap_last_error();
				$this->status = 'Not connected';
			}//[end if]
		}//[end function]
		
		function set_path(){	
			$path = $this->file_path;
			return $path;
		}//[end function]
		
		function set_filestore(){
			$dir = $this->dir_name();
			$path = $this->set_path();
			$this->filestore = $path.$dir;
		}//[end function]
		
		/**
		* Get mailbox info
		*/
		function mailboxmsginfo(){
		
			//$mailbox = imap_mailboxmsginfo($this->link); #It's very slow
			$mailbox = imap_check($this->link);
		
			if ($mailbox) {
				$mbox["Date"]    = $mailbox->Date;
				$mbox["Driver"]  = $mailbox->Driver;
				$mbox["Mailbox"] = $mailbox->Mailbox;
				$mbox["Messages"]= $this->num_message();
				$mbox["Recent"]  = $this->num_recent();
				$mbox["Unread"]  = $mailbox->Unread;
				$mbox["Deleted"] = $mailbox->Deleted;
				$mbox["Size"]    = $mailbox->Size;
			} else {
				$this->error[] = imap_last_error();
			}//[end if]
		
			return $mbox;
		}
		
		/**
		* Number of Total Emails
		*/
		function num_message(){
			return imap_num_msg($this->link);
		}//[end function]
		
		/**
		* Number of Recent Emails
		*/
		function num_recent(){
			return imap_num_recent($this->link);
		}//[end function]
		
		/**
		* Type and subtype message
		*/
		function msg_type_subtype($_type){
		
			if($_type > 0){
			   switch($_type){
				 case '0': $type = "text"; break;
				 case '1': $type = "multipart"; break;
				 case '2': $type = "message"; break;
				 case '3': $type = "application"; break;
				 case '4': $type = "audio"; break;
				 case '5': $type = "image"; break;
				 case '6': $type = "video"; break;
				 case '7': $type = "other"; break;
			   }//[end switch]
			}//[end if]
		
			return $type;
		}//[end function]
		/**
		* Flag message
		*/
		function email_flag(){
		
			switch ($char) {
				case 'S':
					if (strtolower($flag) == '\\seen') {
						$msg->is_seen = true;
					}
					break;
				case 'A':
					if (strtolower($flag) == '\\answered') {
						$msg->is_answered = true;
					}
					break;
				case 'D':
				   if (strtolower($flag) == '\\deleted') {
						$msg->is_deleted = true;
					}
					break;
				case 'F':
					if (strtolower($flag) == '\\flagged') {
						$msg->is_flagged = true;
					}
					break;
				case 'M':
					if (strtolower($flag) == '$mdnsent') {
						$msg->is_mdnsent = true;
					}
					break;
				default:
					break;
			}
		}//[end function]
		
		/**
		* Parse e-mail structure
		*/
		function parsepart($p,$msgid,$i){
			$part=imap_fetchbody($this->link,$msgid,$i);
			#Multipart
		   
			if ($p->type!=0){
				#if base64
				if ($p->encoding==3)$part=base64_decode($part);
				#if quoted printable
				if ($p->encoding==4)$part=quoted_printable_decode($part);
					#If binary or 8bit -we no need to decode
					#body type (to do)
					switch($p->type) {
						case '5': # image
						$this->partsarray[$i]['image'] = array('filename'=>'imag1','string'=>$part, 'part_no'=>$i);
						break;
				}//[end switch]
		
				#Get attachment
				$filename='';
				if (count($p->dparameters)>0){
					foreach ($p->dparameters as $dparam){
						if ((strtoupper($dparam->attribute)=='NAME') ||(strtoupper($dparam->attribute)=='FILENAME')) $filename=$dparam->value;
					}//[end foreach]
				}//[end if]
				#If no filename
				if ($filename==''){
					if (count($p->parameters)>0){
						foreach ($p->parameters as $param){
							if ((strtoupper($param->attribute)=='NAME') ||(strtoupper($param->attribute)=='FILENAME')) $filename=$param->value;
						}//[end foreach]
					}//[end if]
				}//[end if]
				if ($filename!='' ){
				   $this->partsarray[$i]['attachment'] = array('filename'=>$filename,'string'=>$part, 'encoding'=>$p->encoding, 'part_no'=>$i,'type'=>$p->type,'subtype'=>$p->subtype);
		
				}//[end if]
		   }//[end if]
		
		   #Text email
		   else if($p->type==0){
			   #decode text
			   #if QUOTED-PRINTABLE
			   if ($p->encoding==4) $part=quoted_printable_decode($part);
			   #if base_64
			   if ($p->encoding==3) $part=base64_decode($part);
				
				#see if there's a txt attached
				if ($p->ifdisposition==1){
					   #Get attachment
					   $filename='';
					   if (count($p->dparameters)>0){
							foreach ($p->dparameters as $dparam){
								if ((strtoupper($dparam->attribute)=='NAME') ||(strtoupper($dparam->attribute)=='FILENAME')) $filename=$dparam->value;
							}//[end foreach]
						}//[end if]
						#If no filename
						if ($filename==''){
						   if (count($p->parameters)>0){
								foreach ($p->parameters as $param){
								   if ((strtoupper($param->attribute)=='NAME') ||(strtoupper($param->attribute)=='FILENAME')) $filename=$param->value;
								}//[end foreach]
							}//[end if]
						}//[end if]
						if ($filename!='' ){
						   $this->partsarray[$i]['attachment'] = array('filename'=>$filename,'string'=>$part, 'encoding'=>$p->encoding, 'part_no'=>$i,'type'=>$p->type,'subtype'=>$p->subtype);
						
						}//[end if]
						#if plain text
						if (strtoupper($p->subtype)=='PLAIN')1;
						#if HTML
						else if (strtoupper($p->subtype)=='HTML')1;
						$this->partsarray[$i]['text'] = array('type'=>$p->subtype,'string'=>$part);
				}else{
					#if plain text
					if (strtoupper($p->subtype)=='PLAIN')1;
					#if HTML
					else if (strtoupper($p->subtype)=='HTML')1;
					$this->partsarray[$i]['text'] = array('type'=>$p->subtype,'string'=>$part);
			   }//[end if]
			}//[end if]
		
			#if subparts
			if (count($p->parts)>0){
				foreach ($p->parts as $pno=>$parr){
					$this->parsepart($parr,$this->msgid,($i.'.'.($pno+1)));
				}//[end foreach]
			}//[end if]
			return;
		}//[end function]
		
		/**
		* All email headers
		*/
		function email_headers(){
		
			#$headers=imap_headers($this->link);
			if($this->max_headers == 'max'){
				$headers = imap_fetch_overview($this->link, "1:".$this->num_message(), 0);
			} else {
				$headers = imap_fetch_overview($this->link, "1:".$this->max_headers, 0);
			}//[end if]
			if($this->max_headers == 'max') {
				$num_headers = count($headers);
			 } else {
				$count =  count($headers);
				if($this->max_headers >= $count){
					$num_headers = $count;
				} else {
					$num_headers = $this->max_headers;
				}//[end if]
			}//[end if]
		
			$size=sizeof($headers);
			for($i=1; $i<=$size; $i++){
				$val=$headers[$i];
				//while (list($key, $val) = each($headers)){
				
				$subject_s = (empty($val->subject)) ? '[No subject]' : $val->subject;
				$lp = $lp +1;
				imap_setflag_full($this->link,imap_uid($this->link,$i),'\\SEEN',SE_UID);
				$header=imap_headerinfo($this->link, $i, 80,80);
				
				if($val->seen == "0"  && $val->recent == "0") {
					echo  '<b>'.$val->msgno . '-' . $subject_s . '-' . $val->from .'-'. $val->date."</b><br><hr>" ;
				}//[end if]
				else {
					echo  $val->msgno . '-' . $subject_s . '-' . $val->from .'-'. $val->date."<br><hr>" ;
				}//[end if]
			}//[end for]
		}//[end function]
		
		/**
		* Get email
		*/
		function email_get(){
			$email = array();
			$this->set_filestore();
		
			$header = imap_headerinfo($this->link, $this->msgid, 80,80);
			$from   = $header->from;
			$udate  = $header->udate;
			$to     = $header->to;
			$size   = $header->Size;
		
			if ($header->Unseen == "U" || $header->Recent == "N") {
		
				#Check if it is a multipart messsage
				$s = imap_fetchstructure($this->link,$this->msgid);
				if (count($s->parts)>0){
					foreach ($s->parts as $partno=>$partarr){
						#parse parts of email
						$this->parsepart($partarr,$this->msgid,$partno+1);
					}//[end foreach]
				} else { #for non multipart messages
					#get body of message
					$text=imap_body($this->link,$this->msgid);
					#decode if quoted-printable
					if ($s->encoding==4) $text=quoted_printable_decode($text);
		
					if (strtoupper($s->subtype)=='PLAIN') $text=$text;
					if (strtoupper($s->subtype)=='HTML') $text=$text;
		
					$this->partsarray['not multipart']['text']=array('type'=>$s->subtype,'string'=>$text);
				}//[end if]
		
				if(is_array($from)){
					foreach ($from as $id => $object) {
						$fromname = $object->personal;
						$fromaddress = $object->mailbox . "@" . $object->host;
					}//[end foreach]
				}//[end if]
		
				if(is_array($to)){
					foreach ($from as $id => $object) {
						$toaddress = $object->mailbox . "@" . $object->host;
					}//[end foreach]
				}//[end if]
		
				$email['CHARSET']    = $charset;
				$email['SUBJECT']    = $this->mimie_text_decode($header->Subject);
				$email['FROM_NAME']  = $this->mimie_text_decode($fromname);
				$email['FROM_EMAIL'] = $fromaddress;
				$email['TO_EMAIL']   = $toaddress;
				$email['DATE']       = date("Y-m-d H:i:s",strtotime($header->date));
				$email['SIZE']       = $size;
				#SECTION - FLAGS
				$email['FLAG_RECENT']  = $header->Recent;
				$email['FLAG_UNSEEN']  = $header->Unseen;
				$email['FLAG_ANSWERED']= $header->Answered;
				$email['FLAG_DELETED'] = $header->Deleted;
				$email['FLAG_DRAFT']   = $header->Draft;
				$email['FLAG_FLAGGED'] = $header->Flagged;
				#HEADERS
				$email['HEADER']['from'] = $header->from; 
				$email['HEADER']['toaddress'] = $header->toaddress; 
				$email['HEADER']['to'] = $header->to ; 
				$email['HEADER']['fromaddress'] = $header->fromaddress; 
				$email['HEADER']['udate'] = $header->udate; 
				$email['HEADER']['Size'] = $header->Size; 
				$email['HEADER']['Msgno'] = $header->Msgno; 
		
			}//[end if]
			return $email;
		
		  }//[end function]
		
		function mimie_text_decode($string){
		
			$string = htmlspecialchars(chop($string));
		
			$elements = imap_mime_header_decode($string);
			if(is_array($elements)){
				for ($i=0; $i<count($elements); $i++) {
					$charset = $elements[$i]->charset;
					$txt .= $elements[$i]->text;
				}//[end for]
			} else {
				$txt = $string;
			}//[end if]
			if($txt == ''){
				$txt = 'No_name';
			}//[end if]
			if($charset == 'us-ascii'){
				//$txt = $this->charset_decode_us_ascii ($txt);
			}//[end if]
			return $txt;
		}//[end function]
		
		/**
		* Save messages on local disc
		*/
		function save_files($filename, $part){
		  
			$fp=fopen($this->filestore.$filename,"w+");
			fwrite($fp,$part);
			fclose($fp);
			chown($this->filestore.$filename, 'apache');
			
			//get filesize
			return filesize($this->filestore.$filename);	
		}//[end function]
		   
		/**
		* Set flags
		*/
		function email_setflag(){
			imap_setflag_full($this->link, "2,5","\\Seen \\Flagged");
		}//[end function]
		  
		/**
		* Mark a message for deletion
		*/
		function email_delete(){		
			imap_delete($this->link, $this->msgid);
		}//[end function]
		
		/**
		* Delete marked messages
		*/
		function email_expunge(){
			imap_expunge($this->link);
		}//[end function]
		
		
		/**
		* Close IMAP connection
		*/
		function close(){
			imap_close($this->link);
		}//[end function]
		
		
		function listmailbox(){
			$list = imap_list($this->link, "{".$this->IMAP_host."}", "*");
			if (is_array($list)) {
				return $list;
			} else {
				$this->error =  "imap_list failed: " . imap_last_error() . "\n";
			}//[end if]
			return array();
		}//[end function]
		
		
		/*******************************************************************************
		*							DB FUNCTIONS
		******************************************************************************/
		
		/**
		* Add email to DB
		*/
		function db_add_message($email, $phplist_template, $phplist_listowner, $phplist_listid){
			$message_state = 'inprocess';
			if($this->queue_message=='no'){
				$message_state = 'draft';
			}//[end if]
			
			$sql = "INSERT INTO "  . $GLOBALS['table_prefix'] . "message (subject, fromfield, footer, entered, embargo, status,htmlformatted, sendformat, template, owner) VALUES
				  ('".addslashes($email['SUBJECT'])."',
				  '".$email['FROM_NAME'] . " " . $email['FROM_EMAIL']."',
				  '". htmlentities(getConfig("messagefooter"))."',
				  '".$email['DATE']."',
				  '".$email['DATE']."',
				  '" . $message_state . "',
				  '1',
				  '".$this->sendformat."',
				  '".$phplist_template."',
				  '".$phplist_listowner."')";
			Sql_Query($sql);
		
			$execute = Sql_Query("select LAST_INSERT_ID() as UID");
			$row = Sql_Fetch_Array($execute);
			$this->newid = $row["UID"];
		
			//add to tableprefix_listmessage
			$sql = "INSERT INTO "  . $GLOBALS['table_prefix'] . "listmessage (messageid, listid, entered) VALUES
					('".$this->newid."',
					'".$phplist_listid."',
					'".$email['DATE']."')";
			Sql_Query($sql);
		}//[end function]
		  
		  
		/**
		* Add attachments to DB
		**/
		function db_add_attach($file_orig, $filename, $filesize, $filetype){
			$sql = "INSERT INTO "  . $GLOBALS['table_prefix'] . "attachment (filename, remotefile, size, mimetype) VALUES
				('".addslashes($filename)."',
				 '".addslashes($filename)."',
				 '".$filesize."',
				 '".$filetype."')";
			Sql_Query($sql);
		
			$execute = Sql_Query("select LAST_INSERT_ID() as UID");
			$row = Sql_Fetch_Array($execute);
			$attid = $row["UID"];
		
			//add to tableprefix_message_attachment
			$mess_attsql = "INSERT INTO "  . $GLOBALS['table_prefix'] . "message_attachment (messageid, attachmentid) VALUES
				('".$this->newid."',
				'".$attid."')";
			Sql_Query($mess_attsql);
		
		
		}//[end function]
		
		/**
		* Add email to DB
		*/
		function db_update_message($msg, $type){
			$replace_arr = array(
							1 	=>	"/<\/*HTML*>/i",
							2	=>	"/<\/*HEAD*>/i",
							3 	=>	"/<!*HTML*>/i",
							4 	=>	"/<\/*BODY*>/i",
							5 	=>	"/<\/*TITLE*>/i",
							6 	=>	"/<!([^\[>]+)>/s",
			);
			
			$msg = preg_replace($replace_arr,"",$msg);
			
			if($type == 'HTML') Sql_Query("UPDATE "  . $GLOBALS['table_prefix'] . "message SET message='".addslashes($msg)."', sendformat='".$this->sendformat."' WHERE ID= '".$this->newid."'");
			
			if($type == 'PLAIN') Sql_Query("UPDATE "  . $GLOBALS['table_prefix'] . "message SET textmessage='".addslashes($msg)."', sendformat='".$this->sendformat."' WHERE ID= '".$this->newid."'");
		
		}//[end function]
		
		/**
		* Set folder
		*/
		function dir_name() {
		
			$year  = date('Y');
			$month = date('m');
		
			$dir_n = $year . "_" . $month;
			//echo $this->set_path();
			if (is_dir($this->set_path() . $dir_n)) {
				return $dir_n . '/';
			} else {
				mkdir($this->set_path() . $dir_n, 0777);
				return $dir_n . '/';
			}//[end if]
		}//[end function]
		
		function getImageUrl($i){
			$decoded = $this->partsarray[$i][attachment][filename];
			$decoded = $this->mimie_text_decode($decoded);
			$decoded = preg_replace('/[^a-z0-9_\-\.]/i', '_', $decoded);
			$decoded = $this->attach_url . $this->dir_name() . $this->newid . $decoded;
			return $decoded;
		}//[end function]
		  
		function check_sendformat($format){
			switch ($this->sendformat){
				case "HTML":
					if ($format != "HTML"){
						$this->sendformat = "text and HTML";
					}
					break;
				
				case "text":
					if ($format != "text"){
						$this->sendformat = "text and HTML";
					}
					break;
				
				case "text and HTML":
					break;
				
				case "":
					$this->sendformat = $format;
					break;
			
			}//[end switch]
		  
		}//[end function]
		
		
		
		/*******************************************************************************
		*                                 ACTION FUNCTION
		******************************************************************************/
		  
		function do_action($phplist_template, $phplist_listowner, $phplist_listid, $phplist_toemail, $listname, $phplist_queue){
			
			
			$messagetype = array();
			$messagetype["attachmenttype"][0] = "text";
			$messagetype["attachmenttype"][1] = "multipart";
			$messagetype["attachmenttype"][2] = "message";
			$messagetype["attachmenttype"][3] = "application";
			$messagetype["attachmenttype"][4] = "audio";
			$messagetype["attachmenttype"][5] = "image";
			$messagetype["attachmenttype"][6] = "video";
			$messagetype["attachmenttype"][7] = "other";
			
			//get some vars straight
			if($phplist_queue=='no'){
				$this->queue_message = "no";
			}//[end if]
		
		  //begin processing
		  $bad_messages = 0;
		  while($this->msgid <= $this->num_message()) {
		
				$email = $this->email_get();
				$mail_status_inprocess = $this->msgid;
				$mail_status_total = $this->num_message();
				$go_next = true;
				//First we have to check some things (whitelist, mailinglist,...)
				$req = Sql_Query("SELECT * FROM "  . $GLOBALS['table_prefix'] . "mail2list_allowsend WHERE email='".$email['FROM_EMAIL']."'");
				$whitelistcheck = Sql_Fetch_Array($req);
				if ($whitelistcheck['name'] == ""){
					//user not available, reply with error and delete message
					$this->processed_message[$this->msgid] .= "Processed message <b>" . $mail_status_inprocess . "</b> of <b>" . $mail_status_total . "</b> with error!<br />";
					$this->processed_message[$this->msgid]  .= "<br /><b>".$email['FROM_EMAIL']."</b> could not be found on the whitelist!<br />";
					$this->processed_message[$this->msgid] .= "He/she will be sent a notification.<br /><br />";
			
					//send error reply
					$subject = "Failed to send message with subject '".$email['SUBJECT']."'";
					$message  = "Hi ".$email['FROM_NAME'].",\n\n";
					$message .= "You tried to send an email to ".$phplist_toemail."\n";
					$message .= "but your email addres could not be found on our whitelist.\n";
					$message .= "Please make sure you are added to that list before sending mails.\n\n";
					$message .= "Thank you";
					$message .= "\n\n\nOriginal message:\n\n";
					foreach($this->partsarray as $part){
						if($part['text']['type'] == 'HTML'){
							$message .= $part['text']['string'];
						}elseif($part['text']['type'] == 'PLAIN'){
							$message .= $part['text']['string'];
						}elseif($part[attachment]){
							$message .= "\n\n\n This message had one or more attachments";
						}//[end if]
					}//[end foreach]
					mail($email['FROM_EMAIL'], $subject, $message);
			
					$this->email_setflag();
					if(!$this->testmode){
						$this->email_delete();
						$this->email_expunge();
					}
					
					$go_next = false;
					$bad_messages ++;
				}//[end if]

				if($go_next){
					#Get store dir
					$dir = $this->dir_name();
					
					#Insert message to db
					$ismsgdb = $this->db_add_message($email, $phplist_template, $phplist_listowner, $phplist_listid);
					
					foreach($this->partsarray as $part){
						if($part['text']['type'] == 'HTML'){
							$this->check_sendformat("HTML");
							###################################################
							preg_match_all('/src\=\"cid\:(.*?)\@.*?\"/i', $part['text']['string'], $cids);
							$counter = count($cids[0])+2;
							for ($i = 2; $i<$counter; $i++){
								$part['text']['string'] = str_replace($cids[0][$i-2], "src='" . $this->getImageUrl($i) . "'", $part['text']['string']);
							}//[end for]
							#####################################################
							$this->db_update_message($part['text']['string'], $type= 'HTML');
						
						 }elseif($part['text']['type'] == 'PLAIN'){
							$this->check_sendformat("text");
							$message_PLAIN = $part['text']['string'];
							$this->db_update_message($part['text']['string'], $type= 'PLAIN');
							
						 }elseif($part[attachment]){
							#Save files(attachments) on local disc
						
							foreach(array($part['attachment']) as $attach){
								$attach['filename'] = $this->mimie_text_decode($attach[filename]);
								$attach['filename'] = preg_replace('/[^a-z0-9_\-\.]/i', '_', $attach['filename']);
						
								$filesize = $this->save_files($this->newid.$attach['filename'], $attach['string']);
								$filename =  $dir.$this->newid.$attach['filename'];
								$filetype = $messagetype["attachmenttype"][$attach['type']] . "/" . strtolower($attach['subtype']);
								$this->db_add_attach($attach['filename'], $filename, $filesize, $filetype);
							}//[end foreach]
										
						}elseif($part[image]){
							//Save files(attachments) on local disc
						
							$message_IMAGE[] = $part[image];
						
							foreach($message_IMAGE as $image){
								$image['filename'] = $this->mimie_text_decode($image['filename']);
								$image['filename'] = preg_replace('/[^a-z0-9_\-\.]/i', '_', $image['filename']);
						
								$filesize = $this->save_files($this->newid.$image['filename'], $image[string]);
								$filename =  $dir.$this->newid.$image['filename'];
								$filetype = $messagetype["attachmenttype"][$attach['type']] . "/" . strtolower($attach['subtype']);
								$this->db_add_attach($image['filename'], $filename, $filesize, $filetype);
							}//[end foreach]
					
						}//[end if]
					
					}//[end foreach]
					$this->email_setflag();
					if(!$this->testmode){
						$this->email_delete();
						$this->email_expunge();
					}
					$this->processed_message[$this->msgid] .= "Processed message <b>" . $mail_status_inprocess . "</b> of <b>" . $mail_status_total . "</b><br />";
				}//[end if]
				$this->msgid += 1;
			}//[end while]
					   
		   	//all messages have been processed
		   	if($this->num_message() <= 0) {
				$this->processed_message['none'] .= "No emails where sent to <b>" . $phplist_toemail . "</b>.";
			}else{
				$this->processed_message['Finished'] .= "<br /><b>". ($mail_status_total - $bad_messages) ."</b> emails sent to <b>" . $phplist_toemail . "</b><br />have been forwarded to " . $listname . ".";
			}//[end if]
			$this->close();
		}//[end function]
	
	
	}//[end class]
?>