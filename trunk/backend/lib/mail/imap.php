<?php
/////////////////////////////////////////////////////////
//	
//	Iloha IMAP Library (IIL)
//
//	(C)Copyright 2002 Ryo Chijiiwa <Ryo@IlohaMail.org>
//
//	This file is part of IlohaMail. IlohaMail is free software released 
//	under the GPL license.  See enclosed file COPYING for details, or 
//	see http://www.fsf.org/copyleft/gpl.html
//
/////////////////////////////////////////////////////////

/********************************************************

	FILE: include/imap.inc
	PURPOSE:
		Provide alternative IMAP library that doesn't rely on the standard 
		C-Client based version.  This allows IlohaMail to function regardless
		of whether or not the PHP build it's running on has IMAP functionality
		built-in.
	USEAGE:
		Function containing "_C_" in name require connection handler to be
		passed as one of the parameters.  To obtain connection handler, use
		iil_Connect()

********************************************************/

import("lib.mail.icl_commons");


if (!$IMAP_USE_HEADER_DATE) $IMAP_USE_INTERNAL_DATE = true;
$IMAP_MONTHS=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
$IMAP_SERVER_TZ = date('Z');

$iil_error;
$iil_errornum;
$iil_selected;

class iilConnection{
	var $fp;
	var $error;
	var $errorNum;
	var $selected;
	var $message;
	var $host;
	var $cache;
	var $uid_cache;
	var $do_cache;
	var $exists;
	var $recent;
	var $rootdir;
	var $delimiter;
}

class iilBasicHeader{
	var $id;
	var $uid;
	var $subject;
	var $from;
	var $to;
	var $cc;
	var $replyto;
	var $in_reply_to;
	var $date;
	var $messageID;
	var $size;
	var $encoding;
	var $ctype;
	var $flags;
	var $timestamp;
	var $f;
	var $seen;
	var $deleted;
	var $recent;
	var $answered;
	var $internaldate;
	var $is_reply;
}


class iilThreadHeader{
	var $id;
	var $sbj;
	var $irt;
	var $mid;
}


function iil_xor($string, $string2){
    $result = "";
    $size = strlen($string);
    for ($i=0; $i<$size; $i++) $result .= chr(ord($string[$i]) ^ ord($string2[$i]));
        
    return $result;
}

function iil_ReadLine($fp, $size){
	$line="";
	if ($fp){
		do{
			$buffer = fgets($fp, 2048);
			$line.=$buffer;
		}while($buffer[strlen($buffer)-1]!="\n");
	}
	return $line;
}

function iil_MultLine($fp, $line){
	$line = chop($line);
	if (ereg('\{[0-9]+\}$', $line)){
		$out = "";
		preg_match_all('/(.*)\{([0-9]+)\}$/', $line, $a);
		$bytes = $a[2][0];
		while(strlen($out)<$bytes){
			$out.=chop(iil_ReadLine($fp, 1024));
		}
		$line = $a[1][0]."\"$out\"";
	}
	return $line;
}

function iil_ReadBytes($fp, $bytes){
	$data = "";
	$len = 0;
	do{
		$data.=fread($fp, $bytes-$len);
		$len = strlen($data);
	}while($len<$bytes);
	return $data;
}

function iil_ReadReply($fp){
	do{
		$line = chop(trim(iil_ReadLine($fp, 1024)));
	}while($line[0]=="*");
	
	return $line;
}

function iil_ParseResult($string){
	$a=explode(" ", $string);
	if (count($a) > 2){
		if (strcasecmp($a[1], "OK")==0) return 0;
		else if (strcasecmp($a[1], "NO")==0) return -1;
		else if (strcasecmp($a[1], "BAD")==0) return -2;
	}else return -3;
}

// check if $string starts with $match
function iil_StartsWith($string, $match){
	$len = strlen($match);
	if ($len==0) return false;
	if (strncmp($string, $match, $len)==0) return true;
	else return false;
}

function iil_StartsWithI($string, $match){
	$len = strlen($match);
	if ($len==0) return false;
	if (strncasecmp($string, $match, $len)==0) return true;
	else return false;
}


function iil_C_Authenticate(&$conn, $user, $pass, $encChallenge){
    
    // initialize ipad, opad
    for ($i=0;$i<64;$i++){
        $ipad.=chr(0x36);
        $opad.=chr(0x5C);
    }
    // pad $pass so it's 64 bytes
    $padLen = 64 - strlen($pass);
    for ($i=0;$i<$padLen;$i++) $pass .= chr(0);
    // generate hash
    $hash = md5(iil_xor($pass,$opad).pack("H*",md5(iil_xor($pass, $ipad).base64_decode($encChallenge))));
    // generate reply
    $reply = base64_encode($user." ".$hash);
    
    // send result, get reply
    fputs($conn->fp, $reply."\r\n");
    $line = iil_ReadLine($conn->fp, 1024);
    
    // process result
    if (iil_ParseResult($line)==0){
        $conn->error .= "";
        $conn->errorNum = 0;
        return $conn->fp;
    }else{
        $conn->error .= 'Authentication failed (AUTH): <br>"'.htmlspecialchars($line)."\"";
        $conn->errorNum = -2;
        return false;
    }
}

function iil_C_Login(&$conn, $user, $password){

    fputs($conn->fp, "a001 LOGIN $user \"$password\"\r\n");
		
	do{
	    $line = iil_ReadReply($conn->fp);
	}while(!iil_StartsWith($line, "a001 "));
    $a=explode(" ", $line);
    if (strcmp($a[1],"OK")==0){
        $result=$conn->fp;
        $conn->error.="";
        $conn->errorNum = 0;
    }else{
        $result=false;
        fclose($conn->fp);
        $conn->error .= 'Authentication failed (LOGIN):<br>"'.htmlspecialchars($line)."\"";
        $conn->errorNum = -2;
    }
    return $result;
}

function iil_ParseNamespace2($str, &$i, $len=0, $l){
	if (!$l) $str = str_replace("NIL", "()", $str);
	if (!$len) $len = strlen($str);
	$data = array();
	$in_quotes = false;
	$elem = 0;
	for($i;$i<$len;$i++){
		$c = (string)$str[$i];
		if ($c=='(' && !$in_quotes){
			$i++;
			$data[$elem] = iil_ParseNamespace2($str, $i, $len, $l++);
			$elem++;
		}else if ($c==')' && !$in_quotes) return $data;
		else if ($c=="\\"){
			$i++;
			if ($in_quotes) $data[$elem].=$c.$str[$i];
		}else if ($c=='"'){
			$in_quotes = !$in_quotes;
			if (!$in_quotes) $elem++;
		}else if ($in_quotes){
			$data[$elem].=$c;
		}
	}
	return $data;
}

function iil_C_NameSpace(&$conn){
	global $my_prefs;
	
	if ($my_prefs["rootdir"]) return true;
	
	fputs($conn->fp, "ns1 NAMESPACE\r\n");
	do{
		$line = iil_ReadLine($conn->fp, 1024);
		if (iil_StartsWith($line, "* NAMESPACE")){
			$i = 0;
			$data = iil_ParseNamespace2(substr($line,11), $i, 0, 0);
		}
	}while(!iil_StartsWith($line, "ns1"));
	
	if (!is_array($data)) return false;
	
	$user_space_data = $data[0];
	if (!is_array($user_space_data)) return false;
	
	$first_userspace = $user_space_data[0];
	if (count($first_userspace)!=2) return false;
	
	$conn->rootdir = $first_userspace[0];
	$conn->delimiter = $first_userspace[1];
	$my_prefs["rootdir"] = substr($conn->rootdir, 0, -1);
	
	return true;

}

