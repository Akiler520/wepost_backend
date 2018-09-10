<?php
/**
 *  pager
 *
 *  @author     akiler <532171911@qq.com>
 *	@copyright	 Copyright(c) 2010-2020 by akiler. All rights reserved
 *	@version    1.0
 *	@package    LIB-Ak
 *
 *	@since      2014-07-21 15:12:45
 *
 * @usage      $pagerObj = new Ak_Pagination(1000, intval($CurrentPage));   <br>
 *              $pageStr = $pagerObj->getPagination();  <br>
 *              echo $pageStr;  <br>
 */
class AkPagination {
    /**
     * element number of each page
     * @var int
     */
    private $pageSize = 10;

    /**
     * current page number
     * @var
     */
    private $pageIndex;

    /**
     * the total number of the list
     * @var
     */
    private $totalNum;

    /**
     * the total page number of the list
     * @var
     */
    private $totalPagesCount;

    /**
     * the url of page
     * @var
     */
    private $pageUrl;

    /**
     * the number of page bar
     * @var
     */
    private $pageBarNum;

    /**
     * the min number of page bar
     * @var
     */
    private $pageBarMinNum;

    /**
     * the max number of page bar
     * @var
     */
    private $pageBarMaxNum;

    /**
     * other parameters of the url
     * key => value
     * @var array
     */
    private $params = array();

    private $textFirstPage = '首 页';
    private $textNextPage = '下一页';
    private $textPreviousPage = '上一页';
    private $textLastPage = '末 页';

    public function __construct($p_totalNum, $p_pageIndex, $p_pageSize = 10, $p_pageBarNum = 10, $p_pageBarMinNum = 3, $p_pageBarMaxNum = 5) {
        if (! isset ( $p_totalNum ) || !isset($p_pageIndex)) {
            die ( "pager initial error" );
        }

        $this->totalNum = $p_totalNum;
        $this->pageIndex = $p_pageIndex;
        $this->pageSize = $p_pageSize;
        $this->pageBarNum = $p_pageBarNum;
        $this->pageBarMinNum = $p_pageBarMinNum;
        $this->pageBarMaxNum = $p_pageBarMaxNum;
        $this->totalPagesCount = ceil($p_totalNum / $p_pageSize);
        $this->_setPageUrl();

        $this->_initPagerLegal();
    }


    /**
     * get the url of page
     * @return String
     */
    private function _setPageUrl() {
        /*$CurrentUrl = $_SERVER["REQUEST_URI"];
        $arrUrl     = parse_url($CurrentUrl);
        $urlQuery   = $arrUrl["query"];

        if($urlQuery){
            $urlQuery  = preg_replace("(^|&)page=" . $this->pageIndex, "", $urlQuery);  //ereg_replace
            $CurrentUrl = str_replace($arrUrl["query"], $urlQuery, $CurrentUrl);

            if($urlQuery){
                $CurrentUrl.="&page";
            }
            else $CurrentUrl.="page";

        } else {
            $CurrentUrl.="?page";
        }*/

        $webPath = WEB_PATH;
        $controller = Yov_Router::getInstance()->getController();
        $action = Yov_Router::getInstance()->getAction();

        $params = array();

        if(!empty($this->params)){
            foreach($this->params as $key_param => $val_param){
                $params[] = $key_param.'/'.$val_param;
            }
        }

        if(!empty($params)){
            $params = '/'.implode('/', $params);
        }else{
            $params = '';
        }

        $this->pageUrl = $webPath.$controller.'/'.$action.$params.'/page/';

        return true;
    }
    /**
     * get the legal page index
     */
    private function _initPagerLegal()
    {
        if((!is_numeric($this->pageIndex)) ||  $this->pageIndex < 1) {
            $this->pageIndex = 1;
        }elseif($this->pageIndex > $this->totalPagesCount) {
            $this->pageIndex = $this->totalPagesCount;
        }
    }

    public function setParams($params){
        if(empty($params)){
            return false;
        }

        $this->params = $params;

        $this->_setPageUrl();

        return true;
    }

    public function setFirstPageText($text){
        $this->textFirstPage = $text;
    }

    public function setPreviousPageText($text){
        $this->textPreviousPage = $text;
    }

    public function setNextPageText($text){
        $this->textNextPage = $text;
    }

    public function setLastPageText($text){
        $this->textLastPage = $text;
    }

    /**
     * get the start page bar string
     * @return string
     */
    private function getStartPageBar(){
        $str = '';

        //first page and previous page
        if($this->pageIndex == 1)
        {
            $str .="<a href='javascript:void(0)' class='tips' title='{$this->textFirstPage}'>{$this->textFirstPage}</a> "."\n";
            $str .="<a href='javascript:void(0)' class='tips' title='{$this->textPreviousPage}'>{$this->textPreviousPage}</a> "."\n"."\n";
        }else{
            $str .="<a href='{$this->pageUrl}1' class='tips' title='{$this->textFirstPage}'>{$this->textFirstPage}</a> "."\n";
            $str .="<a href='{$this->pageUrl}".($this->pageIndex - 1)."' class='tips' title='{$this->textPreviousPage}'>{$this->textPreviousPage}</a> "."\n"."\n";
        }

        return $str;
    }

