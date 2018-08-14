<?php
define('PREGIP','/^([0-9]|[0-9][0-9]|[01][0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[0-9][0-9]|[01][0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/');
define('PREGSLICEBB','/\[(.+?)\](.+?)\[\/(.+?)\]/ism');
//define('PREGSLICEBB','|\'[([A-Za-z0\'=]*)\'](.*?)\'[/\'\'1\']|ism');
define('PREGEMAIL','/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/');


/**
 * Returns an encrypted & utf8-encoded
 */
function vim_encrypt($pure_string, $encryption_key) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
    return base64_encode($encrypted_string);
}

/**
 * Returns decrypted original string
 */
function vim_decrypt($encrypted_string, $encryption_key) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, base64_decode($encrypted_string), MCRYPT_MODE_ECB, $iv);
    return $decrypted_string?(int)$decrypted_string:$decrypted_string;
}

function my_str_replace_limit($search,$replace,$subject,$limit=1,$start=0){
	$pos=strpos($subject,$search);
	$len=strlen($search);
	if(0<$start)
		for($i=$start;$i>0;$i--){
			$pos=strpos($subject,$search,$pos+$len);
			if(false==$pos) return $subject;
		}
	if(false===$pos) return $subject;
	$subject=substr_replace($subject,$replace,$pos,$len);
	if(1<$limit) $subject=my_str_replace_limit($search,$replace,$subject,--$limit,++$start);
	return $subject;
}
function GetRealIp(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
		//return '194.143.150.134';
	}

function _friendlyErrno($errno){
	if($errno==E_ALL) return 'E_ALL'; // 32767
	$return=array();
	if($errno&E_ERROR) $return[]='E_ERROR'; // 1
	if($errno&E_WARNING) $return[]='E_WARNING'; // 2
	if($errno&E_PARSE) $return[]='E_PARSE'; // 4
	if($errno&E_NOTICE) $return[]='E_NOTICE'; // 8
	if($errno&E_CORE_ERROR) $return[]='E_CORE_ERROR'; // 16
	if($errno&E_CORE_WARNING) $return[]='E_CORE_WARNING'; // 32
	if($errno&E_COMPILE_ERROR) $return[]='E_COMPILE_ERROR'; // 64
	if($errno&E_COMPILE_WARNING) $return[]='E_COMPILE_WARNING'; // 128
	if($errno&E_USER_ERROR) $return[]='E_USER_ERROR'; // 256
	if($errno&E_USER_WARNING) $return[]='E_USER_WARNING'; // 512
	if($errno&E_USER_NOTICE) $return[]='E_USER_NOTICE'; // 1024
	if($errno&E_STRICT) $return[]='E_STRICT'; // 2048
	if($errno&E_RECOVERABLE_ERROR) $return[]='E_RECOVERABLE_ERROR'; // 4096
	if($errno&E_DEPRECATED) $return[]='E_DEPRECATED'; // 8192
	if($errno&E_USER_DEPRECATED) $return[]='E_USER_DEPRECATED'; // 16384
	return implode(' & ',$return);
}
function sendErrorHandler($errno,$msg,$file,$line){
	if(strpos($msg,'imagecreatefrom')!==false || strpos($msg,'add_pa_site.php')!==false || strpos($msg,'Call-time')!==false || strpos($file,'MPDF')!==false || strpos($msg,'iconv()')!==false) return;
	if($errno & En_fatal) unset($_SERVER['tmp_buf']);
	if(FALSE!==strpos($msg,'function.require')) $errno=E_ERROR;
	elseif(FALSE!==strpos($msg,'function.unlink')) $errno=E_NOTICE;
	if('a:3:'==substr($msg,0,4)){
		$msgArr=unserialize($msg);
		$msg=$msgArr['msg'];
		$file=$msgArr['file'];
		$line=$msgArr['line'];
	}
	log::event('error',_friendlyErrno($errno),array('errno'=>$errno,'msg'=>$msg,'file'=>$file,'line'=>$line));
	
	if($errno & En_die){
		header('HTTP/1.1 456 Unrecoverable Error');
		if(ob_get_level()){
			while(ob_get_level()) ob_end_clean();
			header('Content-Encoding: None');
		}
	}
	if($_SERVER['_ctrl']&256) echo '<strong style="color:red">',$msg,' in ',$file,' (',$line,')','</strong>';
	if($errno & En_die){
		echo '<h2 align="center" style="color:#777;">Oops :(<br/>Something is wrong, refer to logs </h2>';
		log::end();
		define('En_exit',TRUE);
		exit;
	}
}
function shutdownFunction(){
	if(defined('En_exit')) return;
	$e=error_get_last();
	if($e['type'] & En_die) sendErrorHandler($e['type'],$e['message'],$e['file'],$e['line']);
	log::end();
	if(ob_get_level()) ob_end_flush();
}

function vim_str_replace($search,$replace,$subject){
	$search_item = explode(',', $search);
	$replace_item = explode(',', $replace);
	//var_dump($search_item,$replace_item);die();
	$subject = str_replace($search_item[0],$replace_item[0],$subject);
	$subject = str_replace($search_item[1],$replace_item[1],$subject);

	return $subject;
}

function my_array_map($charts,$POST){
    $new = array();
    if($charts=='htmlspecialchars'&&is_array($POST)){
        foreach ($POST as $key => $value) {
            if(is_array($value)){ 
                foreach ($value as $k => $v) {
                    if($val = @unserialize($v)) $value[$k]= $val;
                    else $value[$k]= htmlspecialchars($v, ENT_QUOTES);
                }
                
                $new[$key]= $value;
            }else {
                if($val = @unserialize($value)) $new[$key]= $val;
                else $new[$key]= htmlspecialchars($value, ENT_QUOTES);
            }
        }
    }
    return $new;
}
function TrimArray($Input){
    if (!is_array($Input)) return trim($Input);
    return array_map('TrimArray', $Input);
}