function iil_Connect($host, $user, $password){	
    global $iil_error, $iil_errornum;
	global $ICL_SSL, $ICL_PORT;
	global $IMAP_NO_CACHE;
	global $my_prefs, $IMAP_USE_INTERNAL_DATE;
	
	$iil_error = "";
	$iil_errornum = 0;
	
	//strip slashes
	$user = stripslashes($user);
	$password = stripslashes($password);
	
	//set auth method
	$auth_method = "plain";
	if (func_num_args() >= 4){
		$auth_array = func_get_arg(3);
		if (is_array($auth_array)) $auth_method = $auth_array["imap"];
		if (empty($auth_method)) $auth_method = "plain";
	}
	$message = "INITIAL: $auth_method\n";
		
	$result = false;
	
	//initialize connection
	$conn = new iilConnection;
	$conn->error="";
	$conn->errorNum=0;
	$conn->selected="";
	$conn->user = $user;
	$conn->host = $host;
	$conn->cache = array();
	$conn->do_cache = (function_exists("cache_write")&&!$IMAP_NO_CACHE);
	$conn->cache_dirty = array();
	
	if ($my_prefs['sort_field']=='INTERNALDATE') $IMAP_USE_INTERNAL_DATE = true;
	else if ($my_prefs['sort_field']=='DATE') $IMAP_USE_INTERNAL_DATE = false;
	//echo '<!-- conn sort_field: '.$my_prefs['sort_field'].' //-->';
	
	//check input
	if (empty($host)) $iil_error .= "Invalid host<br>\n";
	if (empty($user)) $iil_error .= "Invalid user<br>\n";
	if (empty($password)) $iil_error .= "Invalid password<br>\n";
	if (!empty($iil_error)) return false;
	if (!$ICL_PORT) $ICL_PORT = 143;
	
	//check for SSL
	if ($ICL_SSL){
		$host = "ssl://".$host;
	}
	
	//open socket connection
	$conn->fp = @fsockopen($host, $ICL_PORT);
	if (!$conn->fp){
        $iil_error = "Could not connect to $host at port $ICL_PORT";
        $iil_errornum = -1;
		return false;
	}

	$iil_error.="Socket connection established\r\n";
	$line=iil_ReadLine($conn->fp, 300);
						
	if (strcasecmp($auth_method, "check")==0){
		//check for supported auth methods
		
		//default to plain text auth
		$auth_method = "plain";
			
		//check for CRAM-MD5
		fputs($conn->fp, "cp01 CAPABILITY\r\n");
		do{
		$line = trim(chop(iil_ReadLine($conn->fp, 100)));
			$a = explode(" ", $line);
			if ($line[0]=="*"){
				while ( list($k, $w) = each($a) ){
					if ((strcasecmp($w, "AUTH=CRAM_MD5")==0)||
						(strcasecmp($w, "AUTH=CRAM-MD5")==0)){
							$auth_method = "auth";
						}
				}
			}
		}while($a[0]!="cp01");
	}

	if (strcasecmp($auth_method, "auth")==0){
		$conn->message.="Trying CRAM-MD5\n";
		//do CRAM-MD5 authentication
		fputs($conn->fp, "a000 AUTHENTICATE CRAM-MD5\r\n");
		$line = trim(chop(iil_ReadLine($conn->fp, 1024)));
		if ($line[0]=="+"){
			$conn->message.='Got challenge: '.htmlspecialchars($line)."\n";
			//got a challenge string, try CRAM-5
			$result = iil_C_Authenticate($conn, $user, $password, substr($line,2));
			$conn->message.= "Tried CRAM-MD5: $result \n";
		}else{
			$conn->message.='No challenge ('.htmlspecialchars($line)."), try plain\n";
			$auth = "plain";
		}
	}
		
	if ((!$result)||(strcasecmp($auth, "plain")==0)){
		//do plain text auth
		$result = iil_C_Login($conn, $user, $password);
		$conn->message.="Tried PLAIN: $result \n";
	}
		
	$conn->message .= $auth;
			
	if ($result){
		iil_C_Namespace($conn);
		return $conn;
	}else{
		$iil_error = $conn->error;
		$iil_errornum = $conn->errorNum;
		return false;
	}
}

function iil_Close(&$conn){
	iil_C_WriteCache($conn);
	if (@fputs($conn->fp, "I LOGOUT\r\n")){
		fgets($conn->fp, 1024);
		fclose($conn->fp);
		$conn->fp = false;
	}
}

function iil_ClearCache($user, $host){
}


function iil_C_WriteCache(&$conn){
	//echo "<!-- doing iil_C_WriteCache //-->\n";
	if (!$conn->do_cache) return false;
	
	if (is_array($conn->cache)){
		while(list($folder,$data)=each($conn->cache)){
			if ($folder && is_array($data) && $conn->cache_dirty[$folder]){
				$key = $folder.".imap";
				$result = cache_write($conn->user, $conn->host, $key, $data, true);
				//echo "<!-- writing $key $data: $result //-->\n";
			}
		}
	}
}

function iil_C_EnableCache(&$conn){
	$conn->do_cache = true;
}

function iil_C_DisableCache(&$conn){
	$conn->do_cache = false;
}

function iil_C_LoadCache(&$conn, $folder){
	if (!$conn->do_cache) return false;
	
	$key = $folder.".imap";
	if (!is_array($conn->cache[$folder])){
		$conn->cache[$folder] = cache_read($conn->user, $conn->host, $key);
		$conn->cache_dirty[$folder] = false;
	}
}

function iil_C_ExpireCachedItems(&$conn, $folder, $message_set){
	
	if (!$conn->do_cache) return;	//caching disabled
	if (!is_array($conn->cache[$folder])) return;	//cache not initialized|empty
	if (count($conn->cache[$folder])==0) return;	//cache not initialized|empty
		
	$uids = iil_C_FetchHeaderIndex($conn, $folder, $message_set, "UID");
	$num_removed = 0;
	if (is_array($uids)){
		//echo "<!-- unsetting: ".implode(",",$uids)." //-->\n";
		while(list($n,$uid)=each($uids)){
			unset($conn->cache[$folder][$uid]);
			//$conn->cache[$folder][$uid] = false;
			//$num_removed++;
		}
		$conn->cache_dirty[$folder] = true;

		//echo '<!--'."\n";
		//print_r($conn->cache);
		//echo "\n".'//-->'."\n";
	}else{
		echo "<!-- failed to get uids: $message_set //-->\n";
	}
	
	/*
	if ($num_removed>0){
		$new_cache;
		reset($conn->cache[$folder]);
		while(list($uid,$item)=each($conn->cache[$folder])){
			if ($item) $new_cache[$uid] = $conn->cache[$folder][$uid];
		}
		$conn->cache[$folder] = $new_cache;
	}
	*/
}

function iil_ExplodeQuotedString($delimiter, $string){
	$quotes=explode("\"", $string);
	while ( list($key, $val) = each($quotes))
		if (($key % 2) == 1) 
			$quotes[$key] = str_replace($delimiter, "_!@!_", $quotes[$key]);
	$string=implode("\"", $quotes);
	
	$result=explode($delimiter, $string);
	while ( list($key, $val) = each($result) )
		$result[$key] = str_replace("_!@!_", $delimiter, $result[$key]);
	
	return $result;
}

function iil_CheckForRecent($host, $user, $password, $mailbox){
	if (empty($mailbox)) $mailbox="INBOX";
	
	$conn=iil_Connect($host, $user, $password, "plain");
	$fp = $conn->fp;
	if ($fp){
		fputs($fp, "a002 EXAMINE \"$mailbox\"\r\n");
		do{
			$line=chop(iil_ReadLine($fp, 300));
			$a=explode(" ", $line);
			if (($a[0]=="*") && (strcasecmp($a[2], "RECENT")==0))  $result=(int)$a[1];
		}while (!iil_StartsWith($a[0],"a002"));

		fputs($fp, "a003 LOGOUT\r\n");
		fclose($fp);
	}else $result=-2;
	
	return $result;
}

function iil_C_Select(&$conn, $mailbox){
	$fp = $conn->fp;
	
	if (empty($mailbox)) return false;
	if (strcmp($conn->selected, $mailbox)==0) return true;
	
	iil_C_LoadCache($conn, $mailbox);
	
	if (fputs($fp, "sel1 SELECT \"$mailbox\"\r\n")){
		do{
			$line=chop(iil_ReadLine($fp, 300));
			$a=explode(" ", $line);
			if (count($a) == 3){
				if (strcasecmp($a[2], "EXISTS")==0) $conn->exists=(int)$a[1];
				if (strcasecmp($a[2], "RECENT")==0) $conn->recent=(int)$a[1];
			}
		}while (!iil_StartsWith($line, "sel1"));

		$a=explode(" ", $line);

		if (strcasecmp($a[1],"OK")==0){
			$conn->selected = $mailbox;
			return true;
		}else return false;
	}else{
		return false;
	}
}

function iil_C_CheckForRecent(&$conn, $mailbox){
	if (empty($mailbox)) $mailbox="INBOX";
	
	iil_C_Select($conn, $mailbox);
	if ($conn->selected==$mailbox) return $conn->recent;
	else return false;
}

function iil_C_CountMessages(&$conn, $mailbox, $refresh=false){
	if ($refresh) $conn->selected="";
	iil_C_Select($conn, $mailbox);
	if ($conn->selected==$mailbox) return $conn->exists;
	else return false;
}

function iil_SplitHeaderLine($string){
	$pos=strpos($string, ":");
	if ($pos>0){
		$res[0]=substr($string, 0, $pos);
		$res[1]=trim(substr($string, $pos+1));
		return $res;
	}else{
		return $string;
	}
}

