<?php
/**
 *	word class for words spell check
 *
 *  @author     akiler <532171911@qq.com>
 *	@copyright	 Copyright(c) 2010-2020 by akiler. All rights reserved
 *	@version    1.0
 *	@package    LIB-Ak
 *
 *	@since      2013-09-11
 */
class Ak_Word
{
	private $_Word_File = array('en.txt');
	
	function __construct() {
		
	}
	
	/**
	 * set the words file
	 *
	 * @param array $filePath
	 */
	function setWordFile($filePath = array()) {
		$this->_Word_File = $filePath;
	}
	
	/**
	 * get word list by word file
	 *
	 * @param string $filePath	path to file
	 * 
	 * @return array|bool
	 */
	function getFuncWordListByFile() {
		if (empty($this->_Word_File)) {
			return false;
		}
		
		$list = array();

		foreach ($this->_Word_File as $file) {
			if (!is_file($file)) {
				continue;
			}
			
			if (!($fp = fopen($file, 'r'))) {
				continue;
			}
			
			while (!feof($fp)) {
				$buf = fgets($fp, 1024);
				$list[] = strtolower(trim($buf, " \r\n"));
			}
			
			fclose($fp);
		}
		
		return $list;
	}
	
	/**
	 * words spell check by word file
	 *
	 * @param string $words
	 * 
	 * @return array
	 */
	function checkWordsByFile($words) {
		$words_arr = $this->getWordsList($words);
		$func_w_arr = $this->getFuncWordListByFile();
		$func_w_arr_n = array();
		$missed = 0;
		$matched = 0;
		$missed_arr = array();
		
		foreach ($func_w_arr as $stack) {
			$func_w_arr_n[strtolower($stack)] = 1;
		}

		foreach ($words_arr as $word) {
			if (empty($word)) {
				continue;
			}
			
			if (is_numeric($word)) {
				$matched++;
				continue;
			}
			
			if (strlen($word) <= 1) {
				$matched++;
				continue;
			}
			
			if (AkString::hasNumber($word)) {
				$matched++;
				continue;
			}
			
			if (filter_var($word, FILTER_VALIDATE_EMAIL)) {
				$matched++;
				continue;
			}
			
			$wordCase = AkString::checkWordCase($word);
			if ($wordCase['upper'] == strlen($word)) {	// if all chars are upper, just match it.
				$matched++;
				continue;
			}
			
			$word = AkString::getGermanStr($word, false);
			
			if (isset($func_w_arr_n[strtolower($word)])) {
				$matched++;
			} else {
				$missed++;
				$missed_arr[] = $word;
			}
		}
		
		$perst = number_format($missed/($missed+$matched), 4);
		$ret = array(
			'matched'	=> $matched,
			'missed' 	=> $missed, 
			'missed percentage'	=> $perst,
			'query'			=> 0,
			'missed words'	=> $missed_arr
		);
		
		return $ret;
	}
	
	/**
	 * words spell check by DB data
	 *
	 * @param string $words
	 * 
	 * @return array
	 */
	function checkWordsByDB($words) {
		$words_arr = $this->getWordsList($words);
		$missed = 0;
		$matched = 0;
		$query = 0;
		$missed_arr = array();
		$searched = array();

		foreach ($words_arr as $word) {
			if (empty($word)) {
				continue;
			}
			
			if (is_numeric($word)) {
				$matched++;
				continue;
			}
			
			if (strlen($word) <= 1) {
				$matched++;
				continue;
			}
			
			if (AkString::hasNumber($word)) {
				$matched++;
				continue;
			}
			
			if (filter_var($word, FILTER_VALIDATE_EMAIL)) {
				$matched++;
				continue;
			}
			
			$wordCase = AkString::checkWordCase($word);
			if ($wordCase['upper'] == strlen($word)) {	// if all chars are upper, just match it.
				$matched++;
				continue;
			}

			if (!empty($searched)) {
				foreach ($searched as $searched_info) {
					if ($searched_info['word'] == $word) {
						if ($searched_info['match'] == 1) {
							$matched++;
						}else {
							$missed++;
						}
						continue 2;
					}
				}
			}
			
			$searched_word = array();
			
			$searched_word['word'] = $word;
			
			$word = AkString::getGermanStr($word, false);
			
			$sql = 'SELECT COUNT(*) as num FROM dms_words WHERE word="'.$word.'"';
			
			$sql_ret = $GLOBALS['db']->select($sql);
			
			$query++;
			
			if ($sql_ret[0]['num'] > 0) {
				$matched++;
				$searched_word['match'] = 1;
			} else {
				$missed++;
				$missed_arr[] = $word;
				$searched_word['match'] = 0;
			}
			
			$searched[] = $searched_word;
		}
		
		$perst = number_format($missed/($missed+$matched), 4);
		$ret = array(
			'matched'	=> $matched,
			'missed' 	=> $missed, 
			'missed percentage'	=> $perst,
			'query'		=> $query,
			'missed words'	=> $missed_arr
		);
		
		return $ret;
	}
	
	/**
	 * segment the words string to a single word array
	 *
	 * @param string $words
	 * 
	 * @return array|bool
	 */
	function getWordsList($words) {
		$seg_key = array(',', ' ', ':', ';', '?', '!', '(', ')', '#', '$', '^', '&', '*', '-', '_', '+', '=', '<', '>', '/', '.', '\\', '`', '~', '"', '\'', '%', "\r\n", "\r", "\n");
		
		$words_arr = AkString::multiexplode($seg_key, $words);
		
		return $words_arr;
	}
}