<?php
/**
 *  分页
 *
 *	@author     akiler <532171911@qq.com>
 *	@copyright	 Copyright(c) 2010-2020 by akiler. All rights reserved
 *	@version    1.0
 *	@package    LIB-Ak
 *
 *	@since 2014-03-29 12:01:12
 */
class AkFenYe{
    private $pagecount;//总页数
    private $pagesize=10;//每页显示的记录数
    private $pagenow=1;//当前页
    private $pageindexcount;//总索引数
    private $pageindex=1;//当前索引页
    private $pageindexsize=10;//每页显示索引数
    private $navigation_bar;//导航条
    private $data;//数据
    private $sqlhelper;//数据库连接
    private $pagenowid="pagenow";//当前页url参数名
    private $pageindexid="pageindex";//当前索引页url参数名
    private $data_sql;//查询数据的SQL语句
    private $start="@start";//SQL语句limit占位符
    private $end="@end";//SQL语句limit占位符
    private $url;//连接的地址
    private $pageup;//上翻页连接
    private $nextpage;//下翻页连接
    private $fristpage;//第一页
    private $endpage;//最后一页

    private $pageup_txt="上翻页";//上翻页显示文本
    private $nextpage_txt="下翻页";//下翻页显示文本
    private $firstpage_txt="第一页";//第一页显示文本
    private $endpage_txt="最后一页";//最后一页显示文本


    public function __construct($sql,$url,$pagesize,$pageindexsize){
        $this->pagesize=$pagesize;
        $this->pageindexsize=$pageindexsize;
        $this->sqlhelper=new SqlHelper();
        $this->url=$url;
        $pagecount=$this->sqlhelper->Execute_DQL_Array($sql);
//总页数
        $this->pagecount=ceil($pagecount[0][0]/$this->pagesize);
//总索引数
        $this->pageindexcount=ceil($this->pagecount/$this->pageindexsize);
    }

    public function fenye($data_sql){
        $this->data_sql=$data_sql;
//当前显示的页数
        if(!empty($_GET[$this->pagenowid])){
            $this->pagenow=$_GET[$this->pagenowid];
        }
//当前显示索引
        if(!empty($_GET[$this->pageindexid])){
            $this->pageindex=$_GET[$this->pageindexid];
        }

//生成查询语句
        $this->data_sql=str_replace($this->start, ($this->pagenow-1)*$this->pagesize, $this->data_sql);
        $this->data_sql=str_replace($this->end, $this->pagesize, $this->data_sql);

//获得查询数据
        $this->data=$this->sqlhelper->Execute_DQL($this->data_sql);

        $index=$this->pageindexcount<=1?2:$this->pageindex+$this->pageindexsize;

//生成导航条
        for($j=$this->pageindex;$j<$index;$j++){
            $this->navigation_bar.="<a href='{$this->url}?{$this->pagenowid}=$j&{$this->pageindexid}={$this->pageindex}'>$j</a>";
        }
//生成上翻页与下翻页
        $pagetemp=$this->pageindex;
        if($this->pageindex>1){
            $this->pageindex=$this->pageindex-$this->pageindexsize;
        }
        if($pagetemp<$this->pageindexcount){
            $pagetemp=$pagetemp+$this->pageindexsize;
        }
        $this->pageup="<a href='{$this->url}?$this->pageindexid={$this->pageindex}'>{$this->pageup_txt}</a>";
        $this->nextpage="<a href='{$this->url}?$this->pageindexid={$pagetemp}'>{$this->nextpage_txt}</a>";
//生成第一页与最后一页
        $this->fristpage="<a href='{$this->url}?{$this->pagenowid}=1'>{$this->firstpage_txt}</a>";
        $this->endpage="<a href='{$this->url}?{$this->pagenowid}={$this->pagecount}'>{$this->endpage_txt}</a>";
    }

    /**
     * @return the $pageup_txt
     */
    public function getPageup_txt() {
        return $this->pageup_txt;
    }

    /**
     * @return the $nextpage_txt
     */
    public function getNextpage_txt() {
        return $this->nextpage_txt;
    }

    /**
     * @return the $firstpage_txt
     */
    public function getFirstpage_txt() {
        return $this->firstpage_txt;
    }