function iil_StrToTime($str){
	global $IMAP_MONTHS,$IMAP_SERVER_TZ;
		
	if ($str) $time1 = strtotime($str);
	if ($time1 && $time1!=-1) return $time1-$IMAP_SERVER_TZ;
	
	//echo '<!--'.$str.'//-->';
	
	//replace double spaces with single space
	$str = trim($str);
	$str = str_replace("  ", " ", $str);
	
	//strip off day of week
	$pos=strpos($str, " ");
	if (!is_numeric(substr($str, 0, $pos))) $str = substr($str, $pos+1);

	//explode, take good parts
	$a=explode(" ",$str);
	//$month_a=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
	$month_str=$a[1];
	$month=$IMAP_MONTHS[$month_str];
	$day=$a[0];
	$year=$a[2];
	$time=$a[3];
	$tz_str = $a[4];
	$tz = substr($tz_str, 0, 3);
	$ta=explode(":",$time);
	$hour=(int)$ta[0]-(int)$tz;
	$minute=$ta[1];
	$second=$ta[2];
	
	//make UNIX timestamp
	$time2 = mktime($hour, $minute, $second, $month, $day, $year);
	//echo '<!--'.$time1.' '.$time2.' //-->'."\n";
	return $time2;
}

function iil_C_Sort(&$conn, $mailbox, $field){
	/*  Do "SELECT" command */
	if (!iil_C_Select($conn, $mailbox)) return false;
	
	$field = strtoupper($field);
	if ($field=='INTERNALDATE') $field='ARRIVAL';
	$fields = array('ARRIVAL'=>1,'CC'=>1,'DATE'=>1,'FROM'=>1,'SIZE'=>1,'SUBJECT'=>1,'TO'=>1);
	
	if (!$fields[$field]) return false;
	
	$fp = $conn->fp;
	$command = 's SORT ('.$field.') US-ASCII ALL'."\r\n";
	$line = $data = '';
	
	if (!fputs($fp, $command)) return false;
	do{
		$line = chop(iil_ReadLine($fp, 1024));
		if (iil_StartsWith($line, '* SORT')) $data.=($data?' ':'').substr($line,7);
	}while($line[0]!='s');
	
	if (empty($data)){
		$conn->error = $line;
		return false;
	}
	
	$out = explode(' ',$data);
	return $out;
}

function iil_C_FetchHeaderIndex(&$conn, $mailbox, $message_set, $index_field,$normalize=true){
	global $IMAP_USE_INTERNAL_DATE;
	
	$c=0;
	$result=array();
	$fp = $conn->fp;
		
	if (empty($index_field)) $index_field="DATE";
	$index_field = strtoupper($index_field);
	
	if (empty($message_set)) return array();
	
	//$fields_a["DATE"] = ($IMAP_USE_INTERNAL_DATE?6:1);
	$fields_a['DATE'] = 1;
	$fields_a['INTERNALDATE'] = 6;
	$fields_a['FROM'] = 1;
	$fields_a['REPLY-TO'] = 1;
	$fields_a['SENDER'] = 1;
	$fields_a['TO'] = 1;
	$fields_a['SUBJECT'] = 1;
	$fields_a['UID'] = 2;
	$fields_a['SIZE'] = 2;
	$fields_a['SEEN'] = 3;
	$fields_a['RECENT'] = 4;
	$fields_a['DELETED'] = 5;
	
	$mode=$fields_a[$index_field];
	if (!($mode > 0)) return false;
	
	/*  Do "SELECT" command */
	if (!iil_C_Select($conn, $mailbox)) return false;
		
	/* FETCH date,from,subject headers */
	if ($mode==1){
		$key="fhi".($c++);
		$request=$key." FETCH $message_set (BODY.PEEK[HEADER.FIELDS ($index_field)])\r\n";
		if (!fputs($fp, $request)) return false;
		do{
			
			$line=chop(iil_ReadLine($fp, 200));
			$a=explode(" ", $line);
			if (($line[0]=="*") && ($a[2]=="FETCH") && ($line[strlen($line)-1]!=")")){
				$id=$a[1];

				$str=$line=chop(iil_ReadLine($fp, 300));

				while($line[0]!=")"){					//caution, this line works only in this particular case
					$line=chop(iil_ReadLine($fp, 300));
					if ($line[0]!=")"){
						if (ord($line[0]) <= 32){			//continuation from previous header line
							$str.=" ".trim($line);
						}
						if ((ord($line[0]) > 32) || (strlen($line[0]) == 0)){
							list($field, $string) = iil_SplitHeaderLine($str);
							if (strcasecmp($field, "date")==0){
								$result[$id]=iil_StrToTime($string);
							}else{
								$result[$id] = str_replace("\"", "", $string);
								if ($normalize) $result[$id]=strtoupper($result[$id]);
							}
							$str=$line;
						}
					}
				}
			}
			/*
			$end_pos = strlen($line)-1;
			if (($line[0]=="*") && ($a[2]=="FETCH") && ($line[$end_pos]=="}")){
				$id = $a[1];
				$pos = strrpos($line, "{")+1;
				$bytes = (int)substr($line, $pos, $end_pos-$pos);
				$received = 0;
				do{
					$line = iil_ReadLine($fp, 0);
					$received+=strlen($line);
					$line = chop($line);
					
					if ($received>$bytes) break;
					else if (!$line) continue;
					
					list($field,$string)=explode(": ", $line);
					
					if (strcasecmp($field, "date")==0)
						$result[$id] = iil_StrToTime($string);
					else if ($index_field!="DATE")
						$result[$id]=strtoupper(str_replace("\"", "", $string));
				}while($line[0]!=")");
			}else{
				//one line response, not expected so ignore				
			}
			*/
		}while(!iil_StartsWith($line, $key));
	}else if ($mode==6){
		$key="fhi".($c++);
		$request = $key." FETCH $message_set (INTERNALDATE)\r\n";
		if (!fputs($fp, $request)) return false;
		do{
			$line=chop(iil_ReadLine($fp, 200));
			if ($line[0]=="*"){
				//original: "* 10 FETCH (INTERNALDATE "31-Jul-2002 09:18:02 -0500")"
				$paren_pos = strpos($line, "(");
				$foo = substr($line, 0, $paren_pos);
				$a = explode(" ", $foo);
				$id = $a[1];
				
				$open_pos = strpos($line, "\"") + 1;
				$close_pos = strrpos($line, "\"");
				if ($open_pos && $close_pos){
					$len = $close_pos - $open_pos;
					$time_str = substr($line, $open_pos, $len);
					$result[$id] = strtotime($time_str);
				}
			}else{
				$a = explode(" ", $line);
			}
		}while(!iil_StartsWith($a[0], $key));
	}else{
		if ($mode >= 3) $field_name="FLAGS";
		else if ($index_field=="SIZE") $field_name="RFC822.SIZE";
		else $field_name=$index_field;

		/* 			FETCH uid, size, flags		*/
		$key="fhi".($c++);
		$request=$key." FETCH $message_set ($field_name)\r\n";

		if (!fputs($fp, $request)) return false;
		do{
			$line=chop(iil_ReadLine($fp, 200));
			$a = explode(" ", $line);
			if (($line[0]=="*") && ($a[2]=="FETCH")){
				$line=str_replace("(", "", $line);
				$line=str_replace(")", "", $line);
				$a=explode(" ", $line);
				
				$id=$a[1];

				if (isset($result[$id])) continue; //if we already got the data, skip forward
				if ($a[3]!=$field_name) continue;  //make sure it's returning what we requested
			
				/*  Caution, bad assumptions, next several lines */
				if ($mode==2) $result[$id]=$a[4];
				else{
					$haystack=strtoupper($line);
					$result[$id]=(strpos($haystack, $index_field) > 0 ? "F" : "N");
				}
			}
		}while(!iil_StartsWith($line, $key));
	}

	//check number of elements...
	list($start_mid,$end_mid)=explode(':',$message_set);
	if (is_numeric($start_mid) && is_numeric($end_mid)){
		//count how many we should have
		$should_have = $end_mid - $start_mid +1;
		
		//if we have less, try and fill in the "gaps"
		if (count($result)<$should_have){
			for($i=$start_mid;$i<=$end_mid;$i++) if (!isset($result[$i])) $result[$i] = '';
		}
	}
	
	return $result;	

}

