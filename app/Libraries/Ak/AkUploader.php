<?php
namespace App\Libraries\Ak;

/**
 *	upload manager
 *
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2020
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2010-09-29 14:06:12
 */
class AkUploader {
	/**
	 * the value of the html input named file
	 *
	 * @var string
	 */
	private $_FileBar;
	
	/**
	 * filter file type
	 *
	 * @var array
	 */
//	private $_FileTypeFilter = array('image/gif', 'image/jpeg', 'image/png');
	private $_FileTypeFilter = array('doc', 'docx', 'pptx', 'xls', 'xlsx', 'gif', 'jpeg', 'jpg', 
									'pdf', 'png', 'ico', 'txt', 'rar', 'exe', 'zip', 'gz', 'accdb',
									'psd', 'tif', 'jnt', 'odg', 'odp', 'ods', 'odt');
	
	/**
	 * the path to save file
	 *
	 * @var string
	 */
	private $_FileSavePath = 'upload/';
	
	/**
	 * the prefix of file to save
	 *
	 * @var string
	 */
	private $_FileNameSavePrefix = 'ak_';
	
	/**
	 * set the name to save
	 *
	 * @var string
	 */
	private $_FileSaveName = false;
	
	/**
	 * if is multi files upload
	 *
	 * @var bool
	 */
	private $_IsMulti = false;
	
	/**
	 * if random to set file name to save
	 * 1=yes, 0=use the old name(will add file prefix)
	 *
	 * @var integer
	 */
	private $_IsFileNameRand = 1;
	
	/**
	 * the maxsize of upload file, default is 20M
	 *
	 * @var integer
	 */
	private $_FileSizeLimited = 10;
	
	/**
	 * limit the number of upload files, default is 20.
	 *
	 * @var integer
	 */
	private $_FileNumberLimited = 10;
	
	/**
	 * the max length of file name
	 *
	 * @var integer
	 */
	private $_FileNameLimited = 255;
	
	/**
	 * how much files will be upload
	 *
	 * @var integer
	 */
	private $_UpFileNumber;
	
	/**
	 * if recursive to make dir
	 *
	 * @var bool
	 */
	private $_dirMakeRecursive = true;
	
	/**
	 * error information when upload
	 * array(
	 * 		1=> "The file is too large (server)."
	 * 		2=> "The file is too large (form)."
	 * 	);
	 * the key of array is the key of upload file, depends on $_FILES
	 *
	 * @var array
	 */
	private $_Error = array();
	
	/**
	 * get the information of upload file
	 * the info is from $_FILES
	 *
	 * @var array
	 */
	private $_FileInfo = array(
							'name'		=> array(),
							'type'		=> array(),
							'size'		=> array()	
						);
	
	/**
	 * the result message after upload: array(
	 * 										'src_name'	=> array('name1', 'name2'),		//name include suffix
	 * 										'save_name'	=> array('sname1','sname2'),
	 * 										'size'		=> array(852,396),
	 * 										'status'	=> array(1, 0),		//0=succes,-1=failed, 2=file exist, 3=can't move file(no power)
	 * 										'sava_path' => '/upload'
	 *										);
	 * @var array
	 */
	private $_UpResultInfo = array('src_name'	=> array(),
									'save_name'	=> array(),
									'status'	=> array(),
									'save_path'	=> ''
								);
	
	/**
	 * construct the class
	 * $FileBar must not be $_FILES, but is the value of $_FILES, for instance: $_FILES['upfile']
	 *
	 * @param array $FileBar
	 */
	function __construct($FileBar) {
		$this->_FileBar = $FileBar;
		
		if (is_array($this->_FileBar["name"])) {
			$this->_IsMulti = true;
			$this->_UpFileNumber = count($this->_FileBar["name"]);
		} else {	// change single file upload to multi files upload
			$this->_UpFileNumber = 1;
			$this->_FileBar["name"] = array($this->_FileBar["name"]);
			$this->_FileBar["type"] = array($this->_FileBar["type"]);
			$this->_FileBar["tmp_name"] = array($this->_FileBar["tmp_name"]);
			$this->_FileBar["error"] = array($this->_FileBar["error"]);
			$this->_FileBar["size"] = array($this->_FileBar["size"]);
		}

		$this->fileCheck();
		$this->setUpFileInfo();
	}
	
