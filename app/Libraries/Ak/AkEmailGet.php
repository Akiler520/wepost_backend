<?php
/**
 *	get email in client
 * 
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2013
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2013-02-19
 */
class AkEmailGet {

	/**
	 * the resource of the connect to email server
	 * 
     * @var resource
     */
	private $_connect;

	/**
	 * information of email
	 * 
     * @var object
     */
	private $_mailInfo;

	/**
	 * the total count of emails
	 * 
     * @var int
     */
	private $_totalCount;

	/**
	 * the server info need to connect
	 *
	 * @var string
	 */
	private $_server;

	/**
	 * the username of login user
	 *
	 * @var string
	 */
	private $_username;

	/**
	 * the password of the login user
	 *
	 * @var string
	 */
	private $_password;

	/**
	 * the address of the user's email
	 *
	 * @var string
	 */
	private $_email;

	/**
	 * init class
	 *
	 * @param string $host				the mail server address
	 * @param string $username			the username to login
	 * @param string $password			the password
	 * @param string $emailAddress		the email address of the user
	 * @param string $serviceType		the type of the email service
	 * @param integer $port				the port of the mail server
	 * @param bool $ssl					if use ssl, default is false
	 */
	public function __construct($host, $username, $password, $emailAddress = '', $serviceType = 'pop', $port = 110, $ssl = false) {
		if($serviceType == 'imap') {
			if($port == '') {
				$port = '143';
			}

			$strConnect = '{'.$host.':'.$port. '}INBOX';
		} else {
			$strConnect='{'.$host.':'.$port. '/pop3'.($ssl ? "/ssl" : "").'}INBOX';
		}

		$this->_server 		= $strConnect;
		$this->_username	= $username;
		$this->_password	= $password;
		$this->_email		= $emailAddress;
	}

	/**
     * Open an IMAP stream to a mailbox
     *
     * @return resource|bool
     */
	public function connect() {
		if (!function_exists('imap_open')) {
			exit('You have not install php_imap, so you can not get email.');
		}
		$this->_connect = @imap_open($this->_server, $this->_username, $this->_password);

		if(!$this->_connect) {
			return false;
		}

		return $this->_connect;
	}

	/**
     * Get information about the current mailbox
     *
     * @return object|bool
     */
	public function mailInfo(){
		$this->_mailInfo = imap_mailboxmsginfo($this->_connect);
		if(!$this->_mailInfo) {
			echo "get mailInfo failed: " . imap_last_error();
			return false;
		}

		return $this->_mailInfo;
	}

	/**
     * Read an overview of the information in the headers of the given message
     *
     * @param string $msgRange
     * @return array
     */
	public function mailList($msgRange='')
	{
		if ($msgRange) {
			$range=$msgRange;
		} else {
			$this->mailTotalCount();
			$range = "1:".$this->_totalCount;
		}
		$overview  = imap_fetch_overview($this->_connect,$range);
		foreach ($overview  as $val) {
			$mailList[$val->msgno]=(array)$val;
		}
		return $mailList;
	}

	/**
     * get the total count of the current mailbox
     *
     * @return int
     */
	public function mailTotalCount() {
		$check = imap_check($this->_connect);
		$this->_totalCount = $check->Nmsgs;
		return $this->_totalCount;
	}

	/**
     * Read the header of the message
     *
     * @param string $msgCount
     * @return array
     */
	public function mailHeader($msgCount) {
		$mailHeader = array();
		$header=imap_header($this->_connect,$msgCount);
		$sender=$header->from[0];

		//echo '<pre>';print_r($header);exit;
		$replyTo=$header->reply_to[0];
		if(strtolower($sender->mailbox)!='mailer-daemon' && strtolower($sender->mailbox)!='postmaster') {
			//			$subject = mb_convert_encoding($header->subject, 'utf-8', 'gb2312,ISO-8859-1,gbk,gb18030');
			$subject = $this->decodeMimeString($header->subject);

			$from =strtolower($sender->mailbox).'@'.$sender->host;

			$fromName = $this->decodeMimeString($sender->personal);
			$fromName = $fromName == "" ? $sender->mailbox : $fromName;

			$toOther = strtolower($replyTo->mailbox).'@'.$replyTo->host;

			$toOtherName = $this->decodeMimeString($replyTo->personal);
			$toOtherName = $toOtherName == "" ? $replyTo->mailbox : $toOtherName;

			$mailHeader = array(
			'from'=> $from,
			'fromName'=> $fromName,
			'toOther'=> $toOther,
			'toOtherName'=> $toOtherName,
			'subject'=> $subject,
			'to'	=> $header->to,
			'cc'	=> $header->cc,
			//				'to'=>iconv_mime_decode($header->toaddress, 0, "ISO-8859-1"),
			'date'=>$header->date,
			'id'=>$header->Msgno,
			'seen'=>$header->Unseen
			);
		}

		return $mailHeader;
	}