function print_pre(){$func_get_args=func_get_args();echo '<pre>';foreach($func_get_args AS $func_get_arg){print_r($func_get_arg);}echo '</pre>';}

function str2num($sNumber){
	$aConventions = localeConv();
	$sNumber = trim((string) $sNumber);
	$bIsNegative = (0 === $aConventions['n_sign_posn'] && '(' === $sNumber{0} && ')' === $sNumber{strlen($sNumber) - 1});
	if(empty($aConventions['mon_decimal_point'])) $aConventions['mon_decimal_point']=','==$aConventions['decimal_point']?'.':',';
	$sCharacters = $aConventions['decimal_point']
		.$aConventions['mon_decimal_point']
		.$aConventions['negative_sign'];
	$sNumber = preg_replace('/[^'.preg_quote($sCharacters).'\d]+/', '', trim((string) $sNumber));
	$iLength = strlen($sNumber);
	if(strlen($aConventions['decimal_point'])) $sNumber = str_replace($aConventions['decimal_point'], '.', $sNumber);
	if(strlen($aConventions['mon_decimal_point'])) $sNumber = str_replace($aConventions['mon_decimal_point'], '.', $sNumber);
	$sNegativeSign = $aConventions['negative_sign'];
	if(strlen($sNegativeSign) && 0 !== $aConventions['n_sign_posn']){
		$bIsNegative = ($sNegativeSign === $sNumber{0} || $sNegativeSign === $sNumber{$iLength - 1});
		if($bIsNegative) $sNumber = str_replace($aConventions['negative_sign'], '', $sNumber);
	}
	$fNumber = (float) $sNumber;
	if($bIsNegative) $fNumber = -$fNumber;
	return $fNumber;
}

/*
if(!function_exists('json_encode')) {  
    function json_encode($value) 
    {
        if (is_int($value)) {
            return (string)$value;   
        } elseif (is_string($value)) {
	        $value = str_replace(array('\\', '/', '"', "\r", "\n", "\b", "\f", "\t"), 
	                             array('\\\\', '\/', '\"', '\r', '\n', '\b', '\f', '\t'), $value);
	        $convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
	        $result = "";
	        for ($i = mb_strlen($value) - 1; $i >= 0; $i--) {
	            $mb_char = mb_substr($value, $i, 1);
	            if (mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match)) {
	                $result = sprintf("\\u%04x", $match[1]) . $result;
	            } else {
	                $result = $mb_char . $result;
	            }
	        }
	        return '"' . $result . '"';                
        } elseif (is_float($value)) {
            return str_replace(",", ".", $value);         
        } elseif (is_null($value)) {
            return 'null';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $with_keys = false;
            $n = count($value);
            for ($i = 0, reset($value); $i < $n; $i++, next($value)) {
                        if (key($value) !== $i) {
			      $with_keys = true;
			      break;
                        }
            }
        } elseif (is_object($value)) {
            $with_keys = true;
        } else {
            return '';
        }
        $result = array();
        if ($with_keys) {
            foreach ($value as $key => $v) {
                $result[] = json_encode((string)$key) . ':' . json_encode($v);    
            }
            return '{' . implode(',', $result) . '}';                
        } else {
            foreach ($value as $key => $v) {
                $result[] = json_encode($v);    
            }
            return '[' . implode(',', $result) . ']';
        }
    } 
}
if ( !function_exists('json_decode') ){
function json_decode($json)
{
    $comment = false;
    $out = '$x=';
 
    for ($i=0; $i<strlen($json); $i++)
    {
        if (!$comment)
        {
            if (($json[$i] == '{') || ($json[$i] == '['))       $out .= ' array(';
            else if (($json[$i] == '}') || ($json[$i] == ']'))   $out .= ')';
            else if ($json[$i] == ':')    $out .= '=>';
            else                         $out .= $json[$i];         
        }
        else $out .= $json[$i];
        if ($json[$i] == '"' && $json[($i-1)]!="\\")    $comment = !$comment;
    }
    eval($out . ';');
    return $x;
}
}*/


function GetUidQuery($sep='&',$create=false){
	if(!isset($_SESSION['uidquery'])||!is_array($_SESSION['uidquery'])) $_SESSION['uidquery']=array('key'=>0);
	if($_SESSION['uidquery']['key']>9) $_SESSION['uidquery']['key']=0;
	$q=$_SESSION['uidquery']['key'].forSQL(substr(md5(rand().time()),3,7));
	$_SESSION['uidquery'][$_SESSION['uidquery']['key']]=$q;
	$_SESSION['uidquery']['key']++;
	if($create) $_GET['uidquery']=$q;
	return $sep.'uidquery='.$q;
}

function CheckGetQuery(){
	$return=false;
	if(isset($_GET['uidquery'])&&isset($_SESSION['uidquery'])){
		$key=substr($_GET['uidquery'],0,1);
		if(isset($_SESSION['uidquery'][$key])){
			$return=($_SESSION['uidquery'][$key]==$_GET['uidquery']);
			$_SESSION['uidquery'][$key]=false;
		}
	}
	return $return;
}

