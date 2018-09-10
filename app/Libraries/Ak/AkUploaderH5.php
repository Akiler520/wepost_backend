<?php
/**
 *	upload manager of H5
 *
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2020
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2015-09-29 14:06:12
 */
class AkUploaderH5{
    /**
     * the type of upload method
     * @var string
     */
    protected $_upload_type = 'h5';

    /**
     * max size of the uploading file
     * @var int
     */
    protected $_max_size_chunk = 4194304;    // 4M = 4 * 1024 * 1024

    /**
     * the length of file content to create md5 string
     * @var int
     */
    protected $_md5_file_length = 2097152;     // 2M = 2 * 1024 * 1024

    /**
     * return info
     * @var array
     */
    protected $_return_message = array(
        'status_code'       => 1,
        'status_message'    => "success",
        'save_name'         => "",  // the name is saved in system
        'original_name'     => ""   // the original name of the uploading file
    );

    /**
     * temp dir of file
     * @var string
     */
    protected $_tmp_dir = './upload_tmp';

    /**
     * save dir of file
     * @var string
     */
    protected $_target_dir = './upload';

    /**
     * file name
     * @var string
     */
    protected $_file_name = '';

    /**
     * file name of saved
     * @var string
     */
    protected $_save_name = '';

    /**
     * chunk code of part file
     * if the chunk is supported, it may be 0,1,2,3...
     * @var int
     */
    protected $_chunk = 0;

    /**
     * total number of chunk
     * if the chunk is supported, it may be 1,2,3...
     * @var int
     */
    protected $_chunk_num = 1;

    protected $_save_method = 1;

    /**
     * instance
     * @param integer $saveMethod
     */
    public function __construct($saveMethod = 1){
        $this->_save_method = $saveMethod;

        if(!empty($_FILES)){
            $this->_upload_type = 'form';
        }else{
            $this->_upload_type = 'h5';
        }

        // Chunk might be enabled
        $this->_chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $this->_chunk_num = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;

        $this->setTmpPath();

        $this->getFileName();

        $this->setSaveName();
    }

    /**
     * set the temp path to save the part of uploading file
     * @param string $path
     * @return bool
     */
    public function setTmpPath($path = ''){
        if(empty($path)){
            $path = $_SERVER['DOCUMENT_ROOT'].'/runtime/upload_tmp';
        }

        if(!is_dir($path)) {
            $ret = @mkdir($path, 0777, true);
            if(!$ret){
                $this->_return_message['status_code'] = 6;
                $this->_return_message['status_message'] = "Unable to create upload folder.";

                return false;
            }
        }

        $this->_tmp_dir = $path;

        return true;
    }

    /**
     * set the save path of uploading file
     * @param $path
     * @return bool
     */
    public function setUploadPath($path){
        $current_time = time();

        $cYear  = date('Y', $current_time);
        $cMonth = date('m', $current_time);
        $cDay   = date('d', $current_time);

        switch($this->_save_method){
            case 1:
                $path .= "{$cYear}/{$cMonth}/{$cDay}/";
                break;
            case 2:
                $path .= "{$cYear}/{$cMonth}/";
                break;
            case 3:
                $path .= "{$cYear}/";
                break;
            default:
                break;
        }

        if(!is_dir($path)) {
            $ret = @mkdir($path, 0777, true);
            if(!$ret){
                $this->_return_message['status_code'] = 6;
                $this->_return_message['status_message'] = "Unable to create upload folder.";

                return false;
            }
        }

        $this->_target_dir = $path;

        return true;
    }

    /**
     * get return info of the upload function
     *
     * @return array
     */
    public function getError(){
        return $this->_return_message;
    }

    /**
     * Start upload
     * @return array|bool
     */
    public function upload(){
        // get the contents of file
        $ret = $this->getFileContent();

        if(!$ret){
            return false;
        }

        // try to finish the upload
        $this->finishUpload();

        return true;
    }

    /**
     * check if the error is happened
     *
     * @return bool
     */
    public function isError(){
        if($this->_return_message['status_code'] != 1){
            return true;
        }

        return false;
    }

    /**
     * set the saved name of the file
     * @param string $name
     */
    public function setSaveName($name = ''){
        $fileInfo = pathinfo($this->_file_name);

        if(empty($name)){
            $t = explode(' ', microtime());
            $this->_save_name = $t[1]+$t[0];
            $this->_save_name = str_replace('.', '', $this->_save_name);
        }else{
            $this->_save_name = $name;
        }

        $this->_save_name .= '.' . $fileInfo['extension'];
    }

