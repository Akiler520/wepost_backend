<?php
/**
 *	zip manager
 *
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2020
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2013-09-29 14:06:12
 */
class AkZip
{
	/**
	 * zip out file
	 *
	 * @var string
	 */
	public $_OutFile = 'AkZip.zip';
	
	
	/**
	 * send in, maybe files or folder
	 *
	 * @var unknown_type
	 */
	public $_InFF;
	
	/**
	 * the type of send in
	 * maybe:file, folder
	 *
	 * @var unknown_type
	 */
	public $_InType;
	
	/**
	 * the object of class ZipArchive 
	 *
	 * @var object
	 */
	public $_ZipObj;
	
	/**
	 * error info
	 *
	 * @var array
	 */
	public $_Error = array();
	
	function __construct($inFF, $ourFile = '') {
		$this->_InFF = $inFF;
		if ($ourFile != '') {
			$this->_OutFile = str_replace('\\', '/', $ourFile);
		}
		if (file_exists($this->_OutFile)) {
			$this->setError('Out put file is exist.');
		}
		
		$this->_ZipObj = new ZipArchive();
		
		$this->setInType();
	}
	
	function checkOutFile() {
		if(strtolower(end(explode('.',$filename))) != 'zip'){
			return false;
		}
		
		return true;
	}
	function setError($msg) {
		$this->_Error[] = $msg;
	}
	
	function zip() {
		switch ($this->_InType) {
			case 'folder':
				$ret = $this->folderZip();
				if (!$ret) {
					$this->setError('zip folder error.');
				}
				break;
			case 'file':
				break;
			case 'folders':
				break;
			case 'files':
				break;
		}
		
		return $ret;
	}
	
	function setInType() {
		if (is_dir($this->_InFF)) {
			$this->_InType = 'folder';
		} elseif (is_file($this->_InFF)) {
			$this->_InType = 'file';
		} elseif (is_array($this->_InFF)) {
			
			$dir = $file = 0;
			foreach ($this->_InFF as $val) {
				if (is_dir($val)) {
					$dir = 1;
					continue;
				}
				if (is_file($val)) {
					$file = 2;
					continue;
				}
			}
			
			switch ($dir+$file) {
				case 0:
					$this->_InType = 'error';
					break;
				case 1:
					$this->_InType = 'folders';
					break;
				case 2:
					$this->_InType = 'files';
					break;
				case 3:
					$this->_InType = 'mix';
					break;
				default:
					$this->_InType = 'error';
					break;
			}
		} else {
			$this->_InType = 'error';
		}
	}
	
	function fileZip($files = array()) {
		
	}
	/**
	* @desc  creat compress file by folder
	*
	* @param array $missfile		the file we don't want to include
	* @param array $fromString		set by self
	* 								eg: add strin 'this is my file' into new file 'info.ini'
	* 									set like this: array(array('info.ini','this is my file'));
	*/
	function folderZip($missfile=array(), $addfromString=array()){
		$dir = $this->_InFF;
		$filename = $this->_OutFile;
		if(!file_exists($dir) || !is_dir($dir)){
			$this->setError('Can not exists dir:'.$dir);
			return false;
		}
		
		$dir = str_replace('\\','/',$dir);
		if(file_exists($filename)){
			$this->setError('the zip file '.$filename.' has exists !');
			return false;
		}
		
		$files = array();
		$this->getFolderFiles($dir,$files);
		if(empty($files)){
			$this->setError(' the dir is empty');
			return false;
		}
	
		$res = $this->_ZipObj->open($filename, ZipArchive::CREATE);
		if ($res === TRUE) {
			foreach($files as $v){
				if(!in_array(str_replace($dir.'/','',$v),$missfile)){
					$this->_ZipObj->addFile($v,str_replace($dir.'/','./',$v));
				}
			}
			if(!empty($addfromString)){
				foreach($addfromString as $v){
					$this->_ZipObj->addFromString($v[0],$v[1]);
				}
			}
			$this->_ZipObj->close();
			
			return true;
		} else {
			return false;
		}
	}

	function getFolderFiles($dir, &$files=array()){
		if(!file_exists($dir) || !is_dir($dir)){
			return false;
		}
		
		if(substr($dir, -1) == '/'){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		
		$_files = scandir($dir);
		foreach($_files as $v){
			if($v != '.' && $v!='..'){
				if(is_dir($dir.'/'.$v)){
					$this->getFolderFiles($dir.'/'.$v,$files);
				} else {
					$files[] = $dir.'/'.$v;
				}
			}
		}
		
		return $files;
	}
}