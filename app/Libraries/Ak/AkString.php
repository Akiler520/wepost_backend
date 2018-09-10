<?php
namespace App\Libraries\Ak;

/**
 *	string manager
 *
 *	@author     akiler <532171911@qq.com>
 *	@copyright	 Copyright(c) 2010-2020 by akiler. All rights reserved
 *	@version	 1.0
 *	@package	 LIB-Ak
 *
 *	@since      2010-09-29 14:06:12
 */
class AkString
{
	/**
	 * format to print info
	 * $var can be string or array
	 * $isExit means if to exit from the code running, if true, exit.
	 *
	 * @param string|array $var
	 * @param bool $isExit
	 */
	public static function printM($var, $isExit = true) {
		$var = empty($var) ? "NULL" : $var;

		if (is_array($var)) {
			echo "<pre>";
			print_r($var);
			echo "</pre>";
		} elseif (is_string($var)) {
			echo "{$var}<br>\r\n";
		} else {
			var_dump($var);
		}

		if($isExit) {
			exit;
		}
	}

	/**
	 * convert string to array
	 *
	 * @param  string $string
	 *
	 * @return array
	 */
	public static function str2arr($string) {
		$return = array();
		$string = base64_decode($string);
		//        $string = urldecode($string);
		$tempArray = explode('||', $string);
		$nullValue = urlencode(base64_encode("^^^"));
		foreach ($tempArray as $tempValue) {
			list($key,$value) = explode('|', $tempValue);
			$decodedKey = base64_decode(urldecode($key));
			if($value != $nullValue) {
				$returnValue = base64_decode(urldecode($value));
				if(substr($returnValue, 0, 8) == '^^array^')
					$returnValue = AkString::str2arr(substr($returnValue, 8));
				$return[$decodedKey] = $returnValue;
			}else{
				$return[$decodedKey] = null;
			}
		}

		return $return;
	}

	public static function file_str2arr($string) {
		$string = urldecode(base64_decode($string));
		$arr = explode('|', $string);

		return $arr;
	}

	public static function file_arr2str($array, $md5 = true) {
		$string = implode('|', $array);
		$string = base64_encode(urlencode($string));

		$string = $md5 ? md5($string) : $string;

		return $string;
	}

	/**
	 * convert array to string
	 *
	 * @param  array $array
	 *
	 * @return string
	 */
	public static function arr2str($array) {
		$return = '';
		$nullValue="^^^";
		foreach ($array as $key => $value) {
			if(is_array($value))
				$returnValue = '^^array^'.AkString::arr2str($value);
			else
				$returnValue = (strlen($value) > 0) ? $value : $nullValue;
			$return .= urlencode(base64_encode($key)) . '|' . urlencode(base64_encode($returnValue)) . '||';
		}

		//        return urlencode(substr($return, 0, -2));
		return base64_encode(substr($return, 0, -2));
	}

	/**
	 * add slashes into all $var string, include array
	 *
	 * @param  string|array $var
	 *
	 * @return string|array
	 */
	public static function addslashes_deep($var) {
		if(is_string($var)) {
			$var = addslashes($var);
		} elseif(is_array($var)) {
			foreach($var as $k => $v) {
				$var[$k] = self::addslashes_deep($v);
			}
		}
		return $var;
	}

	/**
	 * strip slashes of $var, include array
	 *
	 * @param  string|array $var
	 *
	 * @return string|array
	 */
	public static function stripslashes_deep($var) {
		if(is_string($var)) {
			$var = stripslashes($var);
		} elseif(is_array($var)) {
			foreach($var as $k => $v) {
				$var[$k] = self::stripslashes_deep($v);
			}
		}
		return $var;
	}

	/**
	 * get the real length of string, include chinese character
	 *
	 * @param string $str
	 * @param string $charset
	 *
	 * @return integer
	 */
	public static function strlen_real($str, $charset="UTF-8"){
		if (!$str) {
			return 0;
		}
		$len = 0;
		$cnt = mb_strlen($str, $charset);
		for ($i = 0; $i < $cnt; $i++) {
			$char = mb_substr($str, $i, 1, $charset);
			if (ord($char) < 128) {
				$len += 1;
			} else {
				$len += 2;
			}
		}
		return $len;
	}

	/**
	 * convert array to equation
	 *
	 * @param array $array		data
	 * @param string $symbol	to connect the equation
	 *
	 * @return string
	 */
	public static function arr2equation($array, $symbol = ',') {
		if(empty($array) || !is_array($array)) return false;

		$i = 0;
		$count = count($array);
		$equation = '';
		foreach ($array as $key => $value) {
			if ($count == $i) {
				$equation .= $key . '=' . $value;
			} else {
				$equation .= $key . '=' . $value . $symbol;
			}

			$i++;
		}

		return trim($equation, $symbol);
	}

