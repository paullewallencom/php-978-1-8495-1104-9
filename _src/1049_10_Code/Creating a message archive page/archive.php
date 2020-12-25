<?php  // PHP (4>=5) PHPlist
// archive.php
/*
If you want to link to this file from the main index.php, change there the line:
  printf('<p><a href="./?p=unsubscribe">%s</a></p>',$strUnsubscribeTitle);
Into:
  printf('<p><a href="./?p=unsubscribe">%s</a></p>',$strUnsubscribeTitle);
// custom code - start
  if (isset($strArchiveTitle))
    $TitleArchive = $strArchiveTitle;
else
    $TitleArchive = "See the archive";
  printf("\r\n" . '<p><a href="archive.php">%s</a></p>', $TitleArchive);
// custom code - end

Note that $strArchive is just a manual value that you can manually
include in your language file. If you don't do it,
it's also fine - you'll just see it in English

* The additions of the following versions were made by http://lior.weissbrod.com

* @version $Id: v1.9, 2009-10-14
* @package Contribution to PHPlist 2.10.10
* Changes:
* Making sure not to use invalid templates

* @version $Id: v1.8, 2009-10-04
* @package Contribution to PHPlist 2.10.10
* Changes:
+ nl2br only used for plaintext messages
+ Individual page titles when viewing specific items
+ Continued multilingual support

* @version $Id: v1.7, 2008-10-20
* @package Contribution to PHPlist 2.10.5
* Changes:
+ Attachment support (even more so, the file sizes are displayed in human readable form)!
+ layoutID is actually used!
+ layoutID defaults to "none" and is taken automatically from each message's setting
+ Added Content-Type header (taken from the active language file)
+ List descriptions go through stripslashes
+ Started multilingual support (with optional opt-in values styled $strArchiveX)

* Previous version info:

* @version $Id: archive.php, v.1.6, Nov 1, 2006
* This is an adapted version that includes Zappu's modifications
* and has been tested with PHPlist 2.10.3.
* Changes in ver. 1.6:
* - archive.php with no GET requests displays index of active lists.
*   + In doing so, we should display the list name.
*   + Add "View all lists" on archive index for given listID.
* - added <html><head> tags to beginning of document, as a quick fix.
* - <Summary> tags are now case-insensitive
* - Includes a few bug fixes by mp459, described at:
*     http://forums.phplist.com/viewtopic.php?t=1501&postdays=0&postorder=asc&start=45
********************************************************************
* INSTALLATION:
* FTP this file to your phplist/lists/ directory
* The path info assumes archive.php is placed in the directory
* phplist/lists/
* In that case you must INCLUDE archive.php in the .htaccess file.
*
* USE with this command line:
*     http://www.yoursite.com/phplist/lists/archive.php?listID=2&layoutID=1
* where: listID is the list you want to display, and LayoutID is the
* Layout you want to use.
* Note that this is the layout of one of your subscribe pages. It is NOT
* the layout of your newsletter template (which would have been more logical).
* So if necessary, create subscribe pages which only use is that they match the
* header and footer of your newsletter template. Anyway, that was my solution.
* Enjoy, H2B2
*
* For pagination: the default pagination is 20, so 20 messages are shown on
* one page. If you want to change this, you can add "&pagerows=xxx" to the
* URL, eg:
*     http://www.yoursite.com/phplist/lists/archive.php?listID=2&layoutID=1&pagerows=50
* Enjoy, Franky
*******************************************************************
To use summary capability:
Include an area in your newsletter that starts with
<Summary>
and ends with
</Summary>

I enclose this in HTML comment tags <!-- and --> if I don't want
to display the summary in the newsletter.

The main page will display everything between these lines, as <pre>
formatted text.  If you care to include html in your summary, remove the
<pre> tags (printf("<pre">; and printf("</pre>") ).
Note that this is not the most efficient way of doing this, since
the snippet code calls the URL that displays the newsletter, rather
than grabbing it directly from the database.  The snippet script
comes courtesy of phpbuilder, with some changes.
********************************************************************

* @version $Id: archive.php, v.1.5, 05/10/2006, kalan
* @package Contribution to PHPlist 2.10.2
* @copyright (C) 2005 solutions_PHP, www.solutionsphp.com
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* support thread: http://www.phplist.com/forums/viewtopic.php?t=1501
********************************************************************
*/