function bbSlice($txt){
	return preg_replace(PREGSLICEBB,'$2',$txt);
}
function bb2html($text){
	$str_search=array(
			"#\[br\]#is",
			"#\[p\](.+?)\[\/p\]#is",
			"#\[b\](.+?)\[\/b\]#is",
			"#\[i\](.+?)\[\/i\]#is",
			"#\[s\](.+?)\[\/s\]#is",
			"#\[u\](.+?)\[\/u\]#is",
			"#\[url=(.+?)\](.+?)\[\/url\]#is",
			"#\[url\](.+?)\[\/url\]#is",
			"#\[img\](.+?)\[\/img\]#is",
			"#\[size=(.+?)\](.+?)\[\/size\]#is",
			"#\[color=(.+?)\](.+?)\[\/color\]#is",
			"#\[list\](.+?)\[\/list\]#is",
			"#\[list=(1|a|I)\](.+?)\[\/list\]#is",
			"#\[\*\](.*)#",
			"#\[h(1|2|3|4|5|6)\](.+?)\[/h\\1\]#is");
	$str_replace=array(
			"<br />",
			"<p>\\1</p>",
			"<strong>\\1</strong>",
			"<span style=\"font-style:italic\">\\1</span>",
			"<span style=\"text-decoration:line-through\">\\1</span>",
			"<span style=\"text-decoration:underline\">\\1</span>",
			"<a href=\"\\1\">\\2</a>",
			"<a href=\"\\1\">\\1</a>",
			"<img src=\"\\1\" />",
			"<span style=\"font-size:\\1pt\">\\2</span>",
			"<span style=\"color:\\1\">\\2</span>",
			"<ul>\\1</ul>",
			"<ol type=\"\\1\">\\2</ol>",
			"<li>\\1</li>",
			"<h\\1>\\2</h\\1>");
	return preg_replace($str_search, $str_replace, $text);
}
function bb2text($text){
	$str_search=array(
			"#\[br\]#is",
			"#\[p\](.+?)\[\/p\]#is",
			"#\[b\](.+?)\[\/b\]#is",
			"#\[i\](.+?)\[\/i\]#is",
			"#\[s\](.+?)\[\/s\]#is",
			"#\[u\](.+?)\[\/u\]#is",
			"#\[url=(.+?)\](.+?)\[\/url\]#is",
			"#\[url\](.+?)\[\/url\]#is",
			"#\[img\](.+?)\[\/img\]#is",
			"#\[size=(.+?)\](.+?)\[\/size\]#is",
			"#\[color=(.+?)\](.+?)\[\/color\]#is",
			"#\[list\](.+?)\[\/list\]#is",
			"#\[list=(1|a|I)\](.+?)\[\/list\]#is",
			"#\[\*\](.*)#",
			"#\[h(1|2|3|4|5|6)\](.+?)\[/h\\1\]#is");
	$str_replace=array(
			"\n",
			"\\1\n",
			"\\1",
			"\\1",
			"\\1",
			"\\1",
			"\\2 \\1",
			"\\1",
			"\\1",
			"\\2",
			"\\2",
			"\\1",
			"\\2",
			"\\1",
			"\\2\n");
	return preg_replace($str_search, $str_replace, $text);
}


function parseCost($cost=0,$precision=2){
	//return preg_match('/^(-?)(\d+)[\.|\,]?(\d+)?$/',(string)round($cost,$precision),$exp)?(!empty($exp[1])?$exp[1]:'').number_format($exp[2],0,',',' ').(!empty($exp[3])?','.$exp[3]:''):'н/д';
	return preg_match('/^(-?)(\d+)[\.|\,]?(\d+)?$/',(string)$cost,$exp)?(!empty($exp[1])?$exp[1]:'').number_format($exp[2],0,',',' ').(!empty($exp[3])?','.substr($exp[3],0,2):''):'н/д';
}

function validateEmail($address){
	return (bool)preg_match(PREGEMAIL,$address);
	//list(,$mailDomain)=explode('@',$address);
//if(checkdnsrr($host[1].'.', 'MX') ) return true;
//if(checkdnsrr($host[1].'.', 'A') ) return true;
//if(checkdnsrr($host[1].'.', 'CNAME') ) return true; 
}

function genPwd($len=8,$alph=false){
	if(!$alph) $alph='0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz-_';
	$alphLen=strlen($alph)-1;
	$str='';
	for(;$len>0;$len--) $str.=substr($alph,rand(0,$alphLen),1);
	return $str;
}

function RFCDate(){
	$tz=date('Z');
	$tzs=($tz < 0) ? '-' : '+';
	$tz=abs($tz);
	$tz=(int)($tz/3600)*100 + ($tz%3600)/60;
	return sprintf("%s %s%04d", date('D, j M Y H:i:s'), $tzs, $tz);
}

define('MAIL_NL',"\n");
define('MAIL_ENCODING','8bit');
function mailHeaderSecure($str){
	return trim(str_replace(array("\r","\n"), '', $str));
}