function iil_CompressMessageSet($message_set){
	//given a comma delimited list of independent mid's, 
	//compresses by grouping sequences together
	
	//if less than 255 bytes long, let's not bother
	if (strlen($message_set)<255) return $message_set;
	
	//see if it's already been compress
	if (strpos($message_set,':')!==false) return $message_set;
	
	//separate, then sort
	$ids = explode(',',$message_set);
	sort($ids);
	
	$result = array();
	$start = $prev = $ids[0];
	foreach($ids as $id){
		$incr = $id - $prev;
		if ($incr>1){			//found a gap
			if ($start==$prev) $result[] = $prev;	//push single id
			else $result[] = $start.':'.$prev;		//push sequence as start_id:end_id
			$start = $id;							//start of new sequence
		}
		$prev = $id;
	}
	//handle the last sequence/id
	if ($start==$prev) $result[] = $prev;
	else $result[] = $start.':'.$prev;

	//return as comma separated string
	return implode(',',$result);
}

function iil_C_UIDsToMIDs(&$conn, $mailbox, $uids){
	if (!is_array($uids) || count($uids)==0) return array();
	return iil_C_Search($conn, $mailbox, "UID ".implode(",", $uids));
}

function iil_C_UIDToMID(&$conn, $mailbox, $uid){
	$result = iil_C_UIDsToMIDs($conn, $mailbox, array($uid));
	if (count($result)==1) return $result[0];
	else return false;
}

function iil_C_FetchUIDs(&$conn,$mailbox){
	global $clock;
	
	$num = iil_C_CountMessages(&$conn, $mailbox);
	if ($num==0) return array();
	$message_set = '1'.($num>1?':'.$num:'');
	
	//if cache not enabled, just call iil_C_FetchHeaderIndex on 'UID' field
	if (!$conn->do_cache)
		return iil_C_FetchHeaderIndex($conn, $mailbox, $message_set, 'UID');

	//otherwise, let's check cache first
	$key = $mailbox.'.uids';
	$cache_good = true;
	if ($conn->uid_cache) $data = $conn->uid_cache;
	else $data = cache_read($conn->user, $conn->host, $key);
	
	//was anything cached at all?
	if ($data===false) $cache_good = -1;
	
	//make sure number of messages were the same
	if ($cache_good>0 && $data['n']!=$num) $cache_good = -2;
	
	//if everything's okay so far...
	if ($cache_good>0){
		//check UIDs of highest mid with current and cached
		$temp = iil_C_Search($conn, $mailbox, 'UID '.$data['d'][$num]);
		if (!$temp || !is_array($temp) || $temp[0]!=$num) $cache_good=-3;
	}

	//if cached data's good, return it
	if ($cache_good>0){
		return $data['d'];
	}

	//otherwise, we need to fetch it
	$data = array('n'=>$num,'d'=>array());
	$data['d'] = iil_C_FetchHeaderIndex($conn, $mailbox, $message_set, 'UID');
	cache_write($conn->user, $conn->host, $key, $data);
	$conn->uid_cache = $data;
	return $data['d'];
}

function iil_SortThreadHeaders($headers, $index_a, $uids){
	asort($index_a);
	$result = array();
	foreach($index_a as $mid=>$foobar){
		$uid = $uids[$mid];
		$result[$uid] = $headers[$uid];
	}
	return $result;
}

function iil_C_FetchThreadHeaders(&$conn, $mailbox, $message_set){
	global $clock;
	global $index_a;
	
	if (empty($message_set)) return false;

	$result = array();
	$uids = iil_C_FetchUIDs($conn, $mailbox);
	$debug = false;
	
	/* Get cached records where possible */
	if ($conn->do_cache){
		$cached = cache_read($conn->user, $conn->host, $mailbox.'.thhd');
		if ($cached && is_array($uids) && count($uids)>0){
			$needed_set = "";
			foreach($uids as $id=>$uid){
				if ($cached[$uid]){
					$result[$uid] = $cached[$uid];
					$result[$uid]->id = $id;
				}else $needed_set.=($needed_set?",":"").$id;
			}
			if ($needed_set) $message_set = $needed_set;
			else $message_set = '';
		}
	}
	$message_set = iil_CompressMessageSet($message_set);
	if ($debug) echo "Still need: ".$message_set;
	
	/* if we're missing any, get them */
	if ($message_set){
		/* FETCH date,from,subject headers */
		$key="fh";
		$fp = $conn->fp;
		$request=$key." FETCH $message_set (BODY.PEEK[HEADER.FIELDS (SUBJECT MESSAGE-ID IN-REPLY-TO)])\r\n";
		$mid_to_id = array();
		if (!fputs($fp, $request)) return false;
		do{
			$line = chop(iil_ReadLine($fp, 1024));
			if ($debug) echo $line."\n";
			if (ereg('\{[0-9]+\}$', $line)){
				$a = explode(" ", $line);
				$new = array();

				$new_thhd = new iilThreadHeader;
				$new_thhd->id = $a[1];
				do{
					$line=chop(iil_ReadLine($fp, 1024),"\r\n");
					if (iil_StartsWithI($line,'Message-ID:') || (iil_StartsWithI($line,'In-Reply-To:')) || (iil_StartsWithI($line,'SUBJECT:'))){
						$pos = strpos($line, ":");
						$field_name = substr($line, 0, $pos);
						$field_val = substr($line, $pos+1);
						$new[strtoupper($field_name)] = trim($field_val);
					}else if (ereg('^[[:space:]]', $line)){
						$new[strtoupper($field_name)].= trim($line);
					}
				}while($line[0]!=')');
				$new_thhd->sbj = $new['SUBJECT'];
				$new_thhd->mid = substr($new['MESSAGE-ID'], 1, -1);
				$new_thhd->irt = substr($new['IN-REPLY-TO'], 1, -1);
				
				$result[$uids[$new_thhd->id]] = $new_thhd;
			}
		}while(!iil_StartsWith($line, "fh"));
	}
	
	/* sort headers */
	if (is_array($index_a)){
		$result = iil_SortThreadHeaders($result, $index_a, $uids);	
	}
	
	/* write new set to cache */
	if ($conn->do_cache){
		if (count($result)!=count($cached))
			cache_write($conn->user, $conn->host, $mailbox.'.thhd', $result);		
	}
	
	//echo 'iil_FetchThreadHeaders:'."\n";
	//print_r($result);
	
	return $result;
}

function iil_C_BuildThreads2(&$conn, $mailbox, $message_set, &$clock){
	global $index_a;

	if (empty($message_set)) return false;
	
	$result=array();
	$roots=array();
	$root_mids = array();
	$sub_mids = array();
	$strays = array();
	$messages = array();
	$fp = $conn->fp;
	$debug = false;
	
	$sbj_filter_pat = '[a-zA-Z]{2,3}(\[[0-9]*\])?:([[:space:]]*)';
	
	/*  Do "SELECT" command */
	if (!iil_C_Select($conn, $mailbox)) return false;

	/* FETCH date,from,subject headers */
	$mid_to_id = array();
	$messages = array();
	$headers = iil_C_FetchThreadHeaders($conn, $mailbox, $message_set);
	if ($clock) $clock->register('fetched headers');
	
	if ($debug) print_r($headers);
	
	/* go through header records */
	foreach($headers as $header){
		//$id = $header['i'];
		//$new = array('id'=>$id, 'MESSAGE-ID'=>$header['m'], 
		//			'IN-REPLY-TO'=>$header['r'], 'SUBJECT'=>$header['s']);
		$id = $header->id;
		$new = array('id'=>$id, 'MESSAGE-ID'=>$header->mid, 
					'IN-REPLY-TO'=>$header->irt, 'SUBJECT'=>$header->sbj);

		/* add to message-id -> mid lookup table */
		$mid_to_id[$new['MESSAGE-ID']] = $id;
		
		/* if no subject, use message-id */
		if (empty($new['SUBJECT'])) $new['SUBJECT'] = $new['MESSAGE-ID'];
		
		/* if subject contains 'RE:' or has in-reply-to header, it's a reply */
		$sbj_pre ='';
		$has_re = false;
		if (eregi($sbj_filter_pat, $new['SUBJECT'])) $has_re = true;
		if ($has_re||$new['IN-REPLY-TO']) $sbj_pre = 'RE:';
		
		/* strip out 're:', 'fw:' etc */
		if ($has_re) $sbj = ereg_replace($sbj_filter_pat,'', $new['SUBJECT']);
		else $sbj = $new['SUBJECT'];
		$new['SUBJECT'] = $sbj_pre.$sbj;
		
		
		/* if subject not a known thread-root, add to list */
		if ($debug) echo $id.' '.$new['SUBJECT']."\t".$new['MESSAGE-ID']."\n";
		$root_id = $roots[$sbj];
		
		if ($root_id && ($has_re || !$root_in_root[$root_id])){
			if ($debug) echo "\tfound root: $root_id\n";
			$sub_mids[$new['MESSAGE-ID']] = $root_id;
			$result[$root_id][] = $id;
		}else if (!isset($roots[$sbj])||(!$has_re&&$root_in_root[$root_id])){
			/* try to use In-Reply-To header to find root 
				unless subject contains 'Re:' */
			if ($has_re&&$new['IN-REPLY-TO']){
				if ($debug) echo "\tlooking: ".$new['IN-REPLY-TO']."\n";
				
				//reply to known message?
				$temp = $sub_mids[$new['IN-REPLY-TO']];
				
				if ($temp){
					//found it, root:=parent's root
					if ($debug) echo "\tfound parent: ".$new['SUBJECT']."\n";
					$result[$temp][] = $id;
					$sub_mids[$new['MESSAGE-ID']] = $temp;
					$sbj = '';
				}else{
					//if we can't find referenced parent, it's a "stray"
					$strays[$id] = $new['IN-REPLY-TO'];
				}
			}
			
			//add subject as root
			if ($sbj){
				if ($debug) echo "\t added to root\n";
				$roots[$sbj] = $id;
				$root_in_root[$id] = !$has_re;
				$sub_mids[$new['MESSAGE-ID']] = $id;
				$result[$id] = array($id);
			}
			if ($debug) echo $new['MESSAGE-ID']."\t".$sbj."\n";
		}
			
	}
	
	//now that we've gone through all the messages,
	//go back and try and link up the stray threads
	if (count($strays)>0){
		foreach($strays as $id=>$irt){
			$root_id = $sub_mids[$irt];
			if (!$root_id || $root_id==$id) continue;
			$result[$root_id] = array_merge($result[$root_id],$result[$id]);
			unset($result[$id]);
		}
	}
	
	if ($clock) $clock->register('data prepped');
	
	if ($debug) print_r($roots);
	//print_r($result);
	return $result;
}