    /**
     * get the end page bar string
     * @return string
     */
    private function getEndPageBar(){
        $str = '';

        //get next and last page bar
        if($this->pageIndex == $this->totalPagesCount) {
            $str .="\n"."<a href='javascript:void(0)' class='tips' title='{$this->textNextPage}'>{$this->textNextPage}</a>"."\n" ;
            $str .="<a href='javascript:void(0)' class='tips' title='{$this->textLastPage}'>{$this->textLastPage}</a>"."\n";
        }else{
            $str .="\n"."<a href='{$this->pageUrl}".($this->pageIndex + 1)."' class='tips' title='{$this->textNextPage}'>{$this->textNextPage}</a> "."\n";
            $str .="<a href='{$this->pageUrl}{$this->totalPagesCount}' class='tips' title='{$this->textLastPage}'>{$this->textLastPage}</a> "."\n" ;
        }

        return $str;
    }

    /**
     * get the final page bar string
     * @return string
     */
    public function getPagination() {
        $str = "<div class=\"Pagination\">";

        $str .= $this->getStartPageBar();

        $currnt = "";

        // get dynamic page bar
        // the total page number is not greater than the setted page bar number, try to list all the page bar
        if($this->totalPagesCount <= $this->pageBarNum){
            for($i = 1;$i <= $this->totalPagesCount; $i++){
                if($i == $this->pageIndex){
                    $currnt=" class='current'";
                }else{
                    $currnt="";
                }

                $str .="<a href='{$this->pageUrl}{$i} ' {$currnt}>$i</a>"."\n" ;
            }
        }else{
            // the current page is less than the min number of setted page bar: current page < $this->pageBarMinNum
            if($this->pageIndex < $this->pageBarMinNum){
                for($i = 1; $i <= $this->pageBarMinNum; $i++) {
                    if($i == $this->pageIndex){
                        $currnt = " class='current'";
                    }else{
                        $currnt = "";
                    }
                    $str .= "<a href='{$this->pageUrl}{$i} ' {$currnt}>$i</a>"."\n" ;
                }

                $str.= "<span class=\"dot\">……</span>"."\n";

                for($i = $this->totalPagesCount - $this->pageBarMinNum + 1; $i <= $this->totalPagesCount; $i++){
                    $str .= "<a href='{$this->pageUrl}{$i}' >$i</a>"."\n" ;
                }
            } elseif ($this->pageIndex <= $this->pageBarMaxNum) {
                //   $this->pageBarMaxNum >= current page >= $this->pageBarMinNum
                for($i = 1; $i <= ($this->pageIndex + 1); $i++) {
                    if($i == $this->pageIndex){
                        $currnt = " class='current'";
                    }else{
                        $currnt = "";
                    }

                    $str .= "<a href='{$this->pageUrl}{$i} ' {$currnt}>$i</a>"."\n" ;
                }
                $str .= "<span class=\"dot\">……</span>"."\n";

                for($i = $this->totalPagesCount - $this->pageBarMinNum + 1; $i <= $this->totalPagesCount; $i++){
                    $str .="<a href='{$this->pageUrl}{$i}' >$i</a>"."\n" ;

                }
            }elseif($this->pageBarMaxNum < $this->pageIndex  &&  $this->pageIndex <= $this->totalPagesCount - $this->pageBarMaxNum ){
                // total page - $this->pageBarMaxNum >= current page > $this->pageBarMaxNum,
                for($i=1;$i<=$this->pageBarMinNum;$i++) {
                    $str .="<a href='{$this->pageUrl}{$i}' >$i</a>"."\n" ;
                }

                $str .= "<span class=\"dot\">……</span>";

                for($i = $this->pageIndex - 1 ;$i <= $this->pageIndex + 1 && $i <= $this->totalPagesCount - $this->pageBarMaxNum + 1; $i++){
                    if($i == $this->pageIndex){
                        $currnt = " class='current'";
                    }else{
                        $currnt = "";
                    }

                    $str .= "<a href='{$this->pageUrl}{$i} ' {$currnt}>$i</a>"."\n" ;
                }
                $str .= "<span class=\"dot\">……</span>";

                for($i = $this->totalPagesCount - $this->pageBarMinNum + 1; $i <= $this->totalPagesCount; $i++) {
                    $str .= "<a href='{$this->pageUrl}{$i}' >$i</a>"."\n" ;
                }
            }else{
                for($i = 1; $i <= $this->pageBarMinNum; $i++){
                    $str .="<a href='{$this->pageUrl}{$i}' >$i</a>"."\n" ;
                }

                $str .= "<span class=\"dot\">……</span>"."\n";

                for($i = $this->totalPagesCount - $this->pageBarMaxNum; $i <= $this->totalPagesCount; $i++){
                    if($i == $this->pageIndex){
                        $currnt = " class='current'";
                    }else{
                        $currnt = "";
                    }

                    $str .= "<a href='{$this->pageUrl}{$i} ' {$currnt}>$i</a>"."\n" ;
                }
            }
        }

        $str .= $this->getEndPageBar();

        $str .= "</div>";

        return $str;
    }
}
?>