	/**
	 * return supported encodings in lowercase.
	 *
	 * @return array
	 */
	function mb_list_lowerencodings() {
		$r = mb_list_encodings();
		for ($n=sizeOf($r); $n--; ) {
			$r[$n]=strtolower($r[$n]);
		}

		return $r;
	}

	/**
	 * Receive a string with a mail header and returns it 
	 * decoded to a specified charset.
	 * If the charset specified into a piece of text from header
	 * isn't supported by "mb", the "fallbackCharset" will be
	 * used to try to decode it.
	 *
	 * @param string $mimeStr
	 * @param string $inputCharset
	 * @param string $targetCharset
	 * @param string $fallbackCharset
	 * @return string
	 */
	function decodeMimeString($mimeStr, $inputCharset='utf-8', $targetCharset='utf-8', $fallbackCharset='iso-8859-1') {
		$encodings = $this->mb_list_lowerencodings();
		$inputCharset = strtolower($inputCharset);
		$targetCharset = strtolower($targetCharset);
		$fallbackCharset = strtolower($fallbackCharset);

		$decodedStr = '';
		$mimeStrs = imap_mime_header_decode($mimeStr);
		for ($n = sizeOf($mimeStrs), $i = 0; $i < $n; $i++) {
			$mimeStr = $mimeStrs[$i];
			$mimeStr->charset = strtolower($mimeStr->charset);
			if (($mimeStr == 'default' && $inputCharset == $targetCharset)
			|| $mimStr->charset == $targetCharset
			) {
				$decodedStr .= $mimStr->text;
			} else {
				$decodedStr .= mb_convert_encoding(
				$mimeStr->text,
				$targetCharset,
				(in_array($mimeStr->charset, $encodings) ? $mimeStr->charset : $fallbackCharset)
				);
			}
		}

		return $decodedStr;
	}
	/**
	 * can decode all kinds of language for email string
	 * xxx: some error happen, I don't know why
	 *
	 * @param string $headerString
	 * @return string
	 */
	public function headerStringDecode($headerString) {
		$elements = imap_mime_header_decode($headerString);

		for ($i = 0; $i < count($elements); $i++) {
			$txt .= $elements[$i]->text;
		}

		return $txt;
	}

	/**
     * decode the subject of chinese
     *
     * @param string $subject
     * @return sting
     */
	public function subjectDecode($subject) {
		$beginStr = substr($subject, 0, 15);
		if (stripos($beginStr, 'ISO-8859-1') !== false) {
			$separator = '=?ISO-8859-1';
			$toEncoding = 'ISO-8859-1';
		} else if (stripos($beginStr, 'UTF-8') !== false) {
			$separator = '=?UTF-8';
			$toEncoding = 'UTF-8';
		} else if (stripos($beginStr, 'GB2312') !== false) {
			$separator = '=?GB2312';
			$toEncoding = 'GB2312';
		} else if (stripos($beginStr, 'gb18030') !== false) {
			$separator = '=?gb18030';
			$toEncoding = 'gb18030';
		}

		$encode = strstr($subject, $separator);
		if ($encode) {
			$explodeArr = explode($separator, $subject);
			$length = count($explodeArr);
			$subjectArr = array();
			for($i = 0; $i < $length / 2; $i++) {
				$subjectArr[$i][] = $explodeArr[$i * 2];
				if (@$explodeArr[$i * 2 + 1]) {
					$subjectArr[$i][] = $explodeArr[$i * 2 + 1];
				}
			}
			foreach ($subjectArr as $arr) {
				$subSubject = implode($separator, $arr);
				if (count($arr) == 1) {
					$subSubject = $separator . $subSubject;
				}
				$begin = strpos($subSubject, "=?");
				$end = strpos($subSubject, "?=");
				$beginStr = '';
				$endStr = '';
				if ($end > 0) {
					if ($begin > 0) {
						$beginStr = substr($subSubject, 0, $begin);
					}
					if ((strlen($subSubject) - $end) > 2) {
						$endStr = substr($subSubject, $end + 2, strlen($subSubject) - $end - 2);
					}
					$str = substr($subSubject, 0, $end - strlen($subSubject));
					$pos = strrpos($str, "?");
					$str = substr($str, $pos + 1, strlen($str) - $pos);
					$subSubject = $beginStr . imap_base64($str) . $endStr;
					$subSubjectArr[] = iconv ( $toEncoding, 'utf-8', $subSubject );
					//                    mb_convert_encoding($subSubject, 'utf-8' ,'gb2312,ISO-2022-JP');
				}
			}
			$subject = implode('', $subSubjectArr);
		}
		return $subject;
	}