    /**
     * @return the $endpage_txt
     */
    public function getEndpage_txt() {
        return $this->endpage_txt;
    }

    /**
     * @param field_type $pageup_txt
     */
    public function setPageup_txt($pageup_txt) {
        $this->pageup_txt = $pageup_txt;
    }

    /**
     * @param field_type $nextpage_txt
     */
    public function setNextpage_txt($nextpage_txt) {
        $this->nextpage_txt = $nextpage_txt;
    }

    /**
     * @param field_type $firstpage_txt
     */
    public function setFirstpage_txt($firstpage_txt) {
        $this->firstpage_txt = $firstpage_txt;
    }

    /**
     * @param field_type $endpage_txt
     */
    public function setEndpage_txt($endpage_txt) {
        $this->endpage_txt = $endpage_txt;
    }

    /**
     * @return the $pageup
     */
    public function getPageup() {
        return $this->pageup;
    }

    /**
     * @return the $nextpage
     */
    public function getNextpage() {
        return $this->nextpage;
    }

    /**
     * @return the $fristpage
     */
    public function getFristpage() {
        return $this->fristpage;
    }

    /**
     * @return the $endpage
     */
    public function getEndpage() {
        return $this->endpage;
    }

    /**
     * @return the $url
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param field_type $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * @return the $data_sql
     */
    public function getData_sql() {
        return $this->data_sql;
    }

    /**
     * @param field_type $data_sql
     */
    public function setData_sql($data_sql) {
        $this->data_sql = $data_sql;
    }

    /**
     * @return the $start
     */
    public function getStart() {
        return $this->start;
    }

    /**
     * @return the $end
     */
    public function getEnd() {
        return $this->end;
    }

    /**
     * @return the $pagenowid
     */
    public function getPagenowid() {
        return $this->pagenowid;
    }

    /**
     * @return the $pageindexid
     */
    public function getPageindexid() {
        return $this->pageindexid;
    }

    /**
     * @param string $pagenowid
     */
    public function setPagenowid($pagenowid) {
        $this->pagenowid = $pagenowid;
    }

    /**
     * @param string $pageindexid
     */
    public function setPageindexid($pageindexid) {
        $this->pageindexid = $pageindexid;
    }

    /**
     * @return the $pagecount
     */
    public function getPagecount() {
        return $this->pagecount;
    }

    /**
     * @return the $pagesize
     */
    public function getPagesize() {
        return $this->pagesize;
    }

    /**
     * @return the $pagenow
     */
    public function getPagenow() {
        return $this->pagenow;
    }

    /**
     * @return the $pageindexcount
     */
    public function getPageindexcount() {
        return $this->pageindexcount;
    }

    /**
     * @return the $pageindex
     */
    public function getPageindex() {
        return $this->pageindex;
    }

    /**
     * @return the $pageindexsize
     */
    public function getPageindexsize() {
        return $this->pageindexsize;
    }

    /**
     * @return the $navigation_bar
     */
    public function getNavigation_bar() {
        return $this->navigation_bar;
    }

    /**
     * @return the $data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param field_type $pagecount
     */
    public function setPagecount($pagecount) {
        $this->pagecount = $pagecount;
    }

    /**
     * @param field_type $pagesize
     */
    public function setPagesize($pagesize) {
        $this->pagesize = $pagesize;
    }

    /**
     * @param field_type $pagenow
     */
    public function setPagenow($pagenow) {
        $this->pagenow = $pagenow;
    }

    /**
     * @param field_type $pageindexcount
     */
    public function setPageindexcount($pageindexcount) {
        $this->pageindexcount = $pageindexcount;
    }

    /**
     * @param field_type $pageindex
     */
    public function setPageindex($pageindex) {
        $this->pageindex = $pageindex;
    }

    /**
     * @param field_type $pageindexsize
     */
    public function setPageindexsize($pageindexsize) {
        $this->pageindexsize = $pageindexsize;
    }

    /**
     * @param field_type $navigation_bar
     */
    public function setNavigation_bar($navigation_bar) {
        $this->navigation_bar = $navigation_bar;
    }

    /**
     * @param field_type $data
     */
    public function setData($data) {
        $this->data = $data;
    }




}