function iil_SortThreads(&$tree, $index, $sort_order='ASC'){
	if (!is_array($tree) || !is_array($index)) return false;

	//create an id to position lookup table
	$i = 0;
	foreach($index as $id=>$val){
		$i++;
		$index[$id] = $i;
	}
	$max = $i+1;
	
	//for each tree, set array key to position
	$itree = array();
	foreach($tree as $id=>$node){
		if (count($tree[$id])<=1){
			//for "threads" with only one message, key is position of that message
			$n = $index[$id];
			$itree[$n] = array($n=>$id);
		}else{
			//for "threads" with multiple messages, 
			$min = $max;
			$new_a = array();
			foreach($tree[$id] as $mid){
				$new_a[$index[$mid]] = $mid;		//create new sub-array mapping position to id
				$pos = $index[$mid];
				if ($pos&&$pos<$min) $min = $index[$mid];	//find smallest position
			}
			$n = $min;	//smallest position of child is thread position
			
			//assign smallest position to root level key
			//set children array to one created above
			ksort($new_a);
			$itree[$n] = $new_a;
		}
	}
	
	
	//sort by key, this basically sorts all threads
	ksort($itree);
	$i=0;
	$out=array();
	foreach($itree as $k=>$node){
		$out[$i] = $itree[$k];
		$i++;
	}
	
	//return
	return $out;
}

function iil_IndexThreads(&$tree){
	/* creates array mapping mid to thread id */
	
	if (!is_array($tree)) return false;
	
	$t_index = array();
	foreach($tree as $pos=>$kids){
		foreach($kids as $kid) $t_index[$kid] = $pos;
	}
	
	return $t_index;
}

function iil_C_FetchHeaders(&$conn, $mailbox, $message_set){
	global $IMAP_USE_INTERNAL_DATE;
	
	$c=0;
	$result=array();
	$fp = $conn->fp;
	
	if (empty($message_set)) return array();
	
	/*  Do "SELECT" command */
	if (!iil_C_Select($conn, $mailbox)){
		$conn->error = "Couldn't select $mailbox";
		return false;
	}
		
	/* Get cached records where possible */
	if ($conn->do_cache){
		$uids = iil_C_FetchHeaderIndex($conn, $mailbox, $message_set, "UID");
		if (is_array($uids) && count($conn->cache[$mailbox]>0)){
			$needed_set = "";
			while(list($id,$uid)=each($uids)){
				if ($conn->cache[$mailbox][$uid]){
					$result[$id] = $conn->cache[$mailbox][$uid];
					$result[$id]->id = $id;
				}else $needed_set.=($needed_set?",":"").$id;
			}
			//echo "<!-- iil_C_FetchHeader\nMessage Set: $message_set\nNeeded Set:$needed_set\n//-->\n";
			if ($needed_set) $message_set = iil_CompressMessageSet($needed_set);
			else return $result;
		}
	}

	/* FETCH date,from,subject headers */
	$key="fh".($c++);
	$request=$key." FETCH $message_set (BODY.PEEK[HEADER.FIELDS (DATE FROM TO SUBJECT REPLY-TO IN-REPLY-TO CC CONTENT-TRANSFER-ENCODING CONTENT-TYPE MESSAGE-ID)])\r\n";
		
	if (!fputs($fp, $request)) return false;
	do{
		$line=chop(iil_ReadLine($fp, 200));
		$a=explode(" ", $line);
		if (($line[0]=="*") && ($a[2]=="FETCH")){
			$id=$a[1];
			$result[$id]=new iilBasicHeader;
			$result[$id]->id = $id;
			$result[$id]->subject = "";
			/*
				Start parsing headers.  The problem is, some header "lines" take up multiple lines.
				So, we'll read ahead, and if the one we're reading now is a valid header, we'll
				process the previous line.  Otherwise, we'll keep adding the strings until we come
				to the next valid header line.
			*/
			$i = 0;
			$lines = array();
			do{
				$line = chop(iil_ReadLine($fp, 300),"\r\n");
				if (ord($line[0])<=32) $lines[$i].=(empty($lines[$i])?"":"\n").trim(chop($line));
				else{
					$i++;
					$lines[$i] = trim(chop($line));
				}
			}while($line[0]!=")");
			
			//process header, fill iilBasicHeader obj.
			//	initialize
			if (is_array($headers)){
				reset($headers);
				while ( list($k, $bar) = each($headers) ) $headers[$k] = "";
			}

			//	create array with header field:data
			$headers = array();
			while ( list($lines_key, $str) = each($lines) ){
				list($field, $string) = iil_SplitHeaderLine($str);
				$field = strtolower($field);
				$headers[$field] = $string;
			}
			$result[$id]->date = $headers["date"];
			$result[$id]->timestamp = iil_StrToTime($headers["date"]);
			$result[$id]->from = $headers["from"];
			$result[$id]->to = str_replace("\n", " ", $headers["to"]);
			$result[$id]->subject = str_replace("\n", "", $headers["subject"]);
			$result[$id]->replyto = str_replace("\n", " ", $headers["reply-to"]);
			$result[$id]->cc = str_replace("\n", " ", $headers["cc"]);
			$result[$id]->encoding = str_replace("\n", " ", $headers["content-transfer-encoding"]);
			$result[$id]->ctype = str_replace("\n", " ", $headers["content-type"]);
			//$result[$id]->in_reply_to = ereg_replace("[\n<>]",'', $headers['in-reply-to']);
			list($result[$id]->ctype,$foo) = explode(";", $headers["content-type"]);
			$messageID = $headers["message-id"];
			if ($messageID) $messageID = substr(substr($messageID, 1), 0, strlen($messageID)-2);
			else $messageID = "mid:".$id;
			$result[$id]->messageID = $messageID;
			
		}
	}while(strcmp($a[0], $key)!=0);
		
	/* 
		FETCH uid, size, flags
		Sample reply line: "* 3 FETCH (UID 2417 RFC822.SIZE 2730 FLAGS (\Seen \Deleted))"
	*/
	$command_key="fh".($c++);
	$request= $command_key." FETCH $message_set (UID RFC822.SIZE FLAGS INTERNALDATE)\r\n";
	if (!fputs($fp, $request)) return false;
	do{
		$line=chop(iil_ReadLine($fp, 200));
		//$a = explode(" ", $line);
		//if (($line[0]=="*") && ($a[2]=="FETCH")){
		if ($line[0]=="*"){
			//echo "<!-- $line //-->\n";
			//get outter most parens
			$open_pos = strpos($line, "(") + 1;
			$close_pos = strrpos($line, ")");
			if ($open_pos && $close_pos){
				//extract ID from pre-paren
				$pre_str = substr($line, 0, $open_pos);
				$pre_a = explode(" ", $line);
				$id = $pre_a[1];
				
				//get data
				$len = $close_pos - $open_pos;
				$str = substr($line, $open_pos, $len);
				
				//swap parents with quotes, then explode
				$str = eregi_replace("[()]", "\"", $str);
				$a = iil_ExplodeQuotedString(" ", $str);
				
				//did we get the right number of replies?
				$parts_count = count($a);
				if ($parts_count>=8){
					for ($i=0;$i<$parts_count;$i=$i+2){
						if (strcasecmp($a[$i],"UID")==0) $result[$id]->uid=$a[$i+1];
						else if (strcasecmp($a[$i],"RFC822.SIZE")==0) $result[$id]->size=$a[$i+1];
						else if (strcasecmp($a[$i],"INTERNALDATE")==0) $time_str = $a[$i+1];
						else if (strcasecmp($a[$i],"FLAGS")==0) $flags_str = $a[$i+1];
					}

					// process flags
					$flags_str = eregi_replace('[\\\"]', "", $flags_str);
					$flags_a = explode(" ", $flags_str);
					//echo "<!-- ID: $id FLAGS: ".implode(",", $flags_a)." //-->\n";
					
					$result[$id]->seen = false;
					$result[$id]->recent = false;
					$result[$id]->deleted = false;
					$result[$id]->answered = false;
					if (is_array($flags_a)){
						reset($flags_a);
						while (list($key,$val)=each($flags_a)){
							if (strcasecmp($val,"Seen")==0) $result[$id]->seen = true;
							else if (strcasecmp($val, "Deleted")==0) $result[$id]->deleted=true;
							else if (strcasecmp($val, "Recent")==0) $result[$id]->recent = true;
							else if (strcasecmp($val, "Answered")==0) $result[$id]->answered = true;
						}
						$result[$id]->flags=$flags_str;
					}
			
					// if time is gmt...	
					$time_str = str_replace('GMT','+0000',$time_str);
					
					//get timezone
					$time_str = substr($time_str, 0, -1);
					$time_zone_str = substr($time_str, -5); //extract timezone
					$time_str = substr($time_str, 1, -6); //remove quotes
					$time_zone = (float)substr($time_zone_str, 1, 2); //get first two digits
					if ($time_zone_str[3]!='0') $time_zone += 0.5;  //handle half hour offset
					if ($time_zone_str[0]=="-") $time_zone = $time_zone * -1.0; //minus?
					$result[$id]->internaldate = $time_str;
					
					if ($IMAP_USE_INTERNAL_DATE){
						//calculate timestamp
						$timestamp = strtotime($time_str); //return's server's time
						$na_timestamp = $timestamp;
						$timestamp -= $time_zone * 3600; //compensate for tz, get GMT
						$result[$id]->timestamp = $timestamp;
					}
						
					if ($conn->do_cache){
						$uid = $result[$id]->uid;
						$conn->cache[$mailbox][$uid] = $result[$id];
						$conn->cache_dirty[$mailbox] = true;
					}
					//echo "<!-- ID: $id : $time_str -- local: $na_timestamp (".date("F j, Y, g:i a", $na_timestamp).") tz: $time_zone -- GMT: ".$timestamp." (".date("F j, Y, g:i a", $timestamp).")  //-->\n";
				}else{
					//echo "<!-- ERROR: $id : $str //-->\n";
				}
			}
		}
	}while(strpos($line, $command_key)===false);
		
	return $result;
}