function mailSend($to='',$head='',$msg='',$options=array()){
	if(!empty($to)&&!empty($head)&&!empty($msg)){
		$options+=array('priority'=>3,'sender'=>defined('MAIL_SENDER')?MAIL_SENDER:'info@clgs.ru','cc'=>NULL,'bcc'=>MAIL_BCC);
		$msgType='plain';
		$to=explode(',',$to);
	
		//$uniq_id=md5(uniqid(time()));
		//$boundary[1]='b1_'.$uniq_id;
		//$boundary[2]='b2_'.$uniq_id;
		
		$headers='Date: '.RFCDate().MAIL_NL;
		$headers.='Return-Path: '.$options['sender'].MAIL_NL;
		//$headers.=mailHeaderLine('To','%TO%');
		//$headers.=mailHeaderLine('From',mailAddrFormat(DOMAIN_MAIN.' <'.$options['sender'].'>'));
		$headers.='From: '.DOMAIN_MAIN.' <'.$options['sender'].'>'.MAIL_NL;
		if($options['bcc']) $headers.='Bcc: '.$options['bcc'].MAIL_NL;
		//$subject=mailHeaderMimeEncode(mailHeaderSecure($head));
		$subject=mailHeaderSecure($head);
		//$headers.=mailHeaderLine('Subject',$subject);
		$headers.='X-Priority: '.$options['priority'].MAIL_NL;
		$headers.='X-Mailer: CMS clgs '.VER.' (clgs.ru)'.MAIL_NL;
		//$headers.=mailHeaderLine('Disposition-Notification-To','<mail@mail.ru>');
		$headers.='MIME-Version: 1.0'.MAIL_NL;
		
		/*switch($msgType){
			case 'plain':
				//$msg.=encodeString($msg,self::ENCODING,MAIL_NL);
			break;
			/*case 'alt':
			$body .= $this->GetBoundary($this->boundary[1], '', 'text/plain', '');
			$body .= $this->EncodeString($this->AltBody, $this->Encoding);
			$body .= $this->LE.$this->LE;
			$body .= $this->GetBoundary($this->boundary[1], '', 'text/html', '');
			$body .= $this->EncodeString($this->Body, $this->Encoding);
			$body .= $this->LE.$this->LE;
			$body .= $this->EndBoundary($this->boundary[1]);
			break;
			case 'attachments':
			$body .= $this->GetBoundary($this->boundary[1], '', '', '');
			$body .= $this->EncodeString($this->Body, $this->Encoding);
			$body .= $this->LE;
			$body .= $this->AttachAll();
			break;
			case 'alt_attachments':
			$body .= sprintf("--%s%s", $this->boundary[1], $this->LE);
			$body .= sprintf("Content-Type: %s;%s" . "\tboundary=\"%s\"%s", 'multipart/alternative', $this->LE, $this->boundary[2], $this->LE.$this->LE);
			$body .= $this->GetBoundary($this->boundary[2], '', 'text/plain', '') . $this->LE; // Create text body
			$body .= $this->EncodeString($this->AltBody, $this->Encoding);
			$body .= $this->LE.$this->LE;
			$body .= $this->GetBoundary($this->boundary[2], '', 'text/html', '') . $this->LE; // Create the HTML body
			$body .= $this->EncodeString($this->Body, $this->Encoding);
			$body .= $this->LE.$this->LE;
			$body .= $this->EndBoundary($this->boundary[2]);
			$body .= $this->AttachAll();
			break;*\/
		}*/
		if(file_exists(homeRootTmpl.'/mail.php')){
			ob_start();
			$msg=bb2html($msg);
			include homeRootTmpl.'/mail.php';
			$msg=ob_get_clean();
			$contentType = 'text/html';
		}
		else{
			$msg=bb2text($msg);
			$contentType = 'text/plain';
		}
		$headers.='Content-Transfer-Encoding: '.MAIL_ENCODING.MAIL_NL;
		$headers.='Content-Type: '.$contentType.'; charset="UTF-8"';

		$send=true;
		foreach($to AS $addr){
			//$addr=mailAddrFormat($addr);
			//if($addr){
				$send=mail($addr, $subject, $msg, $headers/*, '-oi -f '.$options['sender']*/);
			//}
			//else $send=false;
		}
		return $send;
	}
	return false;
}

function sendMail($to,$head,$msg,$data=array(),$options=array()){
	global $_clgs;
	if(!empty($to)&&!empty($head)&&!empty($msg)){
		//$msg=nl2br($msg);
		$msg=str_replace(array("\r","\n"), '[br]', $msg);
		if(count($data)){
			if(file_exists(homeRoot.'/tmpl/mail-data.php')){
				ob_start();
				include(homeRoot.'/tmpl/mail-data.php');
				$msg.=ob_get_clean();
			}
			else{
				$msg.='[br][br]';
				foreach($data AS $d){
					$msg.='[b]'.$d[0].':[/b] '.$d[1].'[br]';
				}
			}
		}
		return mailSend($to,$head,$msg,$options);
	}
	return false;
}

function latinFilter($string='',$to='') {
     $string = preg_replace("/[^A-Za-z|0-9|_]/", $to, $string);
	 return $string;
}

function translit($string='',$add=array()) {
	$ru=array('й'=>'j', 'ц'=>'ts', 'у'=>'u', 'к'=>'k', 'е'=>'e', 'н'=>'n', 'г'=>'g', 'ш'=>'sh', 'щ'=>'sch', 'з'=>'z', 'х'=>'h', 'ъ'=>'"', 'ф'=>'f', 'ы'=>'y', 'в'=>'v', 'а'=>'a', 'п'=>'p', 'р'=>'r', 'о'=>'o', 'л'=>'l', 'д'=>'d', 'ж'=>'zh', 'э'=>'e', 'я'=>'ya', 'ч'=>'ch', 'с'=>'s', 'м'=>'m', 'и'=>'i', 'т'=>'t', 'ь'=>'\'','б'=>'b', 'ю'=>'yu', 'ё'=>'yo',
			'Й'=>'J', 'Ц'=>'TS', 'У'=>'U', 'К'=>'K', 'Е'=>'E', 'Н'=>'N', 'Г'=>'G', 'Ш'=>'SH', 'Щ'=>'SCH', 'З'=>'Z', 'Х'=>'H', 'Ъ'=>'"', 'Ф'=>'F', 'Ы'=>'Y', 'В'=>'V', 'А'=>'A', 'П'=>'P', 'Р'=>'R', 'О'=>'O', 'Л'=>'L', 'Д'=>'D', 'Ж'=>'ZH', 'Э'=>'E', 'Я'=>'YA', 'Ч'=>'CH', 'С'=>'S', 'М'=>'M', 'И'=>'I', 'Т'=>'T', 'Ь'=>'\'', 'Б'=>'B', 'Ю'=>'YU', 'Ё'=>'YO');
	if(is_array($add))
		$ru+=$add;

	return str_replace(array_keys($ru),array_values($ru),$string);
}