	function fileCheck() {
		/**
		 * error check, if so, set it to $_Error.
		 */
		for ($i = 0; $i < $this->_UpFileNumber; $i++) {
			$isType = $this->FileTypeCheck($this->_FileBar['name'][$i]);
			
			if (!$isType) {
				$this->setError($i, "The file type is not allowed.");
				continue;
			}
			
			if (strlen($this->_FileBar['name'][$i]) > $this->_FileNameLimited) {
				$this->setError($i, "The file name is too long, must less than $this->_FileNameLimited.");
				continue;
			}
/*
			if (!preg_match("/^[^\x{4e00}-\x{9fa5}]+$/u",$this->_FileBar['name'][$i])) {	// can not include chinese;
				$this->setError($i, "The file name can not include chinese word.");
				continue;
			}*/
			
			switch ($this->_FileBar["error"][$i]) {
				case 0:
					break;
				case 1:
					$this->setError($i, "The file is too large (server).");
					break;
				case 2:
					$this->setError($i, "The file is too large (form).");
					break;
				case 3:
					$this->setError($i, "The file was only partially uploaded.");
					break;
				case 4:
					$this->setError($i, "No file was uploaded.");
					break;
				case 5:
					$this->setError($i, "The servers temporary folder is missing.");
					break;
				case 6:
					$this->setError($i, "Failed to write to the temporary folder.");
					break;
				case 7:
					$this->setError($i, "Failed to write file to disk");
					break;
				case 8:
					$this->setError($i, "File upload stopped by extension");
					break;			
				default:
					$this->setError($i, "File upload unknow error happened.");
					break;
			}
		}
	}
	
	/**
	 * check file type if is allowed
	 *
	 * @param string $fileName
	 * 
	 * @return bool
	 */
	function FileTypeCheck($fileName) {
		$isType = true;
		
		$suffix = $this->getFileSuffix($fileName);
		/*for ($j = 0; $j < count($this->_FileTypeFilter); $j++) {
			if (strtolower($suffix) == $this->_FileTypeFilter[$j]) {
				$isType = true;
			}
		}*/
		
		if ($suffix == 'php' || $suffix == 'js') {
			$isType = false;
		}
		
		return $isType;
	}
	
	/**
	 * set the information of upload file
	 *
	 */
	function setUpFileInfo() {
		for ($i = 0; $i < $this->_UpFileNumber; $i++) {
			$this->_FileInfo['name'][$i] = $this->_FileBar['name'][$i];
			$this->_FileInfo['type'][$i] = $this->_FileBar['type'][$i];
			$this->_FileInfo['size'][$i] = $this->_FileBar['size'][$i];
		}
	}
	
	/**
	 * get the information of upload file
	 *
	 * @return array
	 */
	function getUpFileInfo() {
		return $this->_FileInfo;
	}
	
	/**
	 * set error message when upload
	 *
	 * @param integer $key	the key of upload files
	 * @param string $msg	error message
	 */
	function setError($key, $msg) {
		$this->_Error[$key] = $msg;
	}
	
	/**
	 * get error message
	 *
	 * @return 0=no error, array=the array of message
	 */
	function getError() {
		if (($count = count($this->_Error)) <= 0) {
			return 0;
		}
		return $this->_Error;
	}
	
	/**
	 * get the result of upload
	 *
	 * @return array
	 */
	function getResult() {
		return $this->_UpResultInfo;
	}
	
	/**
	 * set path to save file
	 *
	 * @param string $path	path name
	 */
	function setSavePath($path) {
		if (!isset($path) || $path == NULL) {
			return false;
		}
		if (!is_dir($path) && !@mkdir($path, '0755', $this->_dirMakeRecursive)) {
			die("{error:'mkdir error.', msg:''}");			
		}
		
		if (substr($path, strlen($path)-1) != '/') {
			$this->_FileSavePath = $path.'/';
		} else {
			$this->_FileSavePath = $path;
		}
	}
	
	/**
	 * get the path of the file to save
	 *
	 * @return string
	 */
	function getSavePath() {
		return $this->_FileSavePath;
	}
	
	/**
	 * if creat path Recursively.
	 * true=yes，divide $key by '/', and creat them by grade.
	 * false=no，only creat one path
	 *
	 * @param bool $key
	 */
	function setDirMakeRecursive($key = true) {
		$this->_dirMakeRecursive = $key;
	}
	
	/**
	 * set the upload file size limit
	 *
	 * @param integer $size
	 * @return bool
	 */
	function setUploadFileMaxSize($size) {
		if (!is_numeric($size)) {
			return false;
		}
		$this->_FileSizeLimited = $size;
		
		return true;
	}
	
	/**
	 * set the prefix of file to save
	 *
	 * @param string $prefix	prefix name
	 */
	function setSaveFileNamePrefix($prefix = '') {
		$this->_FileNameSavePrefix = $prefix;
	}
	