	/**
     * Marks messages listed in msg_number for deletion. 
	 * Messages marked for deletion will stay in the mailbox until either imap_expunge() is called 
	 * or imap_close() is called with the optional parameter CL_EXPUNGE. 
     *
     * @param string $msgCount
     */
	public function markMailDelete($msgCount) {
		return @imap_delete($this->_connect,$msgCount);
	}

	/**
     * get attach of the message
     *
     * @param string $msgCount
     * @param string $path
     * @return array
     */
	public function getAttach($msgCount,$path) {
		$struckture = imap_fetchstructure($this->_connect,$msgCount);	//echo "<pre>"; print_r($struckture);
		$attach = array();
		if($struckture->parts) {
			foreach($struckture->parts as $key => $value) {
				$encoding = $struckture->parts[$key]->encoding;
				$p = $value;
				if($struckture->parts[$key]->ifdparameters && $struckture->parts[$key]->disposition == 'ATTACHMENT') {
					//				if($struckture->parts[$key]->ifdparameters) {	// if use this, sometimes can not get the right name;

					//get filename of attachment if present
					$filename = '';
					// if there are any dparameters present in this part
					if (count($p->dparameters) > 0){
						foreach ($p->dparameters as $dparam){
							if ((strtoupper($dparam->attribute) == 'NAME') || (strtoupper($dparam->attribute) == 'FILENAME')) $filename=$dparam->value;
						}
					}
					//if no filename found
					if ($filename == '') {
						// if there are any parameters present in this part
						if (count($p->parameters) > 0){
							foreach ($p->parameters as $param){
								if ((strtoupper($param->attribute) == 'NAME') ||(strtoupper($param->attribute) == 'FILENAME')) $filename=$param->value;
							}
						}
					}

					$name = $filename;
//					$name=$struckture->parts[$key]->parameters[$key]->value;
					$name = $this->decodeMimeString($name);		// covert the name string, because if there is space char in name, error will happened;
					//					$name = iconv_mime_decode($name, 0, "ISO-8859-1");		// covert the name string, because if there is space char in name, error will happened;
					$message = imap_fetchbody($this->_connect,$msgCount,$key+1);
					if ($encoding == 0) {
						$message = imap_8bit($message);
					} else if ($encoding == 1){
						$message = imap_8bit ($message);
					} else if ($encoding == 2) {
						$message = imap_binary ($message);
					} else if ($encoding == 3) {
						$message = imap_base64 ($message);
					} else if ($encoding == 4) {
						$message = quoted_printable_decode($message);
					}
					$this->downAttach($path,$name,$message);;
					$attach[] = $name;
				}
				if($struckture->parts[$key]->parts) {
					foreach($struckture->parts[$key]->parts as $keyb => $valueb) {
						$encoding=$struckture->parts[$key]->parts[$keyb]->encoding;
						$p = $valueb;
						if($struckture->parts[$key]->parts[$keyb]->ifdparameters && $struckture->parts[$key]->parts[$keyb]->disposition == 'ATTACHMENT'){
							//$name=$struckture->parts[$key]->parts[$keyb]->parameters[0]->value;

							//get filename of attachment if present
							$filename = '';
							// if there are any dparameters present in this part
							if (count($p->dparameters) > 0){
								foreach ($p->dparameters as $dparam){
									if ((strtoupper($dparam->attribute) == 'NAME') || (strtoupper($dparam->attribute) == 'FILENAME')) $filename=$dparam->value;
								}
							}
							//if no filename found
							if ($filename == '') {
								// if there are any parameters present in this part
								if (count($p->parameters) > 0){
									foreach ($p->parameters as $param){
										if ((strtoupper($param->attribute) == 'NAME') ||(strtoupper($param->attribute) == 'FILENAME')) $filename=$param->value;
									}
								}
							}
		
							$name = $this->decodeMimeString($filename);
							//							$name = iconv_mime_decode($name, 0, "ISO-8859-1");
							$partnro = ($key+1).".".($keyb+1);
							$message = imap_fetchbody($this->_connect,$msgCount,$partnro);
							if ($encoding == 0) {
								$message = imap_8bit($message);
							} else if ($encoding == 1) {
								$message = imap_8bit ($message);
							} else if ($encoding == 2) {
								$message = imap_binary ($message);
							} else if ($encoding == 3) {
								$message = imap_base64 ($message);
							} else if ($encoding == 4) {
								$message = quoted_printable_decode($message);
							}
							$this->downAttach($path,$name,$message);
							$attach[] = $name;
						}
					}
				}
			}
		}

		return $attach;
	}