function iil_C_FetchHeader(&$conn, $mailbox, $id){
	$fp = $conn->fp;
	$a=iil_C_FetchHeaders($conn, $mailbox, $id);
	if (is_array($a)) return $a[$id];
	else return false;
}


function iil_SortHeaders($a, $field, $flag){
	if (empty($field)) $field="uid";
	$field=strtolower($field);
	if ($field=="date"||$field=='internaldate') $field="timestamp";
	if (empty($flag)) $flag="ASC";
	$flag=strtoupper($flag);
	
	$c=count($a);
	if ($c>0){
		/*
			Strategy:
			First, we'll create an "index" array.
			Then, we'll use sort() on that array, 
			and use that to sort the main array.
		*/
                
                // create "index" array
		$index=array();
		reset($a);
		while (list($key, $val)=each($a)){
			$data=$a[$key]->$field;
			if (is_string($data)) $data=strtoupper(str_replace("\"", "", $data));
			$index[$key]=$data;
		}
		
		// sort index
		$i=0;
		if ($flag=="ASC") asort($index);
		else arsort($index);
		
		// form new array based on index 
		$result=array();
		reset($index);
		while (list($key, $val)=each($index)){
			$result[$i]=$a[$key];
			$i++;
		}
	}
	
	return $result;
}

function iil_C_Expunge(&$conn, $mailbox){
	$fp = $conn->fp;
	if (iil_C_Select($conn, $mailbox)){
		$c=0;
		fputs($fp, "exp1 EXPUNGE\r\n");
		do{
			$line=chop(iil_ReadLine($fp, 100));
			if ($line[0]=="*") $c++;
		}while (!iil_StartsWith($line, "exp1"));
		
		if (iil_ParseResult($line) == 0){
			$conn->selected = ""; //state has changed, need to reselect			
			//$conn->exists-=$c;
			return $c;
		}else{
			$conn->error = $line;
			return -1;
		}
	}
	
	return -1;
}

function iil_C_ModFlag(&$conn, $mailbox, $messages, $flag, $mod){
	if ($mod!="+" && $mod!="-") return -1;
	
	$fp = $conn->fp;
	$flags=array(
                    "SEEN"=>"\\Seen",
                    "DELETED"=>"\\Deleted",
                    "RECENT"=>"\\Recent",
                    "ANSWERED"=>"\\Answered",
                    "DRAFT"=>"\\Draft",
					"FLAGGED"=>"\\Flagged"
                   );
	$flag=strtoupper($flag);
	$flag=$flags[$flag];
	if (iil_C_Select($conn, $mailbox)){
		$c=0;
		fputs($fp, "flg STORE $messages ".$mod."FLAGS (".$flag.")\r\n");
		do{
			$line=chop(iil_ReadLine($fp, 100));
			if ($line[0]=="*") $c++;
		}while (!iil_StartsWith($line, "flg"));
		
		if (iil_ParseResult($line) == 0){
			iil_C_ExpireCachedItems($conn, $mailbox, $messages);
			return $c;
		}else{
			$conn->error = $line;
			return -1;
		}
	}else{
		$conn->error = "Select failed";
		return -1;
	}
}

function iil_C_Flag(&$conn, $mailbox, $messages, $flag){
	return iil_C_ModFlag($conn, $mailbox, $messages, $flag, "+");
}

function iil_C_Unflag(&$conn, $mailbox, $messages, $flag){
	return iil_C_ModFlag($conn, $mailbox, $messages, $flag, "-");
}

function iil_C_Delete(&$conn, $mailbox, $messages){
	return iil_C_ModFlag($conn, $mailbox, $messages, "DELETED", "+");
}

function iil_C_Undelete(&$conn, $mailbox, $messages){
	return iil_C_ModFlag($conn, $mailbox, $messages, "DELETED", "-");
}


function iil_C_Unseen(&$conn, $mailbox, $messages){
	return iil_C_ModFlag($conn, $mailbox, $messages, "SEEN", "-");
}


function iil_C_Copy(&$conn, $messages, $from, $to){
	$fp = $conn->fp;

	if (empty($from) || empty($to)) return -1;

	if (iil_C_Select($conn, $from)){
		$c=0;
		
		fputs($fp, "cpy1 COPY $messages \"$to\"\r\n");
		$line=iil_ReadReply($fp);
		return iil_ParseResult($line);
	}else{
		return -1;
	}
}

function iil_FormatSearchDate($month, $day, $year){
	$month = (int)$month;
	$months=array(
			1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 
			5=>"May", 6=>"Jun", 7=>"Jul", 8=>"Aug", 
			9=>"Sep", 10=>"Oct", 11=>"Nov", 12=>"Dec"
			);
	return $day."-".$months[$month]."-".$year;
}

function iil_C_CountUnseen(&$conn, $folder){
	$index = iil_C_Search($conn, $folder, "ALL UNSEEN");
	if (is_array($index)){
		$str = implode(",", $index);
		if (empty($str)) return false;
		else return count($index);
	}else return false;
}

function iil_C_UID2ID(&$conn, $folder, $uid){
	if ($uid > 0){
		$id_a = iil_C_Search($conn, $folder, "UID $uid");
		if (is_array($id_a)){
			$count = count($id_a);
			if ($count > 1) return false;
			else return $id_a[0];
		}
	}
	return false;
}