// Include the common configurations for data host, user, password, name.
//include("./config/config.php");
include_once dirname(__FILE__)."/config/config.php";

// These lines are for Zappu's mod
### I am not sure if all includes are needed, but it works ###
require_once dirname(__FILE__).'/admin/commonlib/lib/magic_quotes.php';
require_once dirname(__FILE__).'/admin/init.php';
require_once dirname(__FILE__).'/admin/'.$GLOBALS["database_module"];
require_once dirname(__FILE__)."/texts/dutch.inc"; #HW: change this to the language file you use
include_once dirname(__FILE__)."/texts/".$GLOBALS["language_module"];
require_once dirname(__FILE__)."/admin/defaultconfig.inc";
require_once dirname(__FILE__).'/admin/connect.php';
include_once dirname(__FILE__)."/admin/languages.php";
include_once dirname(__FILE__)."/admin/lib.php";
//below added by mickey, required in order to get default layout ID
include_once dirname(__FILE__)."/admin/defaultconfig.inc";
include_once dirname(__FILE__)."/admin/mysql.inc";

//some sanitation
if (!(isset($_GET['listID'])) || ($_GET['listID']<1) ) {
   $listID = 0;      // Later in the code, if $listID==0, it just lists the active lists.
} else {
   $listID = intval($_GET['listID']);
}
if (!(isset($_GET['layoutID'])) || ($_GET['layoutID']<1) ) {
   $layoutID = 1;
} else {
   $layoutID = intval($_GET['layoutID']);
}

// Print the head section of our document using Zappu's mod ....
$data = PageData($_GET['layoutID']);

// v1.8 code - start
$multilingual = array(
 'Direction' => "ltr",
 'ArchiveNoItem' => "No such item",
 'ArchiveTitle' => "Archives",
 'ArchiveList' => "Please select a list to see its archive",
 'First' => "First",
 'Last' => "Last",
 'Next' => "Next",
 'Prev' => "Previous",
 'ViewLists' => "List of mailing lists",
 'NoActiveLists' => "No active lists found",
 'EmptyArchive' => "No entries were found in the archive",
 'InvalidMessage' => "Could not retrieve the selected message",
 'database_unselected' => "Could not select database",
 'database_unconnected' => "Could not connect to database"
);

foreach ($multilingual as $key => $value) {
      if (isset($GLOBALS["str$key"]))
         $$key = $GLOBALS["str$key"];
      else
         $$key = $value;
}
// v1.8 code - end

// v1.7 code - start
   $backlink = "<a href='javascript:history.back();'>&laquo; {$GLOBALS['strBack']}</a>";
   $ArchiveNoItem = "<h2>$ArchiveNoItem</h2>\r\n$backlink";

    header( 'Content-Type: text/html; charset=' . $GLOBALS['strCharSet']);
print("<html><head>\r\n");
// print('<title>'.$GLOBALS["strForwardTitle"].'</title>');
$theheader = str_replace('<td><div align=left>', '<td><div>', $data["header"]);
// v1.7 code - end

// Data query
define("__QUERY__","select * from ".$table_prefix."message,".$table_prefix."list,".$table_prefix."listmessage where ".$table_prefix."list.id=".$listID." and ".$table_prefix."list.id=".$table_prefix."listmessage.listid and ".$table_prefix."message.id=".$table_prefix."listmessage.messageid and ".$table_prefix."list.active=1");

// maximum 20 rows per page
if (!(isset($_GET['pagerows'])) || ($_GET['pagerows']<1) ) {
   $pagerows = 20;
} else {
   $pagerows = intval($_GET['pagerows']);
}

// if pagenum is not set, or lower then one: set it to page 1
if (!(isset($_GET['pagenum'])) || ($_GET['pagenum']<1) ) {
   $pagenum = 1;
} else {
   $pagenum = intval($_GET['pagenum']);
}

