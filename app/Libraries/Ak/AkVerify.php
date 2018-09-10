<?php
/**
 *	verify code manager
 *
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2020
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2014-09-29 14:06:12
 */
class AkVerify{
    private $charset = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789";  //随机因子
    private $code;  //验证码
    private $codelen = 4; //验证码长度
    private $width = 110; //宽度
    private $height = 30; //高度
    private $img;   //图像资源句柄
    private $font = 2;  //制定字体
    private $fontSize = 25;   //字体大小
    private $fontColor; //字体颜色

    public function __construct(){
//        $this->font="CALIBRIZ.TTF";
    }

    /**
     * set properties of verify code
     *
     * @param int $length
     * @param int $width
     * @param int $height
     */
    public function setCode($length = 4, $width = 110, $height = 30){
        $this->codelen = $length;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * create code
     */
    private function createCode(){
        $len = strlen($this->charset)-1;
        for ($i = 0; $i < $this->codelen; $i++) {
            $this->code .= $this->charset[mt_rand(0,$len)];
        }
    }

    /**
     * create background of code picture
     */
    private function createBg(){
        $this->img=imagecreatetruecolor($this->width,$this->height);
        $color = imagecolorallocate($this->img,mt_rand(157,255),mt_rand(157,255),mt_rand(157,255));
        imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);
    }

    /**
     * create characters on picture
     */
    private function createFont(){
        $x=$this->width/$this->codelen;
        for ($i = 0; $i < $this->codelen; $i++) {
            $this->fontColor=imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
//            imagettftext($this->img,$this->fontSize,mt_rand(-30,30),$i*$x+mt_rand(1,5),$this->height/1.4,$this->fontColor,$this->font,$this->code[$i]);  // www.jb51.net
            imagestring($this->img,5,$i*$x+mt_rand(1,5),5,$this->code[$i],$this->fontColor);
        }
    }

    /**
     * create other design on picture: line and snowflake
     */
    private function createDisturb(){
        for ($i = 0; $i < 6; $i++) {
            $color=imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
            imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->width),mt_rand(0,$this->width),mt_rand(0,$this->width),$color);
        }
        for ($i = 0; $i < 100; $i++) {
            $color=imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
            imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),'*',$color);
        }
    }

    /**
     * output to front page by header
     */
    private function outPut(){
        header("Content-Type:image/png");
        imagepng($this->img);
        imagedestroy($this->img);
    }

    /**
     * start create verify picture
     */
    public function fire(){
        $this->createBg();
        $this->createCode();
        $this->createDisturb();
        $this->createFont();
        $this->outPut();
    }

    /**
     * get the verify code
     *
     * @return string
     */
    public function getCode(){
        return strtolower($this->code);
    }
}
?>