function iil_C_Search(&$conn, $folder, $criteria){
	$fp = $conn->fp;
	if (iil_C_Select($conn, $folder)){
		$c=0;
		
		$query = "srch1 SEARCH ".chop($criteria)."\r\n";
		fputs($fp, $query);
		do{
			$line=trim(chop(iil_ReadLine($fp, 10000)));
			if (eregi("^\* SEARCH", $line)){
				$str = trim(substr($line, 8));
				$messages = explode(" ", $str);
			}
		}while(!iil_StartsWith($line, "srch1"));
		
		$result_code=iil_ParseResult($line);
		if ($result_code==0) return $messages;
		else{
			$conn->error = "iil_C_Search: ".$line."<br>\n";
			return false;
		}
		
	}else{
		$conn->error = "iil_C_Search: Couldn't select \"$folder\" <br>\n";
		return false;
	}
}

function iil_C_Move(&$conn, $messages, $from, $to){
	$fp = $conn->fp;
	
	if (!$from || !$to) return -1;
	
	$r=iil_C_Copy($conn, $messages, $from,$to);
	if ($r==0){
		return iil_C_Delete($conn, $from, $messages);
	}else{
		return $r;
	}
}

function iil_C_GetHierarchyDelimiter(&$conn){
	if ($conn->delimiter) return $conn->delimiter;
	
	$fp = $conn->fp;
	$delimiter = false;
	
	//try (LIST "" ""), should return delimiter (RFC2060 Sec 6.3.8)
	if (!fputs($fp, "ghd LIST \"\" \"\"\r\n")) return false;
	do{
		$line=iil_ReadLine($fp, 500);
		if ($line[0]=="*"){
			$line = rtrim($line);
			$a=iil_ExplodeQuotedString(" ", $line);
			if ($a[0]=="*") $delimiter = str_replace("\"", "", $a[count($a)-2]);
		}
	}while (!iil_StartsWith($line, "ghd"));

	if (strlen($delimiter)>0) return $delimiter;
	
	//if that fails, try namespace extension
	//try to fetch namespace data
	fputs($conn->fp, "ns1 NAMESPACE\r\n");
	do{
		$line = iil_ReadLine($conn->fp, 1024);
		if (iil_StartsWith($line, "* NAMESPACE")){
			$i = 0;
			$data = iil_ParseNamespace2(substr($line,11), $i, 0, 0);
		}
	}while(!iil_StartsWith($line, "ns1"));
		
	if (!is_array($data)) return false;
	
	//extract user space data (opposed to global/shared space)
	$user_space_data = $data[0];
	if (!is_array($user_space_data)) return false;
	
	//get first element
	$first_userspace = $user_space_data[0];
	if (!is_array($first_userspace)) return false;

	//extract delimiter
	$delimiter = $first_userspace[1];	

	return $delimiter;
}

function iil_C_ListMailboxes(&$conn, $ref, $mailbox){
	global $IGNORE_FOLDERS;
	
	$ignore = $IGNORE_FOLDERS[strtolower($conn->host)];
		
	$fp = $conn->fp;
	if (empty($mailbox)) $mailbox="*";
	if (empty($ref) && $conn->rootdir) $ref = $conn->rootdir;
	
    // send command
	if (!fputs($fp, "lmb LIST \"".$ref."\" \"$mailbox\"\r\n")) return false;
	$i=0;
    // get folder list
	do{
		$line=iil_ReadLine($fp, 500);
		$line=iil_MultLine($fp, $line);

		$a = explode(" ", $line);
		if (($line[0]=="*") && ($a[1]=="LIST")){
			$line = rtrim($line);
            // split one line
			$a=iil_ExplodeQuotedString(" ", $line);
            // last string is folder name
			$folder = str_replace("\"", "", $a[count($a)-1]);
            if (empty($ignore) || (!empty($ignore) && !eregi($ignore, $folder))) $folders[$i] = $folder;
            // second from last is delimiter
            $delim = str_replace("\"", "", $a[count($a)-2]);
            // is it a container?
            $i++;
		}
	}while (!iil_StartsWith($line, "lmb"));

	if (is_array($folders)){
        if (!empty($ref)){
            // if rootdir was specified, make sure it's the first element
            // some IMAP servers (i.e. Courier) won't return it
            if ($ref[strlen($ref)-1]==$delim) $ref = substr($ref, 0, strlen($ref)-1);
            if ($folders[0]!=$ref) array_unshift($folders, $ref);
        }
        return $folders;
	}else if (iil_ParseResult($line)==0){
		return array('INBOX');
	}else{
		$conn->error = $line;
		return false;
	}
}


function iil_C_ListSubscribed(&$conn, $ref, $mailbox){
	global $IGNORE_FOLDERS;
	
	$ignore = $IGNORE_FOLDERS[strtolower($conn->host)];
	
	$fp = $conn->fp;
	if (empty($mailbox)) $mailbox = "*";
	if (empty($ref) && $conn->rootdir) $ref = $conn->rootdir;
	$folders = array();

    // send command
	if (!fputs($fp, "lsb LSUB \"".$ref."\" \"".$mailbox."\"\r\n")){
		$conn->error = "Couldn't send LSUB command\n";
		return false;
	}
	$i=0;
    // get folder list
	do{
		$line=iil_ReadLine($fp, 500);
		$line=iil_MultLine($fp, $line);
		$a = explode(" ", $line);
		if (($line[0]=="*") && ($a[1]=="LSUB")){
			$line = rtrim($line);
            // split one line
			$a=iil_ExplodeQuotedString(" ", $line);
            // last string is folder name
            //$folder = UTF7DecodeString(str_replace("\"", "", $a[count($a)-1]));
            $folder = str_replace("\"", "", $a[count($a)-1]);
			if ((!in_array($folder, $folders)) && (empty($ignore) || (!empty($ignore) && !eregi($ignore, $folder)))) $folders[$i] = $folder;
            // second from last is delimiter
            $delim = str_replace("\"", "", $a[count($a)-2]);
            // is it a container?
            $i++;
		}
	}while (!iil_StartsWith($line, "lsb"));

	if (is_array($folders)){
        if (!empty($ref)){
            // if rootdir was specified, make sure it's the first element
            // some IMAP servers (i.e. Courier) won't return it
            if ($ref[strlen($ref)-1]==$delim) $ref = substr($ref, 0, strlen($ref)-1);
            if ($folders[0]!=$ref) array_unshift($folders, $ref);
        }
        return $folders;
	}else{
		$conn->error = $line;
		return false;
	}
}


function iil_C_Subscribe(&$conn, $folder){
	$fp = $conn->fp;

	$query = "sub1 SUBSCRIBE \"".$folder."\"\r\n";
	fputs($fp, $query);
	$line=trim(chop(iil_ReadLine($fp, 10000)));
	return iil_ParseResult($line);
}


function iil_C_UnSubscribe(&$conn, $folder){
	$fp = $conn->fp;

	$query = "usub1 UNSUBSCRIBE \"".$folder."\"\r\n";
	fputs($fp, $query);
	$line=trim(chop(iil_ReadLine($fp, 10000)));
	return iil_ParseResult($line);
}


function iil_C_FetchPartHeader(&$conn, $mailbox, $id, $part){
	$fp = $conn->fp;
	$result=false;
	if (($part==0)||(empty($part))) $part="HEADER";
	else $part.=".MIME";
	
	if (iil_C_Select($conn, $mailbox)){
		$key="fh".($c++);
		$request=$key." FETCH $id (BODY.PEEK[$part])\r\n";
		if (!fputs($fp, $request)) return false;
		do{
			$line=chop(iil_ReadLine($fp, 200));
			$a=explode(" ", $line);
			if (($line[0]=="*") && ($a[2]=="FETCH") && ($line[strlen($line)-1]!=")")){
				$line=iil_ReadLine($fp, 300);
				while(chop($line)!=")"){
					$result.=$line;
					$line=iil_ReadLine($fp, 300);
				}
			}
		}while(strcmp($a[0], $key)!=0);
	}
	
	return $result;
}