	/**
     * download the attach of the mail to localhost
     *
     * @param string $filePath
     * @param string $message
     * @param string $name
     */
	public function downAttach($filePath,$name,$message) {
		if(!is_dir($filePath)) {
			@mkdir($filePath);
		}

		$fileOpen = @fopen($filePath.$name,"w");

		if (!$fileOpen) {
			return false;
		}

		fwrite($fileOpen,$message);
		fclose($fileOpen);
	}

	/**
     * get the body of the message
     *
     * @param string $msgCount
     * @return string
     */
	public function getBody($msgCount) {
		$body = $this->getPart($msgCount, "TEXT/HTML");
		if ($body == '') {
			$body = $this->getPart($msgCount, "TEXT/PLAIN");
		}
		if ($body == '') {
			return '';
		}
		return $body;
	}

	/**
     * Read the structure of a particular message and fetch a particular
     * section of the body of the message
     *
     * @param string $msgCount
     * @param string $mimeType
     * @param object $structure
     * @param string $partNumber
     * @return string|bool
     */
	private function getPart($msgCount, $mimeType, $structure = false, $partNumber = false) {
		if(!$structure) {
			$structure = imap_fetchstructure($this->_connect, $msgCount);
		}
		if($structure) {
			if($mimeType == $this->getMimeType($structure)) {
				if(!$partNumber) {
					$partNumber = "1";
				}
				$fromEncoding = $structure->parameters[0]->value;
				$text = imap_fetchbody($this->_connect, $msgCount, $partNumber);
				if($structure->encoding == 3) {
					$text =  imap_base64($text);
				} else if($structure->encoding == 4) {
					$text =  imap_qprint($text);
				}

				$text = mb_convert_encoding($text, 'utf-8', $fromEncoding.',gb2312,ISO-8859-1,gbk');
				return $text;
			}
			if($structure->type == 1) {
				while(list($index, $subStructure) = each($structure->parts)) {
					if($partNumber) {
						$prefix = $partNumber . '.';
					}
					$data = $this->getPart($msgCount, $mimeType, $subStructure, $prefix . ($index + 1));
					if($data){
						return $data;
					}
				}
			}
		}
		return false;
	}

	/**
     * get the subtype and type of the message structure
     *
     * @param object $structure
     */
	private function getMimeType($structure) {
		$mimeType = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
		if($structure->subtype) {
			return $mimeType[(int) $structure->type] . '/' . $structure->subtype;
		}
		return "TEXT/PLAIN";
	}

	/**
     * put the message from unread to read
     *
     * @param string $msgCount
     * @return bool
     */
	public function mailRead($msgCount) {
		$status = imap_setflag_full($this->_connect, imap_uid($this->_connect, $msgCount), "\\Seen", SE_UID);
		return $status;
	}

	/**
     * Close an IMAP stream
     * If set $flag to CL_EXPUNGE, the function will silently expunge the mailbox before closing, 
     * removing all messages marked for deletion. You can achieve the same thing by using imap_expunge()
     * and yon can set $flag to 0, only close the stream
     * 
     * @param integer $flag
     */
	public function closeMail($flag = CL_EXPUNGE) {
		@imap_close($this->_connect, $flag);
	}
}