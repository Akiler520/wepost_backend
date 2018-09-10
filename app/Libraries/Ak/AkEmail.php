<?php
/**
 *	email class for send
 * 
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2013
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2013-02-19
 */
class AkEmail
{
	/**
	 * mail servers
	 *
	 * @var array
	 */
	private static $mailer_server = array();

	/**
	 * object of PHPMailer
	 *
	 * @var object
	 */
    public $mailer;
    
    /**
     * debug
     *
     * @var bool
     */
    public $debug = false;
    
    /**
     * timeout of send mail
     *
     * @var integer
     */
    public $timeout = 30;

    /**
     * error
     *
     * @var array
     */
    public $errors = array();
    
    /**
     * Blind Carbon Copy
     *
     * @var array
     */
    public $bcc = array();

    public function __construct($mailer_gw) {
        $this->mailer = new PHPMailer();
//        $this->mailer->SetLanguage('zh_cn');		// default is en.

        if (!isset($mailer_gw['Mailer']) || $mailer_gw['Mailer'] == 'mail') {
            $this->mailer->IsMail();
        } else {
            $mailer_gw = $this->initGw($mailer_gw);
            foreach ($mailer_gw as $key=>$val) {
                $this->mailer->$key = $val;
            }
        }
    }

    /**
     * init object once
     */
    static public function get_instance($mail_config, $name = '') {
        if (!isset(self::$mailer_server[$name])) {
            $mailer_gw = array(
                'Mailer'	=> $mail_config['mode'],
                'From' 		=> $mail_config['from'],
                'FromName'	=> $mail_config['from_name'],
                'Host' 		=> $mail_config['host'],
                'Port' 		=> $mail_config['port'],
                'Username' 	=> $mail_config['auth_username'],
                'Password' 	=> $mail_config['auth_password']
            );		
            
            return self::$mailer_server[$name] = new self($mailer_gw);
        } else {
            return self::$mailer_server[$name];
        }
    }
    
    public function setBcc($address, $name = '') {
    	$this->bcc[$name] = $address;
    }

    /**
     * email send
     * @param mixed $toaddress the address of accept
     *        $toaddress = arary('$name'=>'@email')
     * @param string $subject 
     * @param string $body 
     * @param string $charset 
     * @param bool $is_html 
     * @param mixed $attachs 
     *        eg：$attachs = arary('$name'=>'$file')
     * @return boolean
     */
    public function send($toaddress, $subject, $body, $attachs = false, $charset='utf-8', $is_html=true) {
        //$this->mailer->Priority  = $this->priority; // the priority of email
        $this->mailer->CharSet   = $charset;
        $this->mailer->IsHTML($is_html);
        $this->mailer->Subject   = $subject;
        $this->mailer->Body      = $body;
        $this->mailer->timeout = $this->timeout;
        $this->mailer->SMTPDebug = $this->debug;
        if ($attachs) {
            $this->mailer->ClearAttachments();//if set attachment, then clear the history.
            if (is_array($attachs)) {
                foreach ($attachs as $name=>$file) {
                    $this->mailer->AddAttachment($file, $name);
                }
            } else {
                $this->mailer->AddAttachment($attachs, $attachs);
            }
        }

        $this->mailer->ClearAddresses(); // clear the history of email receiver.
        if (is_array($toaddress)) {
            foreach ($toaddress as $name=>$mail) {
                $this->mailer->AddAddress($mail, $name);
            }
        } else {
            $this->mailer->AddAddress($toaddress);
        }
        
        if (!empty($this->bcc)) {
        	foreach ($this->bcc as $bcc) {
        		$this->mailer->AddBCC($bcc);
        	}
        }
        $send_result = $this->mailer->Send();
        $this->errors[] = $this->mailer->ErrorInfo;
        
        return $send_result;
    }

    /**
     * init the gateway information of email send.
     * @param array $mailer_gw
     * @return arrray
     */
    private function initGw($mailer_gw) {
        $init_gw = array(
            'Mailer'=>'mail',
            'From' => '',
            'FromName' => 'bpg.com',
            'Host' => 'localhost',
            'Port' => '25',
            'SMTPAuth' => true,
            'Username' => '',
            'Password' => '',
            'Timeout' => 30,
            'SMTPDebug' => false,
        );
        if (is_array($mailer_gw)) {
            // intersect array to set, only set the attribute of phpmailer.
            $mailer_gw = array_intersect_key($mailer_gw, $init_gw);
            $ret = array_merge($init_gw, $mailer_gw);
 
            return $ret;
        } else {
            return false;
        }
    }
    
    public function getError() {
    	return $this->errors;
    }
}