	/**
	 * set the name to save
	 *
	 * @param string $name
	 */
	function setFileSaveName($name) {
		$this->_FileSaveName = $name;
	}
	
	/**
	 * get the suffix of file
	 * not include '.'
	 *
	 * @param string $FileName	file name
	 * @return string
	 */
	function getFileSuffix($FileName) {
		return strtolower(substr(strrchr($FileName,'.'), 1));
	}
	
	/**
	 * get the random string of file name
	 * use second and micosecond of current time.
	 *
	 * @return string
	 */
	function getFileNameRand($baseName = '') {
		$result = AkString::getMicroString();
		
		if ($baseName !== '') {
			$nameArr = array(
				'time'	=> $result,
				'name'	=> $baseName
			);
			
			$result = AkString::file_arr2str($nameArr);
		}
		
		return $result;
	}
	
	/**
	 * check error by $FileKey, the $FileKey is the number of upload file, depends on $_FILES.
	 *
	 * @param integer $FileKey	number of file in $_FILES.
	 * @return bool
	 */
	function isError($FileKey) {
		foreach ($this->_Error as $key => $val) {
			if ($key == $FileKey) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * set if to creat the save name file by random
	 *
	 * @param integer $key	1=random, 0=keep the old name(add file prefix)
	 */
	function setFileNameRand($key = 1) {
		$this->_IsFileNameRand = $key;
	}
	
	/**
	 * upload all files
	 */
	function uploadAll($fileId = 0) {
		$this->_UpResultInfo['save_path'] = $this->_FileSavePath;

		foreach ((array)$this->_FileBar["name"] as $key=>$val) {
			// if error happened, stop it, and run next.
			if ($this->isError($key)) {
				continue;
			}
			
//			$val = AkString::changeCharset($val);

			if ($this->_FileSaveName) {
				$val = $this->_FileSaveName;
			}

			if ($this->_IsFileNameRand == 1) {
				$new_name = $this->_FileNameSavePrefix.$this->getFileNameRand($val).'.'.$this->getFileSuffix($val);
			} else {
				$new_name = $this->_FileNameSavePrefix.$val;
			}
			
			// check if file exist, compare file name and file size.
			if (file_exists($this->_FileSavePath.$new_name) && 
				filesize($this->_FileSavePath.$new_name) == $this->_FileBar["size"][$key]) {
                //file exists
				$this->_UpResultInfo['status'][$key] = 2;
				$this->_UpResultInfo['save_name'][$key] = "error";
                                
			} else {
				$ret = move_uploaded_file($this->_FileBar["tmp_name"][$key], $this->_FileSavePath . $new_name);
                                
                if($fileId != 0){
                    //if file exists 
                    $this->_UpResultInfo['status'][$key] = ($ret == true) ? 4 : 5;
                }else{
                    //file not exists
                    $this->_UpResultInfo['status'][$key] = ($ret == true) ? 0 : 1;
                }
                                
				$this->_UpResultInfo['save_name'][$key] = $new_name;
			}
			
			$this->_UpResultInfo['size'][$key] = $this->_FileBar["size"][$key];			
			$this->_UpResultInfo['src_name'][$key] = $val;			
		}
	}
	
	/**
	 * upload one file
	 *
	 * @param integer $key		the number of file in $_FILES
	 * @param string $saveName	if set it, then use it to be the file name to save(must include file suffix), 
	 * 							if no, use random name.
	 */
	function uploadOne($key, $saveName = NULL) {
		$fileName = $this->_FileBar["name"][$key];
		$this->_UpResultInfo['src_name'][$key] = $fileName;	
		$this->_UpResultInfo['save_path'] = $this->_FileSavePath;
		
		if ($this->_IsFileNameRand == 1) {
			$saveName = $this->_FileNameSavePrefix.$this->getFileNameRand($fileName).'.'.$this->getFileSuffix($fileName);
		} else {
			if ($saveName == NULL) {
				$saveName = $this->_FileNameSavePrefix.$fileName;
			} else {
				$saveName = $this->_FileNameSavePrefix.$saveName;
			}
		}

		if (file_exists($this->_FileSavePath.$saveName) && 
			filesize($this->_FileSavePath.$saveName) == $this->_FileBar["size"][$key]) {
			$this->_UpResultInfo['status'][$key] = 2;
			$this->_UpResultInfo['save_name'][$key] = "error";
		} else {
			$ret = move_uploaded_file($this->_FileBar["tmp_name"][$key], $this->_FileSavePath . $saveName);
			$this->_UpResultInfo['status'][$key] = ($ret == true) ? 0 : 1;
			$this->_UpResultInfo['save_name'][$key] = $saveName;
		}
	}	
}
?>