function str2url($txt,$translit=URL_TRANSLIT_IS,$limit=0){
	if($translit)
		$txt=strtolower(latinFilter(translit($txt,array(' '=>'_'))));
	else
		$txt=preg_replace("/[^A-Za-z|А-Яа-я|0-9|]/u", '-', $txt);
	$txt=preg_replace("/([_-]){2,}/u",'_',$txt);
	if($limit>0) $txt=substr($txt,0,$limit);
	return $txt;
}

function cutsStr($str,$maxWords=30,$maxChar=300){
	$words=explode(' ',$str);
	if(count($words)>$maxWords)
		$str=implode(' ',array_slice($words,0,$maxWords));
	//if(iconv_strlen($str,'utf-8')>$maxChar)
	if(strlen($str)>$maxChar)
		//$str=iconv_substr($str,0,$maxChar,'utf-8');
		$str=substr($str,0,$maxChar);
	return $str;
}

function cutsStrEndWord($string='', $count=60, $end='&hellip;') {
	/*if(mb_strlen($string)>$count) {
		$cutter = '#';
		$string_cut = wordwrap($string,$count,$cutter);
		$end_cut = explode($cutter,$string_cut,2);
		print_pre($end_cut);
		if(mb_strlen($end_cut[0])>$count)
			$end_cut[0]=substr($end_cut[0],0,$count);
		return $end_cut[0].' '.$end;
	}
	else
		return $string;*/
		/*ktif*/
	if (mb_strlen($string)>$count) {
		$temp=mb_substr($string, 0, $count);
		if (preg_match('/(.*) \S*$/', $temp, $res) && !empty($res[1])) return $res[1].' ...'; else return $temp.' ...';
	} else return $string;
}


function forHTML($mixed='',$maxLength=0){
	if(is_array($mixed))
		return array_map('ForHtml',$mixed);
	else{
		$mixed=htmlspecialchars(trim($mixed),ENT_QUOTES);
		if($maxLength>0)
			$mixed=cutsStr($mixed,$maxLength,$maxLength);
		return $mixed;
	}
}