	/**
	 * get the repeat elements in the array
	 *
	 * @param $array
	 * @return array
	 */
	public static function arrayGetRepeat($array) {
		// remove repeated element
		$unique_arr = array_unique ( $array );

		// get repeated element array
		$repeat_arr = array_diff_assoc ( $array, $unique_arr );

		return $repeat_arr;
	}

	/**
	 * encrypt the string by key
	 * when you decipher the string, must use the key
	 *
	 * @param string $txt
	 * @param string $key
	 * @return string
	 */
	public static function passport_encrypt($txt, $key) {
		srand((double)microtime() * 1000000);
		$encrypt_key = md5(rand(0, 32000));
		$ctr = 0;
		$tmp = '';

		for($i = 0;$i < strlen($txt); $i++) {
			$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
			$tmp .= $encrypt_key[$ctr].($txt[$i] ^ $encrypt_key[$ctr++]);
		}

		return base64_encode(self::passport_key($tmp, $key));
	}

	/**
	 * decipher the string by key
	 *
	 * @param string $txt
	 * @param string $key
	 * @return string
	 */
	public static function passport_decrypt($txt, $key) {
		$txt = self::passport_key(base64_decode($txt), $key);
		$tmp = '';

		for($i = 0;$i < strlen($txt); $i++) {
			$md5 = $txt[$i];
			$tmp .= $txt[++$i] ^ $md5;
		}

		return $tmp;
	}

	/**
	 * encypt the key into a string
	 *
	 * @param string $txt
	 * @param string $encrypt_key
	 * @return string
	 */
	public static function passport_key($txt, $encrypt_key) {
		$encrypt_key = md5($encrypt_key);
		$ctr = 0;
		$tmp = '';

		for($i = 0; $i < strlen($txt); $i++) {
			$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
			$tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
		}

		return $tmp;
	}

	/**
	 * get the time string
	 * include Micro time
	 *
	 * @return string
	 */
	public static function getMicroString() {
		$mtime = explode(' ',microtime());
		$utime = explode('.', $mtime[0]);
		$result = $mtime[1].$utime[1];

		return $result;
	}

	/**
	 * get current time
	 * include second and micro time
	 *
	 * @return float
	 */
	public static function getMicroTime() {
		$mtime = explode(' ',microtime());

		$time = $mtime[1]+$mtime[0];

		return $time;
	}

	/**
	 * format datetime string to a time string
	 * the $datetime must be a correct date time format, or error will happen.
	 *
	 * e.g. dateString2time('09/11/2013 13:13:39') => 1381468419
	 *
	 * @param string $datetime
	 *
	 * @return string|integer
	 */
	public static function dateString2time($datetime) {
		$datetime = new DateTime($datetime);

		$time = date_timestamp_get($datetime);

		return $time;
	}

	/**
	 * format a datetime string by $format
	 *
	 * @param string $datetime	e.g. 20120403
	 * @param string $format
	 *
	 * @return datetime|date
	 */
	public static function dateFormat($datetime, $format = 'Y-m-d H:i:s') {
		$datetime = new DateTime($datetime);

		$datetime = date_format($datetime, $format);

		return $datetime;
	}

	/**
	 * get client time by offset, because there is a difference between client and server on the time
	 *
	 * @param datetime $datatime	'2014-06-28 12:12:12'
	 * @param integer $timeoffset	second
	 * @param string $format	default 'Y-m-d H:i:s'
	 * @return datetime
	 */
	public static function getClientDate($datatime, $timeoffset, $format = 'Y-m-d H:i:s') {
		$gwt = gmdate('Y-m-d H:i:s', AkString::dateString2time($datatime));
		$ret = date($format, AkString::dateString2time($gwt)-$timeoffset);

		return $ret;
	}

	/**
	 * Returns the offset from the origin timezone to the remote timezone, in seconds.
	 * @param string $remote_tz: default='GMT'(Greenwich Mean Time ),
	 * @param string $origin_tz: If null the servers current timezone is used as the origin.
	 * @return integer
	 */
	public static function get_timezone_offset($remote_tz='GMT', $origin_tz = null) {
		if($origin_tz === null) {
			if(!is_string($origin_tz = date_default_timezone_get())) {
				return false; // A UTC timestamp was returned -- bail out!
			}
		}

		$origin_dtz = new DateTimeZone($origin_tz);
		$remote_dtz = new DateTimeZone($remote_tz);

		$origin_dt = new DateTime("now", $origin_dtz);
		$remote_dt = new DateTime("now", $remote_dtz);

		$offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);