// Decide whether to show a list of titles or show a specific message ....
if(!isset($_GET['x'])) {
    if ($listID == 0) {   // no listID was specified: list all active lists.
       if($DB = mysql_connect($database_host, $database_user, $database_password)){
           if(mysql_select_db($database_name)){
               $_QUERY = "select * from ".$table_prefix."list where active=1";
               if($RS = mysql_query($_QUERY, $DB)) {
                    if(mysql_num_rows($RS)<=0) {
// v1.8 code - start
                       print("<title>$ArchiveTitle</title>\r\n$theheader");
                       print("<br /><h2>$NoActiveLists</h2><br /><br /><a href='javascript:history.back();'>&laquo; {$GLOBALS['strBack']}</a>");
                    } else {
                       print("<title>$ArchiveList</title>\r\n$theheader");
// 1.8 code - end
// 1.7 code - start
                       print("<br /><h1>$ArchiveList</h1>\n");
// 1.7 code - end
                        while($_ROW = mysql_fetch_assoc($RS)){
                            printf('<br /><h2><a href="./%s?listID='.$_ROW['id'].'">'.$_ROW['name'].'</a></h2>', basename($_SERVER['PHP_SELF']));
// v1.7 code - start
                            printf("<p>".stripslashes($_ROW['description'])."</p>\n\n");
// v1.7 code - end
                        }
                    }

                    mysql_free_result($RS);
                    mysql_close($DB);
               }else{
                    mysql_free_result($RS);
                    mysql_close($DB);

// v1.7 code - start
                    print($ArchiveNoItem);
// v1.7 code - end
                    exit(0);
                  }
           }else{
               mysql_close($DB);
// v1.8 code - start
               print("<h2>$database_unselected</h2>");
           }
       }else{
           print("<h2>$database_unconnected</h2>");
// v1.8 code - end
       }
    } else {   // a listID was specified

           if($DB = mysql_connect($database_host, $database_user, $database_password)){

             if(mysql_select_db($database_name)){
              if($tmp_res = mysql_query(__QUERY__, $DB)){
             // find the number of rows returned
             $rows = mysql_num_rows($tmp_res);
        // and tell us the pagenumber of the last page
        $last_page = ceil($rows/$pagerows);
        // let the pagenumber not be larger than the last page
        if ($pagenum > $last_page) {
           $pagenum = $last_page;
        }
        // add a limit statement for the sql
        $query = __QUERY__ ;
        $query .= ' order by sent DESC limit ' .($pagenum - 1) * $pagerows .',' .$pagerows;
        // print the name of the list here, so it doesn't get printed
        // multiple times for each message
        // we only need to fetch one row to know the name
                  $row = mysql_fetch_assoc($tmp_res);
// v1.8 code - start
            print("<title>$ArchiveTitle {$row['name']}</title>\r\n$theheader");
            printf("<br /><h2>$ArchiveTitle {$row['name']}</h2><br />");
// v1.8 code - end

                  mysql_free_result($tmp_res);
         }

              if($RS = mysql_query($query, $DB)){

                  while($_ROW = mysql_fetch_assoc($RS)){

                 // Print the selected newsletter...
                 // Note: the original line of code will take the layoutID from
                 // the command line.
                 // I prefer using the alternative code line where the layoutID
                 // is predefined, because I need the page displaying the
                 // listing of newsletters to be in a different layout than
                 // the newsletters. In my case I use layoutID=3 for displaying
                 // the newsletter itself. I still use the command line layoutID
                 // to display the listing of newsletters, which in my case is
                 // layoutID=1

                 // Original code to print the selected newsletter:
                      //printf("<br /><h2>%s</h2><a href='%s?x=%s&listID=".$listID."&layoutID=".$layoutID."&pagerows=".$pagerows."&pagenum=".$pagenum."'>%s</a><br /><em>Sent: %s</em><br /><br />",
// v1.7 code - start
                      if (!isset($_GET['layoutID']) || $_GET['layoutID']<1)
        $layoutID = $_ROW['template'];
                      printf("<a dir=\"$Direction\" href='%s?x=%s&listID=".$listID."&layoutID=".$layoutID."&pagerows=".$pagerows."&pagenum=".$pagenum."'>%s</a><br /><em>Sent: %s</em><br /><br />",
// v1.7 code - end
                 // Alternative code to print the selected newsletter
                 // with a predefined layoutID:
                     //  printf("<br /><h2>%s</h2><a href='%s?x=%s&listID=".$_GET['listID']."&layoutID=3' target=_blank>%s</a><br /><em>Sent: %s</em><br /><br />",

                      //stripslashes($_ROW['name']),$_SERVER['PHP_SELF'],$_ROW['messageid'],$_ROW['subject'],$_ROW['sent']);
                      $_SERVER['PHP_SELF'],$_ROW['messageid'],$_ROW['subject'],$_ROW['sent']);

                      // mickey: print a snippet of the newsletter
                      printf("<pre>");
                      $fromURL="http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?x=".$_ROW['messageid']."&listID=".$listID."&layoutID=".$layoutID."&pagerows=".$pagerows."&pagenum=".$pagenum;
                      $baseURL="http://".$_Server['SERVER_NAME'] ;
                 includeFrom( $fromURL,
                           $baseURL,
                           "<Summary>",
                           "</Summary>",
                           TRUE );
                      printf("</pre><br />");

                  }

                  if(mysql_num_rows($RS)<=0){
// v1.8 code - start
                      print("<br /><h2>$EmptyArchive</h2><br /><br /><a href='javascript:history.back();'>&laquo; {$GLOBALS['strBack']}</a>");
// v1.8 code - end
                  }

                  mysql_free_result($RS);
                  mysql_close($DB);

// v1.8 code - start
        // the first/previous and next/last links
        echo "<br /><br /><center dir=\"$Direction\">";
        if ($pagenum != 1) {
           echo " <a href='{$_SERVER['PHP_SELF']}?&listID={$listID}&layoutID={$layoutID}&pagerows={$pagerows}&pagenum=1'> &laquo; $First</a> ";
           echo " ";
           $previous = $pagenum-1;
           echo " <a href='{$_SERVER['PHP_SELF']}?&listID={$listID}&layoutID={$layoutID}&pagerows={$pagerows}&pagenum=$previous'> &lsaquo; $Prev</a> ";
        }
        if ($pagenum != $last_page) {
           $next = $pagenum+1;
           echo " <a href='{$_SERVER['PHP_SELF']}?&listID={$listID}&layoutID={$layoutID}&pagerows={$pagerows}&pagenum=$next'> $Next &rsaquo;</a> ";
           echo " ";
           echo " <a href='{$_SERVER['PHP_SELF']}?&listID={$listID}&layoutID={$layoutID}&pagerows={$pagerows}&pagenum=$last_page'> $Last &raquo;</a> ";
        }
        echo "</center>";
// v1.8 code - end

        // Back to archives list of lists
// v1.8 code - start
        echo "\n<hr>\n<center><a href='{$_SERVER['PHP_SELF']}'>$ViewLists</a></center>";
// 1.8 code - end

               }else{

                  mysql_free_result($RS);
                  mysql_close($DB);

// v1.7 code - start
                  print($ArchiveNoItem);
// v1.7 code - end
                  exit(0);

               }
            }else{
                mysql_close($DB);
// v1.8 code - start
                print("<h2>$database_unselected</h2>");
            }
         }else{
             print("<h2>$database_unconnected</h2>");
// v1.8 code - end
         }
    }
} else {   // a message (x) was specified.

           if($DB = mysql_connect($database_host, $database_user, $database_password)){

             if(mysql_select_db($database_name)){

              $_QUERY = sprintf("select * from ".$table_prefix."message where id=%s",intval($_GET['x']));

              if($RS = mysql_query($_QUERY, $DB)){

                  $_ROW = mysql_fetch_assoc($RS);

                 // Re-print the list of selected newsletters after clicking on
                 // the archive link in the header of a displayed newsletter
                 // Note: the original line of code will take the layoutID from
                 // the command line.
                 // I prefer using the alternative code line where the layoutID
                 // is predefined, because I need the page displaying the
                 // listing of newsletters to be in a different layout than
                 // the newsletters.

// v1.8 code - start
    print("<title>{$_ROW['subject']}</title>\r\n$theheader");
// v1.8 code - end

// v1.7 code - start
    $_TEMPQUERY = sprintf("select * from ".$table_prefix."template where id=%s", $layoutID);
    if ($_TEMPRS = mysql_query($_TEMPQUERY, $DB)) {
           $_TEMPROW = mysql_fetch_assoc($_TEMPRS);
// v1.7 code - end
// v1.9 code - start
           if (isset($_TEMPROW["template"])) {
             $_TEMPROW = stripslashes($_TEMPROW["template"]);
             $_ROW['message'] = str_replace("[CONTENT]", $_ROW['message'], $_TEMPROW);
           } else
             unset($_TEMPROW);
           if (!$_ROW['htmlformatted'])
                 $_ROW['message'] = nl2br($_ROW['message']);
           if (isset($_TEMPROW) && strpos($_TEMPROW, "[FOOTER]") !== false) {
               $_ROW['message'] = str_replace("[FOOTER]", nl2br($_ROW['footer']), $_ROW['message']);
// v1.9 code - end
// v1.7 code - start
// Don't include footer twice
               $_ROW['footer'] = "";
           }
    }
                       if (!empty($_ROW['footer']))
                         $_ROW['footer'] = "\r\n<br /><br />" .  $_ROW['footer'];
// v1.7 code - end

                 // Original code to re-print the list of selected newsletters:
// v1.8 code - start
                       printf("<br /><a href=./%s?listID=".$listID."&layoutID=".$layoutID."&pagerows=".$pagerows."&pagenum=".$pagenum.">&laquo; " . $ArchiveTitle . "</a>".
// v1.8 code - end

                 // Alternative code to re-print the list of selected newsletters
                 // with a predefined layoutID:
                   // printf("&nbsp;&nbsp;<a href=./%s?listID=".$_GET['listID']."&layoutID=1>&laquo; <b>Archive</b></a>".

                         "<h1 align='center'>%s</h1>".
                        // "<br>Sent: %s</em><hr /><br />".
                         "<br />%s%s",
                         basename($_SERVER['PHP_SELF']),
                         $_ROW['subject'],
                        // $_ROW['sent'],
// v1.8 code - start
                         $_ROW['message'],
// v1.8 code - end
// v1.7 code - start
                         nl2br($_ROW['footer']));

// If this message contains attachments, list them
                       $i = 0;
                       $_QUERY2 = sprintf("select * from ".$table_prefix."message_attachment where messageid=%s",$_ROW['id']);
                       $RS2 = mysql_query($_QUERY2, $DB);
                       while ($_ROW2 = mysql_fetch_assoc($RS2)) {
        $i++;
                            if ($i == 1)
                                printf("\r\n<hr>\r\n<div dir=\"$Direction\">$strAttachmentIntro\r\n<table border=1>\r\n");
                            printf("<tr><td>$i\r\n");
        $_ROW2 = $_ROW2['attachmentid'];
                            $_QUERY3 = sprintf("select * from ".$table_prefix."attachment where id=%s",$_ROW2);
                            if($RS3 = mysql_query($_QUERY3, $DB)) {
                                $_ROW3 = mysql_fetch_assoc($RS3);
                                $_ROW3['remotefile'] = "<a href=\"http://{$GLOBALS['website']}/{$GLOBALS['pageroot']}/dl.php?id={$_ROW3['id']}\">{$_ROW3['remotefile']}</a>";
                                unset($_ROW3['id'], $_ROW3['filename']);
                                $_ROW3['size'] = size_hum_read($_ROW3['size']);
                                foreach ($_ROW3 as $value)
                                    printf("<td>$value\r\n");
                            }
                       }
                       if ($i > 0)
                           printf("</table>\r\n</div>\r\n");
// v1.7 code - end

                  if(mysql_num_rows($RS)<=0){
// v1.8 code - start
                      print("<br /><h2>$InvalidMessage</h2><br /><br /><a href='javascript:history.back();'>&laquo; {$GLOBALS['strBack']}</a>");
// v1.8 code - end
                  }


                  mysql_free_result($RS);
                  mysql_close($DB);


               }else{

                  mysql_free_result($RS);
                  mysql_close($DB);

// v1.7 code - start
                  print($ArchiveNoItem);
// v1.7 code - end
                  exit(0);

               }
            }else{
                mysql_close($DB);
// v1.8 code - start
                print("<h2>$database_unselected</h2>");
            }
         }else{
             print("<h2>$database_unconnected</h2>");
// v1.8 code - end
         }

}