function ip(){
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])&&preg_match(PREGIP,$_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
	elseif(isset($_SERVER['REMOTE_ADDR'])&&preg_match(PREGIP,$_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
	else return 'unknown';
}

function ip2int($ip) {
   $a=explode(".",$ip);
   return $a[0]*256*256*256+$a[1]*256*256+$a[2]*256+$a[3];
}

/*function unSetSession($name='') {
	if(!empty($name)){
		$name=explode(',',$name);
		foreach($name AS $el){
			$_SESSION[$el]=false;
			unset($_SESSION[$el]);
			session_unregister($el);
		}
		return true;
	}
	return false;
}

function unSetCookie($name='') {
	if(!empty($name)){
		$name=explode(',',$name);
		foreach($name AS $el){
			$_COOKIE[$el]=false;
			setcookie($el,0,CTIME_S-3600,'/');
			unset($_COOKIE[$el]);
		}
		return true;
	}
	return false;
}*/

/**
 * Add : clgs 02.06.10 15:06
 * Delete a file or recursively delete a directory
 */
 
 /*
function recursiveDelete($str){
	if(is_file($str)){
		return @unlink($str);
	}
	elseif(is_dir($str)){
		$scan = glob(rtrim($str,'/').'/'."*");
		foreach($scan AS $index=>$path){
			recursiveDelete(basename($path));
		}
		return @rmdir($str);
	}
}/**/
/*
	//$dirs=glob(rtrim($str,'/').'/'."*");
	$files=glob(rtrim($str,'/').'/'."*");
	foreach($files as $file) {
		var_dump($file);
		if(is_file($file)) {
			@unlink($file);
		}
		elseif(is_dir($file)) {
			$file=basename($file).'/';
			recursiveDelete($str.$file,true);
		}
	}
	if($rmroot)
		return @rmdir($str);
	return true;
}/**/

function unlinkFiles($files,$dir,$rmroot=true){
	if(!is_array($files)||empty($dir)) return;
	if(substr($dir,-1)=='/') $dir=substr($dir,0,-1);
	if(!$idnt=opendir(homeRoot.$dir)) return -1;
	$i=0;
	while(false!==($file=readdir($idnt))){
		if($file=='.'||$file== '..') continue;
		$exp=explode('_',$file);
		$i++;
		if(in_array($exp[1],$files)){
			unlink(homeRoot.$dir.'/'.$file);
			$i--;
		}
	}
	closedir($idnt);
	if(1>$i&&$rmroot) @rmdir(homeRoot.$dir);
	return true; 
}

function unlinkRecursive($dir,$rmroot=true){
	if(substr($dir,-1)=='/') $dir=substr($dir,0,-1);
	if(!$idnt=opendir(homeRoot.$dir)) return false;
	while(false!==($file=readdir($idnt))){
		if($file=='.'||$file== '..') continue;
		if(is_dir(homeRoot.$dir.'/'.$file)) unlinkRecursive($dir.'/'.$file,true);
		else @unlink(homeRoot.$dir.'/'.$file);
	}
	closedir($idnt);
	if($rmroot) @rmdir(homeRoot.$dir);
	return true; 
}


function dirmv($source,$dest,$overWrite=false,$mode=0755){
	if(substr($source,-1)!='/') $source.='/';
	if(substr($dest,-1)!='/') $dest.='/';
	if(!is_dir(homeRoot.$dest)) mkdir(homeRoot.$dest,$mode);

	if(!$idnt=opendir(homeRoot.$source)) return false;
	while(false!==($file=readdir($idnt))){
		if($file=='.'||$file== '..') continue;

		$fileSource=$source.$file;
		$fileDest=$dest.$file;

		if(is_file(homeRoot.$fileSource)){
			if(!is_file(homeRoot.$fileDest))
				rename(homeRoot.$fileSource,homeRoot.$fileDest);
			elseif($overWrite){
				if(unlink(homeRoot.$fileDest)) rename(homeRoot.$fileSource,homeRoot.$fileDest);
			}
		}elseif(is_dir(homeRoot.$fileSource)){
			dirmv($fileSource,$fileDest,$overWrite,$mode); //recurse!
			rmdir(homeRoot.$fileSource);
		}
	}
	closedir($idnt);
	rmdir(homeRoot.$source);
	return true;
}

function formGenId($formName='default',$create=false){return formGetId($create);}
function formGetId($create=false){
	$q=md5(rand().time());
	$qFix=substr($q, 1 , 9);
	global $_formLastKey;
	$_formLastKey=$id=rand();
	if(!isset($_SESSION['_formId'])||!is_array($_SESSION['_formId'])) $_SESSION['_formId']=array();
	$_SESSION['_formId'][$id]=$qFix;
	$_SESSION['_formId']=array_slice($_SESSION['_formId'],-11,null,true);
	if($create){$_POST['_formId']=array($id=>$q);}
	return '<input type="hidden" name="_formId['.$id.']" value="'.$q.'"/>';
}
function formLastKey(){
	global $_formLastKey;
	return empty($_formLastKey)?'default':$_formLastKey;
}

function formCheckId(){
	if(!isset($_POST['_formId'])||!is_array($_POST['_formId'])) return false;
	reset($_POST['_formId']);
	$id=key($_POST['_formId']);
	$return=(!empty($_POST['_formId'][$id])&&!empty($_SESSION['_formId'][$id])&&$_SESSION['_formId'][$id]==substr($_POST['_formId'][$id], 1 , 9))?true:false;
	unset($_SESSION['_formId'][$id]);
	return $return;
}
function formCheckIdPost(){return formCheckId();}


function getValArrPost($name,$default=''){
	$name=str_replace(array('[','][',']'),array('|','|',''),$name);
	$exp=explode('|',$name);
	$val=$_POST;
	foreach($exp AS $v){
		if(is_array($val)&&isset($val[$v]))
			$val=$val[$v];
		else
			return $default;
	}
	return $val;
}
function getValArrGet($name,$default=''){
	$name=str_replace(array('[','][',']'),array('|','|',''),$name);
	$exp=explode('|',$name);
	$val=$_GET;
	foreach($exp AS $v){
		if(is_array($val)&&isset($val[$v]))
			$val=$val[$v];
		else
			return $default;
	}
	return $val;
}

function getValArrDonor($fieldName,$aDonor=array()){
	$fieldName=str_replace(array('[','][',']'),array('|','|',''),$fieldName);
	$exp=explode('|',$fieldName);
	$expCnt=count($exp)-1;
	for($i=0;$i<$expCnt;$i++){
		if(isset($aDonor[$exp[$i]])){
			if(is_array($aDonor[$exp[$i]])) $aDonor=$aDonor[$exp[$i]];
			else{$aDonor=NULL;break;}
		}
		else{$aDonor=NULL;break;}
	}
	return isset($aDonor[$exp[$expCnt]])?$aDonor[$exp[$expCnt]]:NULL;
}

function getValPost($name='',$default='',$fix=true){
	$return=isset($_POST[$name])?$_POST[$name]:$default;
	return $fix?htmlspecialchars($return):$return;
}
function getValGet($name='',$default='',$fix=true){
	$return=isset($_GET[$name])?$_GET[$name]:$default;
	return $fix?htmlspecialchars($return):$return;
}


function checkChecked($name,$value='on',$default=''){
	return getValArrPost($name,$default)==$value?' checked="checked"':'';
}
function checkSelected($name,$value=null,$default=null){
	return getValArrPost($name,$default)==$value?' selected="selected"':'';
}

function formValidateElement($fieldName,$return=' style="color:red"'){
	global $formValidateNoFill;
	return isset($formValidateNoFill[$fieldName])?$return:NULL;
}

function formValidateElements($fieldArrName,$return=' style="color:red"'){
	global $formValidateNoFill;
	foreach($fieldArrName AS $fieldName){
		if(isset($formValidateNoFill[$fieldName])) return $return;
	}
	return NULL;
}

function ReturnSearchingBot(){
	if(empty($_SERVER['HTTP_USER_AGENT'])) return;
	if((substr($_SERVER['HTTP_USER_AGENT'],0,6)=='Yandex') || (substr($_SERVER['HTTP_USER_AGENT'],0,11)=='YaDirectBot')) return 'yandex';
	else if((strpos($_SERVER['HTTP_USER_AGENT'],'Googlebot')!==false) || (strpos($_SERVER['HTTP_USER_AGENT'],'Mediapartners-Google')!==false) || ( strpos($_SERVER['HTTP_USER_AGENT'],'Google Search Appliance')!==false)) return 'google';
	//else if(substr($_SERVER['HTTP_USER_AGENT'],0,12)=='StackRambler') return 'rambler';
	return;
}
function UserBrowser(){ // определение браузера пользователя (и версии браузера)
	if(array_key_exists('UserUseIs',(array)$_SESSION)) return $_SESSION['UserUseIs'];
	return !ReturnSearchingBot()?$_SESSION['UserUseIs']=true:$_SESSION['UserUseIs']=false;
}


function _getBacktrace($file,$show=FALSE){if(!($show||($_SERVER['_ctrl']&512))) return array();$bt=debug_backtrace(FALSE);do{$c=next($bt);$f=isset($c['file'])?$c['file']:NULL;}while($file==$f);return !isset($c['file'])?$c:array('file'=>$c['file'],'line'=>$c['line']);}
// Returns the proper RFC 822 formatted date.

function genIDNT($len=8,$alph=FALSE){
	if(!$alph) $alph='0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz-_.';
	$alphLen=strlen($alph)-1;
	$str='';
	for(;$len>0;$len--) $str.=substr($alph,rand(0,$alphLen),1);
	return $str;
}
function get_ip(){
    $ip = false;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipa[] = trim(strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ','));

    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipa[] = $_SERVER['HTTP_CLIENT_IP'];       

    if (isset($_SERVER['REMOTE_ADDR']))
        $ipa[] = $_SERVER['REMOTE_ADDR'];

    if (isset($_SERVER['HTTP_X_REAL_IP']))
        $ipa[] = $_SERVER['HTTP_X_REAL_IP'];

    foreach($ipa as $ips){
        if(is_valid_ip($ips)){                    
                $ip = $ips;
                break;
        }
    }
    return $ip;
}

function is_valid_ip($ip=null){
    if(preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip))return true; 

    return false;
}