		return $offset;
	}

	/**
	 * get date range
	 *
	 * @param int $type
	 * @param string $year
	 * @param string $month
	 * @param string $format
	 * @return array = array('from' => '2014-11-01 00:00:00', 'to' => '2014-11-30 12:59:59)
	 */
	public static function getDateRange($type = 1, $year = '', $month = '', $format = 'Y-m-d H:i:s'){
		$range = array(
				'from'  => '',
				'to'    => ''
		);

		switch($type){
			case 1:
				// by year
				$year = (int)(empty($year) ? date('Y') : $year);

				$range['from']  = date($format, mktime(0, 0, 0, 1, 1, $year));
				$range['to']    = date($format, mktime(12, 59, 59, 1, 0, $year+1));
				break;
			case 2:
				// by month
				$year = (int)(empty($year) ? date('Y') : $year);
				$month = (int)(empty($month) ? date('m') : $month);

				$range['from']  = date($format, mktime(0, 0, 0, $month, 1, $year));
				$range['to']    = date($format, mktime(12, 59, 59, $month+1, 0, $year));
				break;
			case 3:
				// by week, from Monday to Sunday
				$year = (int)(empty($year) ? date('Y') : $year);
				$month = (int)(empty($month) ? date('m') : $month);
				$curWeekNumber = date('w');
				$curDate = date('d');

				$range['from']  = date($format, mktime(0, 0, 0, $month, $curDate-$curWeekNumber+1, $year));
				$range['to']    = date($format, mktime(12, 59, 59, $month, $curDate+(7-$curWeekNumber), $year));
				break;
			case 4:
				// by day
				$year = (int)(empty($year) ? date('Y') : $year);
				$month = (int)(empty($month) ? date('m') : $month);
				$curDate = date('d');

				$range['from']  = date($format, mktime(0, 0, 0, $month, $curDate, $year));
				$range['to']    = date($format, mktime(12, 59, 59, $month, $curDate+1, $year));
				break;
			default:
				break;
		}

		return $range;
	}

	/**
	 * strip the char can not include in disk file name.
	 *
	 * @param string $name
	 * @return bool | string
	 */
	public static function stripDiskFileName($name) {
		if ($name == '') {
			return false;
		}

		$name = str_replace(':',' ', $name);
		$name = str_replace('/',' ', $name);
		$name = str_replace('\\',' ', $name);
		$name = str_replace('<',' ', $name);
		$name = str_replace('>',' ', $name);
		$name = str_replace('*',' ', $name);
		$name = str_replace('?',' ', $name);
		$name = str_replace('|',' ', $name);
		$name = str_replace('"',' ', $name);

		// filter the line break characters
		$name = str_replace("\r\n","", $name);
		$name = str_replace("\r","", $name);
		$name = str_replace("\n","", $name);

		$name = trim($name, ' ');

		if (strlen($name) <= 0 || $name == NULL) {
			return false;
		}

		return $name;
	}

	/**
	 * change charset to utf-8(default)
	 *
	 * @param string $source        the string will be changed
	 * @param string $to_charset    out charset
	 *
	 * @return string
	 */
	public static function changeCharset($source, $to_charset = 'UTF-8')
	{
		// detect the charset of source string, don't use 'auto', it can not work well.
		$encoding = mb_detect_encoding($source, array('GB2312','GBK','UTF-8','BIG5','UTF-16','UCS-2','ASCII', 'ISO-8859-1'));

		// convert
		if ($encoding != false && strtolower($encoding) != strtolower($to_charset)) {
			$source = iconv($encoding, $to_charset, $source);		// iconv is more effective.
		}

		return $source;
	}

	public static function str_coder($str,$_type='chat' )
	{
		if ( $_type == 'chat' )
		{
			$bian=@mb_detect_encoding($str,"EUC-CN,EUC-TW,GB2312,BIG5,UTF-8,SJIS,SHIFT-JIS,EUC-JP,ISO-8859-1");
		}elseif( $_type == 'mail' )
		{
			$bian=@mb_detect_encoding($str,"AUTO");
		}
		//AkString::printm($bian);
		if( count(explode('BIG-5',$bian))>1 || $bian =='BIG-5' ){
			$str= @mb_convert_encoding($str,"UTF-8","GBK,BIG-5,GB2312,UTF-8,ISO-8859-1");
		}
		elseif(count(explode('SJIS',$bian))>1 || $bian=='SJIS'){
			$str= @mb_convert_encoding($str,"UTF-8","UTF-8,SJIS");
		}
		elseif(count(explode('EUC-JP',$bian))>1 || $bian=='EUC-JP'){
			$str= @mb_convert_encoding($str,"UTF-8","EUC-JP,GB2312,BIG5,UTF-8,EUC-JP");
		}
		elseif(count(explode('EUC-TW',$bian))>1 || $bian=='EUC-TW'){
			$str= @mb_convert_encoding($str,"UTF-8","UTF-8,EUC-TW");
		}
		elseif(count(explode('EUC-CN',$bian))>1 || $bian=='EUC-CN'){
			$str= @mb_convert_encoding($str,"UTF-8","ISO-8859-1,GBK,EUC-CN,GB2312,BIG5,UTF-8");
		}
		elseif( count(explode('GBK',$bian))>1 || $bian=='UTF-8' ){
			$str= @mb_convert_encoding($str,"UTF-8","UTF-8,GB2312,BIG5");
		}
		else{
		}

		return $str;
	}

	/**
	 * get the encode of file
	 *
	 * @param string $filename		path to the file
	 * @return encode||false
	 */
	public static function getFileEncode($filename)
	{
		define('UTF32_BIG_ENDIAN_BOM', chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
		define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
		define('UTF16_BIG_ENDIAN_BOM', chr(0xFE) . chr(0xFF));
		define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
		define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));

		$text = file_get_contents($filename);

		$first2 = substr($text, 0, 2);
		$first3 = substr($text, 0, 3);
		$first4 = substr($text, 0, 3);

		if ($first3 == UTF8_BOM)
			return 'UTF-8 BOM';
		elseif ($first4 == UTF32_BIG_ENDIAN_BOM)
			return 'UTF-32BE';
		elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM)
			return 'UTF-32LE';
		elseif ($first2 == UTF16_BIG_ENDIAN_BOM)
			return 'UTF-16BE';
		elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM)
			return 'UTF-16LE';

		if ($text === iconv('UTF-8', 'UTF-8', iconv('UTF-8', 'UTF-8', $text)))
			return 'UTF-8';
		if ($text === iconv('UTF-8', 'ASCII', iconv('ASCII', 'UTF-8', $text)))
			return 'ASCII';
		if ($text === iconv('UTF-8', 'GB2312', iconv('GB2312', 'UTF-8', $text)))
			return 'GB2312';

		return false;
	}

	/**
	 * change html to text string, filter the html tags
	 * @param $str
	 * @param string $encode
	 * @return mixed
	 */
	public static function html2text($str, $encode = 'GB2312')
	{
		$str = preg_replace("/<style .*?<\/style>/is", "", $str);
		$str = preg_replace("/<script .*?<\/script>/is", "", $str);
		$str = preg_replace("/<br s*\/?\/>/i", "n", $str);
		$str = preg_replace("/<\/?p>/i", "nn", $str);
		$str = preg_replace("/<\/?td>/i", "n", $str);
		$str = preg_replace("/<\/?div>/i", "n", $str);
		$str = preg_replace("/<\/?blockquote>/i", "n", $str);
		$str = preg_replace("/<\/?li>/i", "n", $str);

		$str = preg_replace("/&nbsp;/i", " ", $str);
		$str = preg_replace("/&nbsp/i", " ", $str);

		$str = preg_replace("/&amp;/i", "&", $str);
		$str = preg_replace("/&amp/i", "&", $str);

		$str = preg_replace("/&lt;/i", "<", $str);
		$str = preg_replace("/&lt/i", "<", $str);

		$str = preg_replace("/&ldquo;/i", '"', $str);
		$str = preg_replace("/&ldquo/i", '"', $str);

		$str = preg_replace("/&lsquo;/i", "'", $str);
		$str = preg_replace("/&lsquo/i", "'", $str);

		$str = preg_replace("/&rsquo;/i", "'", $str);
		$str = preg_replace("/&rsquo/i", "'", $str);

		$str = preg_replace("/&gt;/i", ">", $str);
		$str = preg_replace("/&gt/i", ">", $str);

		$str = preg_replace("/&rdquo;/i", '"', $str);
		$str = preg_replace("/&rdquo/i", '"', $str);

		$str = strip_tags($str);
		$str = html_entity_decode($str, ENT_QUOTES, $encode);
		$str = preg_replace("/&#.*?;/i", "", $str);

		return $str;
	}

	/**
	 * get special characters of German
	 *
	 * @param string $string
	 * @return string
	 */
	public static function getGermanStr($string, $both = true) {
		if ($string == '' || strlen($string) <= 0) {
			return null;
		}

		$var = $string;

		$german_sp = array('ä', 'ß', 'ö', 'ü');

		foreach ($german_sp as $german) {
			if (stripos($string, $german) !== false) {
				$isGerman = true;
			}
		}

		if ($isGerman) {
			// case-insensitive
			$var = str_ireplace("ü","ue",$var);
			$var = str_ireplace("ä","ae",$var);
			$var = str_ireplace("ö","oe",$var);
			$var = str_ireplace("ß","ss",$var);
		} else if($both) {
			// case-insensitive
			$var = str_ireplace("ue","ü",$var);
			$var = str_ireplace("ae","ä",$var);
			$var = str_ireplace("oe","ö",$var);
			$var = str_ireplace("ss","ß",$var);
		}

		return $var;
	}

	/**
	 * decode the German characters
	 *
	 * @param string $info
	 * @return string|null
	 */
	public static function German_decode($info) {
		if ($info == '' || strlen($info) <= 0) {
			return null;
		}

		$var = $info;
		/*$var = str_replace("Ã¼","&uuml;",$var);
		$var = str_replace("Ãœ","&Uuml;",$var);
		$var = str_replace("Ã¶","&ouml;",$var);
		$var = str_replace("Ã–","&Ouml;",$var);
		$var = str_replace("Ã¤","&auml;",$var);
		$var = str_replace("Ã„","&Auml;",$var);
		$var = str_replace("ÃŸ","&suml;",$var);*/

		$var = str_replace("Ã¼","ü",$var);
		$var = str_replace("Ãœ","Ü",$var);
		$var = str_replace("Ã¶","ö",$var);
		$var = str_replace("Ã–","Ö",$var);
		$var = str_replace("Ã¤","ä",$var);
		$var = str_replace("Ã„","Ä",$var);
		$var = str_replace("ÃŸ","ß",$var);
		$var = str_replace("Ã","ß",$var);


		/*
		UPDATE dms_file SET `name`=REPLACE(`name`,'Ã¼','ü');
		UPDATE dms_file SET `name`=REPLACE(`name`,'Ãœ','Ü');
		UPDATE dms_file SET `name`=REPLACE(`name`,'Ã¶','ö');
		UPDATE dms_file SET `name`=REPLACE(`name`,'Ã–','Ö');
		UPDATE dms_file SET `name`=REPLACE(`name`,'Ã¤','ä');
		UPDATE dms_file SET `name`=REPLACE(`name`,'Ã„','Ä');
		UPDATE dms_file SET `name`=REPLACE(`name`,'ÃŸ','ß');

		UPDATE dms_folder SET `name`=REPLACE(`name`,'Ã¼','ü');
		UPDATE dms_folder SET `name`=REPLACE(`name`,'Ãœ','Ü');
		UPDATE dms_folder SET `name`=REPLACE(`name`,'Ã¶','ö');
		UPDATE dms_folder SET `name`=REPLACE(`name`,'Ã–','Ö');
		UPDATE dms_folder SET `name`=REPLACE(`name`,'Ã¤','ä');
		UPDATE dms_folder SET `name`=REPLACE(`name`,'Ã„','Ä');
		UPDATE dms_folder SET `name`=REPLACE(`name`,'ÃŸ','ß');

		*/


		$info = $var;

		return $info;
	}

	/**
	 * chinese cut, support gb2312,gbk,utf-8,big5
	 *
	 * @param string $str 	source string
	 * @param int $start 	start position
	 * @param int $length 	cut length
	 * @param string $charset 	utf-8|gb2312|gbk|big5
	 * @param $suffix
	 *
	 * @return string
	 */
	public static function csubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = '...')
	{
		if(function_exists("mb_substr"))
		{
			if(mb_strlen($str, $charset) <= $length) {
				return $str;
			}
			$slice = mb_substr($str, $start, $length, $charset);
		}
		else
		{
			$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			$re['gbk']          = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			$re['big5']          = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

			preg_match_all($re[$charset], $str, $match);

			if(count($match[0]) <= $length) {
				return $str;
			}

			$slice = join("",array_slice($match[0], $start, $length));
		}

		$slice .= $suffix;

		return $slice;
	}

	/**
	 * multi explode
	 *
	 * @param array $delimiters
	 * @param string $string
	 *
	 * @return array
	 */
	public static function multiexplode ($delimiters = array(), $string)
	{
		$ready = str_replace($delimiters, $delimiters[0], $string);
		$launch = explode($delimiters[0], $ready);

		return  $launch;
	}

	/**
	 * to improve the function in_array of PHP
	 *
	 * @param string $needle
	 * @param array $haystack
	 *
	 * @return bool
	 */
	public static function in_array_imp($needle, $haystack = array()) {
		if (empty($needle) || empty($haystack) || !is_array($haystack)) {
			return false;
		}

		foreach ($haystack as $stack) {
			$new_haystack[strtolower($stack)] = 1;
		}

		if (isset($new_haystack[strtolower($needle)])) {
			return true;
		}

		return false;
	}

	/**
	 * check if a string include number
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function hasNumber($string) {
		if (empty($string)) {
			return false;
		}
		if (is_numeric($string)) {
			return true;
		}

		$pattern = '/\d/';

		$ret = preg_match($pattern, $string);

		return ($ret==1) ? true : false;
	}

	/**
	 * check the word case
	 * return array include upper and lower number
	 *
	 * @param string $word
	 *
	 * @return array
	 */
	public static function checkWordCase($word) {
		if (empty($word)) {
			return false;
		}

		$len = strlen($word);
		$upper = 0;
		$lower = 0;

		for ($i = 0; $i < $len; $i++) {
			$char_ord = ord(substr($word, $i, 1));
			if ($char_ord > 64 && $char_ord < 91) {
				$upper++;
			}
			if ($char_ord > 96 && $char_ord < 123) {
				$lower++;
			}
		}

		return array('upper'=>$upper, 'lower'=>$lower);
	}

	/*
	* 加密，可逆
	* 可接受任何字符
	* 安全度非常高
	*/
	public static function encrypt($txt, $key = 'akiler')
	{
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
		$ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
		$nh1 = rand(0,64);
		$nh2 = rand(0,64);
		$nh3 = rand(0,64);
		$ch1 = $chars{$nh1};
		$ch2 = $chars{$nh2};
		$ch3 = $chars{$nh3};
		$nhnum = $nh1 + $nh2 + $nh3;
		$knum = 0;$i = 0;
		while(isset($key{$i})) $knum +=ord($key{$i++});
		$mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum%8,$knum%8 + 16);
		$txt = base64_encode($txt);
		$txt = str_replace(array('+','/','='),array('-','_','.'),$txt);
		$tmp = '';
		$j=0;$k = 0;
		$tlen = strlen($txt);
		$klen = strlen($mdKey);
		for ($i=0; $i<$tlen; $i++) {
			$k = $k == $klen ? 0 : $k;
			$j = ($nhnum+strpos($chars,$txt{$i})+ord($mdKey{$k++}))%64;
			$tmp .= $chars{$j};
		}
		$tmplen = strlen($tmp);
		$tmp = substr_replace($tmp,$ch3,$nh2 % ++$tmplen,0);
		$tmp = substr_replace($tmp,$ch2,$nh1 % ++$tmplen,0);
		$tmp = substr_replace($tmp,$ch1,$knum % ++$tmplen,0);
		return $tmp;
	}

	/*
	* 解密
	*
	*/
	public static function decrypt($txt, $key = 'akiler')
	{
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
		$ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
		$knum = 0;$i = 0;
		$tlen = strlen($txt);
		while(isset($key{$i})) $knum +=ord($key{$i++});
		$ch1 = $txt{$knum % $tlen};
		$nh1 = strpos($chars,$ch1);
		$txt = substr_replace($txt,'',$knum % $tlen--,1);
		$ch2 = $txt{$nh1 % $tlen};
		$nh2 = strpos($chars,$ch2);
		$txt = substr_replace($txt,'',$nh1 % $tlen--,1);
		$ch3 = $txt{$nh2 % $tlen};
		$nh3 = strpos($chars,$ch3);
		$txt = substr_replace($txt,'',$nh2 % $tlen--,1);
		$nhnum = $nh1 + $nh2 + $nh3;
		$mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum % 8,$knum % 8 + 16);
		$tmp = '';
		$j=0; $k = 0;
		$tlen = strlen($txt);
		$klen = strlen($mdKey);
		for ($i=0; $i<$tlen; $i++) {
			$k = $k == $klen ? 0 : $k;
			$j = strpos($chars,$txt{$i})-$nhnum - ord($mdKey{$k++});
			while ($j<0) $j+=64;
			$tmp .= $chars{$j};
		}
		$tmp = str_replace(array('-','_','.'),array('+','/','='),$tmp);
		return base64_decode($tmp);
	}

	/**
	 * check the $source, if include $str_check
	 *
	 * @param string|array $source
	 * @param string $str_check
	 *
	 * @return bool
	 */
	public static function isInclude($source, $str_check)
	{
		if (is_array($source)) {
			if (!empty($source)) {
				foreach ($source as $val) {
					if (stripos($val, $str_check) !== false) {
						$find = true;
						break;
					}
				}
			} else {
				$find = false;
			}
		} else {
			if (stripos($source, $str_check) !== false) {
				$find = true;
			} else {
				$find = false;
			}
		}

		return $find;
	}

	/**
	 * get needles position by array input
	 *
	 * input multi needles by array
	 *
	 * @param string $haystack
	 * @param array $needles
	 * @param integer $offset
	 *
	 * @return array
	 */
	public static function multineedle_stripos($haystack, $needles=array(), $offset=0) {
		foreach($needles as $needle) {
			$found[$needle] = stripos($haystack, $needle, $offset);
		}
		return $found;
	}

	/**
	 * size_format modifier plugin
	 *
	 * changed in 2013-01-19
	 *
	 * @param string $string
	 * @param integer $decimal	the number of the end
	 * @param string $toFormat	can be 'B, KB, MB, GB, TB'
	 *
	 * @return string
	 */
	public static function size_format($string, $decimal = 2, $toFormat = 'auto'){
		if ($string == '' || !is_numeric($string)) {
			return false;
		}

		if ($decimal > 6 || $decimal < 0) {
			return $string.'B';
		}

		$size = $string;
		$unit = 'B';

		if ($toFormat == 'auto') {
			if ($size < 1024) {
				$size = $size;
				$unit = 'B';
			} elseif ($size < (1024*1024)) {
				$size = $size/1024;
				$unit = 'KB';
			} elseif ($size < (1024*1024*1024)) {
				$size = $size/1024/1024;
				$unit = 'MB';
			} elseif ($size < (1024*1024*1024*1024)) {
				$size = $size/1024/1024/1024;
				$unit = 'GB';
			} else {
				$size = $size/1024/1024/1024/1024;
				$unit = 'TB';
			}
		} else {
			switch (strtoupper($toFormat)) {
				case 'B':
					$size = $size;
					$unit = 'B';
				case 'KB':
					$size = $size/1024;
					$unit = 'KB';
					break;
				case 'MB':
					$size = $size/1024/1024;
					$unit = 'MB';
					break;
				case 'GB':
					$size = $size/1024/1024/1024;
					$unit = 'GB';
					break;
				case 'TB':
					$size = $size/1024/1024/1024/1024;
					$unit = 'GB';
					break;
				default:
					$size = $size/1024;
					$unit = 'KB';
					break;
			}
		}

		if (($pos = stripos($size, '.')) !== false) {
			$offset = $decimal+1;
			$ceil = substr($size, $pos+$offset, 1);
			$tmp = substr($size, 0, $pos+$offset);

			if ($ceil != '' && $ceil >= 5) {
				$tmp = $tmp+(1/pow(10, $decimal));
			}

			$size = $tmp;
		}

		$size = $size.$unit;

		return $size;
	}

	/**
	 * check if the two files are the same one
	 * pass two file names and path
	 *
	 * @param $fn1
	 * @param $fn2
	 * @return bool - TRUE if files are the same, FALSE otherwise
	 */
	public static function file_identical($fn1, $fn2) {
		define('FILE_READ_LEN', 4096);

		if(filetype($fn1) !== filetype($fn2))
			return FALSE;

		if(filesize($fn1) !== filesize($fn2))
			return FALSE;

		if(!$fp1 = fopen($fn1, 'rb'))
			return FALSE;

		if(!$fp2 = fopen($fn2, 'rb')) {
			fclose($fp1);
			return FALSE;
		}

		$same = TRUE;
		while (!feof($fp1) and !feof($fp2)) {
			if(fread($fp1, FILE_READ_LEN) !== fread($fp2, FILE_READ_LEN)) {
				$same = FALSE;
				break;
			}
		}

		if(feof($fp1) !== feof($fp2)) {
			$same = FALSE;
		}

		fclose($fp1);
		fclose($fp2);

		return $same;
	}

	public static function file_identical_md5($fn1, $fn2) {

	}

	/**
	 * get IP address of client
	 * @return string
	 */
	public static function getClientIP(){
		if ($_SERVER["HTTP_X_FORWARDED_FOR"])
		{
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
		elseif ($_SERVER["HTTP_CLIENT_IP"])
		{
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}
		elseif ($_SERVER["HTTP_X_REAL_IP"])
		{
			$ip = $_SERVER["HTTP_X_REAL_IP"];
		}
		elseif ($_SERVER["HTTP_REMOTE_HOST"])
		{
			$ip = $_SERVER["HTTP_REMOTE_HOST"];
		}
		elseif ($_SERVER["REMOTE_ADDR"])
		{
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		elseif (getenv("HTTP_X_FORWARDED_FOR"))
		{
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		}
		elseif (getenv("HTTP_CLIENT_IP"))
		{
			$ip = getenv("HTTP_CLIENT_IP");
		}
		elseif (getenv("REMOTE_ADDR"))
		{
			$ip = getenv("REMOTE_ADDR");
		}
		else
		{
			$ip = "Unknown";
		}

		return $ip;
	}

	/**
	 * get a string by rand, and not to be duplicated
	 * @param int $limit
	 * @return string
	 */
	public static function getRand($limit = 6){
		$time = self::getMicroString();

		$time_md5 = md5($time);

		return substr($time_md5, -$limit);
	}

	/**
	 * change camel to be underline
	 * e.g.: AkString -> ak_string
	 *
	 * @param $name
	 * @return string
	 */
	public static function camel2underline($name){
		$temp_array = array();
		for($i=0;$i<strlen($name);$i++){
			$ascii_code = ord($name[$i]);
			if($ascii_code >= 65 && $ascii_code <= 90){
				if($i == 0){
					$temp_array[] = chr($ascii_code + 32);
				}else{
					$temp_array[] = '_'.chr($ascii_code + 32);
				}
			}else{
				$temp_array[] = $name[$i];
			}
		}
		return implode('',$temp_array);
	}

	/**
	 * change charset to be UTF-8
	 * @param $source
	 * @return string
	 */
	public static function charsetToUTF8($source){
		if (mb_detect_encoding($source, 'UTF-8', true) === false) {
			$source = utf8_encode($source);
		}

		return $source;
	}

	/**
	 * get comments of function
	 * @param $module
	 * @param $function
	 * @return string
	 */
	public static function getFunctionComment($module,$function){
		$act   = new $module();
		$func  = new ReflectionMethod($act, $function);
		$tmp   = $func->getDocComment();

		$flag  = preg_match_all('/@func(.*?)\n/', $tmp, $tmp);
		$tmp   = trim($tmp[1][0]);
		$tmp   = $tmp != '' ? $tmp : 'null';

		return $tmp;
	}

	/**
	 * get action list of pointed class
	 * @param $module
	 * @param $module_ignore
	 * @return array
	 */
	public static function getActionList($module, $module_ignore = "ControllerBase"){
		$base = get_class_methods($module_ignore);
		$curr = get_class_methods($module);

		$funcArr = array();

		if(empty($curr)){
			return $funcArr;
		}

		foreach($curr AS $val_curr){
			if(!in_array($val_curr, $base)){
				$action = substr($val_curr, -6);

				if($action === "Action"){
					$funcArr[] = $val_curr;
				}
			}
		}

		return $funcArr;
	}

	/**
	 * check if the request is Ajax
	 * @return bool
	 */
	public static function isAjax() {
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
			if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
				return true;
		}

		return false;
	}

	/**
	 * return json data format
	 *
	 * @param int $code
	 * @param string $message
	 * @param array $data
	 * @param bool|true $exit
	 */
	public static function jsonReturn($code = 1, $message = '成功', $data = array(), $exit = true){
		$result = array(
				'status_code'   => $code,
				'status_message'=> $message,
				'rows'          => $data,
				'total'			=> count($data)
		);

		echo(json_encode($result));
		if($exit) exit;
	}

	/**
	 * 创建优惠码
	 * 不会重复
	 * @param int $length
	 * @param string $codePre
	 * @return string
	 */
	public static function createCoupons($length = 10, $codePre = ""){
		$codeArr = array();

		for ($i = 0; $i < ($length- strlen($codePre)); $i ++) {
			$type = mt_rand(1, 2);

			if($i == 0 || $type == 1){//字母，保证第一个是字母
				while (true) {
					$ascii = mt_rand(65, 90);
					if ($ascii != 79 && $ascii != 73) {
						// 排除O字母和I
						break;
					}
				}
				$codeArr[] = chr($ascii);
			}else{//数字
				$num = mt_rand(2, 9);
				$codeArr[] = $num;
			}
		}

		$code = implode('', $codeArr);

		strlen($codePre) > 0 && $code = $codePre.$code;

		return $code;
	}
}