print($html);
// Print our document's footer using Zappu's mod ....
// print("<P>".$GLOBALS["PoweredBy"]."</p>");
print($data["footer"]);

//Mickey: summary snippet functions:

  function addBase( $baseUrl, $code )
  {
        // This is a very naive implementation, it doesn't properly
        // parse the "img" tag, but only looks for the string
        // '<img src="' - if the 'src' element comes somewhere else
        // within the tag, or if the filename it points to isn't
        // in double-quotes, this routine won't catch it.
        // Also this routine is chock full of magic numbers.  Whooop.
    $pCode = $code; $index = 0;
    while( $index < strlen( $code ) )
    {
      $srcPos = strpos( $pCode, "<img src=\"", $index );
      if( $srcPos != false )
      {
        // check for a ":" in the first 7 characters after 'src=', in which
        // case it's already a URL
        $colonStr = strpos( $pCode, ":", $srcPos );
        $subStrLen = $srcPos + 10 - $index; // 10 = size of "<img src="" string.
        echo( substr( $pCode, $index, $subStrLen) );
        $index += $subStrLen;
        if( ($colonStr < 12) || ($colonStr > 16) || ($colonStr === false) )
            // somewhat arbitrary
        {
          // modify the string by adding the $baseUrl
          echo( $baseUrl );
        }
      }
      else
      {
        $subStrLen = strlen( $code ) - $index;
        echo( substr( $pCode, $index, $subStrLen) );
        $index = strlen( $code );
      }
    }
  }

  function report( $message, $report )
  {
    if( $report != FALSE )
      echo( $message );
  }

  function includeFrom( $fromUrl, $baseUrl, $startString, $endString, $report )
  // $fromUrl - URL to read from, e.g. "http://foo.bar.com/index.html"
  // $baseUrl - Base to apply to local resources, e.g. "http://foo.bar.com/"
  // $startString - String before content you want to include
  // $endString - String after content you want to include
  // $report - "FALSE" if you don't want includeFrom() to insert error
  //           reporting into the output; errors will just cause no output
  {
    $fd = fopen( $fromUrl, "r" );

   // stripos does not exist in PHP <5
   // stripos is a case-insensitive strpos.
    if (!function_exists("stripos")) {
     function stripos($str,$needle,$offset=0)
     {
        return strpos(strtolower($str),strtolower($needle),$offset);
     }
    }

    if( $fd != FALSE )
    {
        $fr = '';
        while ( !feof($fd) ) {
          $fr .= fread( $fd, 8192 );
        }
       fclose( $fd );
      if ($fr) {
         // for PHP <5, you have to change these to strpos(), and it becomes case-sensitive.
        //$start = stripos( $fr, $startString ) + strlen($startString);
        $start = stripos( $fr, $startString ) ;
        $finish = stripos( $fr, $endString ); // - strlen($endString);
       if( ($start != FALSE) && ($finish != FALSE) )
        {
          $start += strlen($startString);
          $length = $finish - $start;
          $code = Substr( $fr, $start, $length );
// if you don't want to get fancy, use echo( $code ) instead of addBase()
          addBase( $baseUrl, $code );
        }
        else
        report( "<! delimiter not found in $fromUrl >", $report );
      }
      else
        report( "<! could not read data from $fromUrl >", $report );
    }
    else
      report( "<! could not open $fromUrl >", $report );
  }

// v1.7 code - start
// Returns a human readable size
function size_hum_read($size) {
  $digits = 1;
  $i=0;
  $iec = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
  while (($size/1024)>1) {
   $size=$size/1024;
   $i++;
  }
  return round($size, $digits) . ' ' . $iec[$i];
}
// v1.7 code - end
?>