function iil_C_HandlePartBody(&$conn, $mailbox, $id, $part, $mode){
    /* modes:
        1: return string
        2: print
        3: base64 and print
    */
	$fp = $conn->fp;
	$result=false;
	if (($part==0)||(empty($part))) $part="TEXT";
	
	if (iil_C_Select($conn, $mailbox)){
        $reply_key="* ".$id;
        // format request
		$key="ftch".($c++)." ";
		$request=$key."FETCH $id (BODY.PEEK[$part])\r\n";
        // send request
		if (!fputs($fp, $request)) return false;
        // receive reply line
        do{
            $line = chop(iil_ReadLine($fp, 1000));
            $a = explode(" ", $line);
        }while ($a[2]!="FETCH");
        $len = strlen($line);
        if ($line[$len-1] == ")"){
            //one line response, get everything between first and last quotes
            $from = strpos($line, "\"") + 1;
            $to = strrpos($line, "\"");
            $len = $to - $from;
            if ($mode==1) $result = substr($line, $from, $len);
            else if ($mode==2) echo substr($line, $from, $len);
            else if ($mode==3) echo base64_decode(substr($line, $from, $len));
        }else if ($line[$len-1] == "}"){
            //multi-line request, find sizes of content and receive that many bytes
            $from = strpos($line, "{") + 1;
            $to = strrpos($line, "}");
            $len = $to - $from;
            $sizeStr = substr($line, $from, $len);
            $bytes = (int)$sizeStr;
            $received = 0;
            while ($received < $bytes){
                $remaining = $bytes - $received;
                $line = iil_ReadLine($fp, 1024);
                $len = strlen($line);
                if ($len > $remaining) substr($line, 0, $remaining);
                $received += strlen($line);
                if ($mode==1) $result .= chop($line)."\n";
                else if ($mode==2){ echo chop($line)."\n"; flush(); }
                else if ($mode==3){ echo base64_decode($line); flush(); }
            }
        }
        // read in anything up until 'til last line
		do{
            $line = iil_ReadLine($fp, 1024);
		}while(!iil_StartsWith($line, $key));
        
        if ($result){
			$result = chop($result);
            return substr($result, 0, strlen($result)-1);
        }else return false;
	}else{
		echo "Select failed.";
	}
    
    if ($mode==1) return $result;
    else return $received;
}

function iil_C_FetchPartBody(&$conn, $mailbox, $id, $part){
    return iil_C_HandlePartBody($conn, $mailbox, $id, $part, 1);
}

function iil_C_PrintPartBody(&$conn, $mailbox, $id, $part){
    iil_C_HandlePartBody($conn, $mailbox, $id, $part, 2);
}

function iil_C_PrintBase64Body(&$conn, $mailbox, $id, $part){
    iil_C_HandlePartBody($conn, $mailbox, $id, $part, 3);
}

function iil_C_CreateFolder(&$conn, $folder){
	$fp = $conn->fp;
	if (fputs($fp, "c CREATE \"".$folder."\"\r\n")){
		do{
			$line=iil_ReadLine($fp, 300);
		}while($line[0]!="c");
        $conn->error = $line;
		return (iil_ParseResult($line)==0);
	}else{
		return false;
	}
}

function iil_C_RenameFolder(&$conn, $from, $to){
	$fp = $conn->fp;
	if (fputs($fp, "r RENAME \"".$from."\" \"".$to."\"\r\n")){
		do{
			$line=iil_ReadLine($fp, 300);
		}while($line[0]!="r");
		return (iil_ParseResult($line)==0);
	}else{
		return false;
	}	
}

function iil_C_DeleteFolder(&$conn, $folder){
	$fp = $conn->fp;
	if (fputs($fp, "d DELETE \"".$folder."\"\r\n")){
		do{
			$line=iil_ReadLine($fp, 300);
		}while($line[0]!="d");
		return (iil_ParseResult($line)==0);
	}else{
		$conn->error = "Couldn't send command\n";
		return false;
	}
}

function iil_C_Append(&$conn, $folder, $message){
	if (!$folder) return false;
	$fp = $conn->fp;

	$message = str_replace("\r", "", $message);
	$message = str_replace("\n", "\r\n", $message);		

	$len = strlen($message);
	if (!$len) return false;
	
	$request="A APPEND \"".$folder."\" (\\Seen) {".$len."}\r\n";
	echo $request.'<br>';
	if (fputs($fp, $request)){
		$line=iil_ReadLine($fp, 100);
		echo $line.'<br>';
		
		$sent = fwrite($fp, $message."\r\n");
		flush();
		do{
			$line=iil_ReadLine($fp, 1000);
			echo $line.'<br>';
		}while($line[0]!="A");
	
		$result = (iil_ParseResult($line)==0);
		if (!$result) $conn->error .= $line."<br>\n";
		return $result;
	
	}else{
		$conn->error .= "Couldn't send command \"$request\"<br>\n";
		return false;
	}
}


function iil_C_AppendFromFile(&$conn, $folder, $path){
	if (!$folder) return false;
	
	//open message file
	$in_fp = false;				
	if (file_exists(realpath($path))) $in_fp = fopen($path, "r");
	if (!$in_fp){ 
		$conn->error .= "Couldn't open $path for reading<br>\n";
		return false;
	}
	
	$fp = $conn->fp;
	$len = filesize($path);
	if (!$len) return false;
	
	//send APPEND command
	$request="A APPEND \"".$folder."\" (\\Seen) {".$len."}\r\n";
	$bytes_sent = 0;
	if (fputs($fp, $request)){
		$line=iil_ReadLine($fp, 100);
				
		//send file
		while(!feof($in_fp)){
			$buffer = fgets($in_fp, 4096);
			$bytes_sent += strlen($buffer);
			fputs($fp, $buffer);
		}
		fclose($in_fp);

		fputs($fp, "\r\n");

		//read response
		do{
			$line=iil_ReadLine($fp, 1000);
			echo $line.'<br>';
		}while($line[0]!="A");
			
		$result = (iil_ParseResult($line)==0);
		if (!$result) $conn->error .= $line."<br>\n";
		return $result;
	
	}else{
		$conn->error .= "Couldn't send command \"$request\"<br>\n";
		return false;
	}
}


function iil_C_FetchStructureString(&$conn, $folder, $id){
	$fp = $conn->fp;
	$result=false;
	if (iil_C_Select($conn, $folder)){
		$key = "F1247";
		if (fputs($fp, "$key FETCH $id (BODYSTRUCTURE)\r\n")){
			do{
				$line=chop(iil_ReadLine($fp, 5000));
				if ($line[0]=="*"){
					if (ereg("\}$", $line)){
						preg_match('/(.+)\{([0-9]+)\}/', $line, $match);  
						$result = $match[1];
						do{
							$line = chop(iil_ReadLine($fp, 100));
							if (!preg_match("/^$key/", $line)) $result .= $line;
							else $done = true;
						}while(!$done);
					}else{
						$result = $line;
					}
					list($pre, $post) = explode("BODYSTRUCTURE ", $result);
					$result = substr($post, 0, strlen($post)-1);		//truncate last ')' and return
				}
			}while (!preg_match("/^$key/",$line));
		}
	}
	return $result;
}

function iil_C_PrintSource(&$conn, $folder, $id, $part){
	$header = iil_C_FetchPartHeader($conn, $folder, $id, $part);
	//echo str_replace("\r", "", $header);
	echo $header;
	echo iil_C_PrintPartBody($conn, $folder, $id, $part);
}

function iil_C_GetQuota(&$conn){
/*
b GETQUOTAROOT "INBOX"
* QUOTAROOT INBOX user/rchijiiwa1
* QUOTA user/rchijiiwa1 (STORAGE 654 9765)
b OK Completed
*/
	$fp = $conn->fp;
	$result=false;
	$quota_line = "";
	
	//get line containing quota info
	if (fputs($fp, "QUOT1 GETQUOTAROOT \"INBOX\"\r\n")){
		do{
			$line=chop(iil_ReadLine($fp, 5000));
			if (iil_StartsWith($line, "* QUOTA ")) $quota_line = $line;
		}while(!iil_StartsWith($line, "QUOT1"));
	}
	
	//return false if not found, parse if found
	if (!empty($quota_line)){
		$quota_line = eregi_replace("[()]", "", $quota_line);
		$parts = explode(" ", $quota_line);
		$storage_part = array_search("STORAGE", $parts);
		if ($storage_part>0){
			$result = array();
			$used = $parts[$storage_part+1];
			$total = $parts[$storage_part+2];
			$result["used"] = $used;
			$result["total"] = (empty($total)?"??":$total);
			$result["percent"] = (empty($total)?"??":round(($used/$total)*100));
			$result["free"] = 100 - $result["percent"];
		}
	}
	
	return $result;
}


function iil_C_ClearFolder(&$conn, $folder){
	$num_in_trash = iil_C_CountMessages($conn, $folder);
	if ($num_in_trash > 0) iil_C_Delete($conn, $folder, "1:".$num_in_trash);
	return (iil_C_Expunge($conn, $folder) >= 0);
}

?>