    /**
     * get file name
     */
    public function getFileName(){
        // Get a file name
        if (isset($_REQUEST["name"])) {
            $this->_file_name = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $this->_file_name = $_FILES["file"]["name"];
        } else {
            $this->_file_name = uniqid("file_");
        }

        /*if (mb_detect_encoding($this->_file_name, 'UTF-8', true) === false) {
            $this->_file_name = utf8_encode($this->_file_name);
        }*/

        $this->_file_name = AkString::charsetToUTF8($this->_file_name);
    }

    /**
     * get the content of the uploading file
     */
    public function getFileContent(){
        $fpOut = '';
        $fpIn = '';
        $content = '';

        $filePath = $this->_tmp_dir . DIRECTORY_SEPARATOR . $this->_file_name;
        $uploadPath = $this->_target_dir . DIRECTORY_SEPARATOR . $this->_file_name;

        $chunkFile = "{$filePath}_{$this->_chunk}.part";
        $chunkFileTmp = "{$filePath}_{$this->_chunk}.parttmp";

        // check if the chunk file is exist on server
        if(is_file($chunkFile)){
            $this->_return_message['status_code'] = 5;
            $this->_return_message['status_message'] = "Chunk file was uploaded yet.";

            return false;
        }

        // Open temp file
        if (!$fpOut = @fopen($chunkFileTmp, "wb")) {
            $this->_return_message['status_code'] = 3;
            $this->_return_message['status_message'] = "Can't create temp file for the uploading file.";

            return false;
        }

        switch($this->_upload_type){
            case "form":
                if($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])){
                    $this->_return_message['status_code'] = 2;
                    $this->_return_message['status_message'] = "Unknown uploading file.";

                    return false;
                }

                if(!$fpIn = @fopen($_FILES["file"]["tmp_name"], "rb")){
                    $this->_return_message['status_code'] = 3;
                    $this->_return_message['status_message'] = "Can't get the contents of the uploading file.";

                    return false;
                }
                break;
            case "h5":
                if (!$fpIn = @fopen("php://input", "rb")) {
                    $this->_return_message['status_code'] = 3;
                    $this->_return_message['status_message'] = "Can't get the contents of the uploading file.";

                    return false;
                }
                break;
        }

        while ($buff = fread($fpIn, $this->_max_size_chunk)) {
            fwrite($fpOut, $buff);
        }

        @fclose($fpOut);
        @fclose($fpIn);

        rename($chunkFileTmp, $chunkFile);

        return true;
    }

    /**
     * try to finish the upload
     * gather all the trunk files, then save into the final file
     *
     * @return bool
     */
    public function finishUpload(){
        $index = 0;
        $done = true;
        $filePath = $this->_tmp_dir . DIRECTORY_SEPARATOR . $this->_file_name;
        $uploadPath = $this->_target_dir . DIRECTORY_SEPARATOR . $this->_save_name;

        for( $index = 0; $index < $this->_chunk_num; $index++ ) {
            if ( !file_exists("{$filePath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }

        if ( $done ) {
            if (!$fpOut = @fopen($uploadPath, "wb")) {
                $this->_return_message['status_code'] = 3;
                $this->_return_message['status_message'] = "Can't open file stream.";

                return false;
            }

            if ( flock($fpOut, LOCK_EX) ) {
                for( $index = 0; $index < $this->_chunk_num; $index++ ) {
                    if (!$fpIn = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }

                    while ($buff = fread($fpIn, 4096)) {
                        fwrite($fpOut, $buff);
                    }

                    @fclose($fpIn);
                    @unlink("{$filePath}_{$index}.part");
                }

                flock($fpOut, LOCK_UN);
            }

            $this->_return_message['save_name'] = $uploadPath;  // saved name
            $this->_return_message['original_name'] = $this->_file_name;  // the original name

            @fclose($fpOut);

            // get md5 of file by length
            $handle = fopen($uploadPath,"rb");//使用打开模式为rb
            $contentMd5 = fread($handle,$this->_md5_file_length);//读为二进制

            $this->_return_message['md5'] = md5($contentMd5);
            @fclose($handle);
        }

        if(!$done){
            $this->_return_message['status_code'] = 4;
            $this->_return_message['status_message'] = "Don't gather all the chunks of the file by now.";

            return false;
        }

        return $done;
    }

}