function find_in_text($text = '',$find='',$charset='utf-8'){
    $cnt_find = mb_strlen($find,$charset);
    $text_mod = convertAccentsAndSpecialToNormal($text);
    
//    $text_mod = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
//    $cnt_text = mb_strlen($text,$charset);
//    $cnt_text_mod = mb_strlen($text_mod,$charset);
    
    if($start = mb_strripos($text_mod,$find,0,$charset)){
        return mb_substr($text, $start, $cnt_find, $charset);
    }else return false;
}
function convertAccentsAndSpecialToNormal($string) {
    $table = array(
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Ă'=>'A', 'Ā'=>'A', 'Ą'=>'A', 'Æ'=>'A', 'Ǽ'=>'A',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'ă'=>'a', 'ā'=>'a', 'ą'=>'a', 'æ'=>'a', 'ǽ'=>'a',
        'Þ'=>'B', 'þ'=>'b', 'ß'=>'Ss',
        'Ç'=>'C', 'Č'=>'C', 'Ć'=>'C', 'Ĉ'=>'C', 'Ċ'=>'C',
        'ç'=>'c', 'č'=>'c', 'ć'=>'c', 'ĉ'=>'c', 'ċ'=>'c',
        'Đ'=>'Dj', 'Ď'=>'D', 'Đ'=>'D',
        'đ'=>'dj', 'ď'=>'d',
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ĕ'=>'E', 'Ē'=>'E', 'Ę'=>'E', 'Ė'=>'E',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'ę'=>'e', 'ė'=>'e',
        'Ĝ'=>'G', 'Ğ'=>'G', 'Ġ'=>'G', 'Ģ'=>'G',
        'ĝ'=>'g', 'ğ'=>'g', 'ġ'=>'g', 'ģ'=>'g',
        'Ĥ'=>'H', 'Ħ'=>'H',
        'ĥ'=>'h', 'ħ'=>'h',
        'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'İ'=>'I', 'Ĩ'=>'I', 'Ī'=>'I', 'Ĭ'=>'I', 'Į'=>'I',
        'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'į'=>'i', 'ĩ'=>'i', 'ī'=>'i', 'ĭ'=>'i', 'ı'=>'i',
        'Ĵ'=>'J',
        'ĵ'=>'j',
        'Ķ'=>'K',
        'ķ'=>'k', 'ĸ'=>'k',
        'Ĺ'=>'L', 'Ļ'=>'L', 'Ľ'=>'L', 'Ŀ'=>'L', 'Ł'=>'L',
        'ĺ'=>'l', 'ļ'=>'l', 'ľ'=>'l', 'ŀ'=>'l', 'ł'=>'l',
        'Ñ'=>'N', 'Ń'=>'N', 'Ň'=>'N', 'Ņ'=>'N', 'Ŋ'=>'N',
        'ñ'=>'n', 'ń'=>'n', 'ň'=>'n', 'ņ'=>'n', 'ŋ'=>'n', 'ŉ'=>'n',
        'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ō'=>'O', 'Ŏ'=>'O', 'Ő'=>'O', 'Œ'=>'O',
        'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ō'=>'o', 'ŏ'=>'o', 'ő'=>'o', 'œ'=>'o', 'ð'=>'o',
        'Ŕ'=>'R', 'Ř'=>'R',
        'ŕ'=>'r', 'ř'=>'r', 'ŗ'=>'r',
        'Š'=>'S', 'Ŝ'=>'S', 'Ś'=>'S', 'Ş'=>'S',
        'š'=>'s', 'ŝ'=>'s', 'ś'=>'s', 'ş'=>'s',
        'Ŧ'=>'T', 'Ţ'=>'T', 'Ť'=>'T',
        'ŧ'=>'t', 'ţ'=>'t', 'ť'=>'t',
        'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ũ'=>'U', 'Ū'=>'U', 'Ŭ'=>'U', 'Ů'=>'U', 'Ű'=>'U', 'Ų'=>'U',
        'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ũ'=>'u', 'ū'=>'u', 'ŭ'=>'u', 'ů'=>'u', 'ű'=>'u', 'ų'=>'u',
        'Ŵ'=>'W', 'Ẁ'=>'W', 'Ẃ'=>'W', 'Ẅ'=>'W',
        'ŵ'=>'w', 'ẁ'=>'w', 'ẃ'=>'w', 'ẅ'=>'w',
        'Ý'=>'Y', 'Ÿ'=>'Y', 'Ŷ'=>'Y',
        'ý'=>'y', 'ÿ'=>'y', 'ŷ'=>'y',
        'Ž'=>'Z', 'Ź'=>'Z', 'Ż'=>'Z', 'Ž'=>'Z',
        'ž'=>'z', 'ź'=>'z', 'ż'=>'z', 'ž'=>'z',
    );

    $string = strtr($string, $table);
    return $string;
}

function change_num($item,$how=true){
    $arr=array();
    $num = 3;
    
    if(is_numeric($item)){
        $item = str_split($item);

        if($how===true){
            foreach ($item as $val){
                $new_val = $val+$num;
                if($new_val>9){
                    $new_val = $new_val%10;
                }

                $arr[] = $new_val;
            }
        }else{
            foreach ($item as $val){
                $new_val = $val-$num;
                if($new_val<0){
                    $new_val = 10+$new_val;
                }
                $arr[] = $new_val;
            }
        }
        
        $out=implode('',$arr);
        
        return (is_numeric($out))?$out:false;
    
    }else return false;
}

function curlUrlIn($url,$data){
  $data = serialize($data);
  $cookie=CurlCookieIn($url);
  
  $agent="Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1";       
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);    
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_COOKIE,$cookie);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.$data);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);    
  $result = curl_exec($ch);   
  $info = curl_getinfo($ch);
  curl_close($ch);
  return $result;  
}

function CurlCookieIn($url)
{
  $cookie='';
  $agent="Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1";   
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
  curl_setopt($ch, CURLOPT_COOKIE,$cookie);   
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
  curl_setopt($ch, CURLOPT_NOBODY, 1);
  $dataHeader = curl_exec($ch);   
  preg_match_all("/Set-Cookie: (.*?)=(.*?);/i",$dataHeader,$res);
  
  foreach ($res[1] as $key => $value) {
   $cookie.= $value.'='.$res[2][$key].'; ';
  };  
  curl_close($ch);
  return $cookie; 
}

function get_curl($url,$param=array()){
    $agent="Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20100101 Firefox/21.0";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    //curl_setopt($ch, CURLOPT_PROXY, "110.172.167.34:8080");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);		
    $data = curl_exec($ch);
    if(curl_errno($ch)) return false;
    
    curl_close($ch);
    return $data;
}

function getContent($url, $referer = null, $proxies = array(null)) {
        $proxies = (!is_null($proxies) && count($proxies)>0)?(array) $proxies:array();
        
        $steps = count($proxies);
        $step = 0;
        $try = true;
        while ($try) {
        // create curl resource
            $ch = curl_init();
            $proxy = isset($proxies[$step]) ? $proxies[$step] . ':7384' : null;

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_REFERER, $referer);
            curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.9.168 Version/11.51");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'ua60210:1Bys2k4kMl');
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

            $output = curl_exec($ch); // get content
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // close curl resource to free up system resources
            curl_close($ch);

            $step++;
            $try = (($step < $steps) && ($http_code != 200));
        }
        file_put_contents(FILESROOT."/log/log_".date('d_m_Y').".txt", $output."\n",FILE_APPEND);
        return $output;
    }


function _redirect($location = '/'){
    header('Location: http://'.DOMAIN_MAIN.$location); exit();
}
function _redirect301($location = '/'){
    header('HTTP/1.1 301 Moved Permanently'); header('Location: http://'.DOMAIN_MAIN.$location); exit();
}
function getmicrotime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

function get_time_diff($time_start) {
    $time = getmicrotime() - $time_start;
    return array('time'=>sprintf('%02d:%02d:%02d', $time / 3600, ($time % 3600) / 60, ($time % 3600) % 60),'ms'=>$time);
}

function cmp($a, $b)
{
    if (strlen($a['name']) == strlen($b['name'])) {
        return 0;
    }
    return (strlen($a['name']) > strlen($b['name'])) ? -1 : 1;
}

function convert_dechex($numstring, $frombase=10,$tobase=16)
{
    $numstring=(string)$numstring;
    $chars = "0123456789abcdefghijklmnopqrstuvwxyz";
    $tostring = substr($chars, 0, $tobase);
 
    $length = strlen($numstring);
    $result = '';
    for ($i = 0; $i < $length; $i++)
    {
        $number[$i] = strpos($chars, $numstring{$i});
    }
    do
    {
        $divide = 0;
        $newlen = 0;
        for ($i = 0; $i < $length; $i++)
        {
            $divide = $divide * $frombase + $number[$i];
            if ($divide >= $tobase)
            {
                $number[$newlen++] = (int)($divide / $tobase);
                $divide = $divide % $tobase;
            } elseif ($newlen > 0)
            {
                $number[$newlen++] = 0;
            }
        }
        $length = $newlen;
        $result = $tostring{$divide} . $result;
    } while ($newlen != 0);
    return $result;
}

function time_notify($time){
    if($time>0) return strftime((date('d')!=date('d',$time)||date('m')!=date('m',$time)?'%d.%m'.(date('Y')!=strftime('%Y',$time)?'.%Y':'').' - ':'Today at ').'%k:%M',$time);
    else return "";
}

?>