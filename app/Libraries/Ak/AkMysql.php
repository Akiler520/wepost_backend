<?php
/**
 *	mysql manager
 *
 *	@author     akiler <532171911@qq.com>
 *	@copyright	 Copyright(c) 2010-2020 by akiler. All rights reserved
 *	@version    1.0
 *	@package    LIB-Ak
 *
 *	@since      2010-09-29 14:06:12
 */
class AkMysql
{
    /**
     * host address of database
     * @var
     */
    protected $host;

    /**
     * user name to connect the database
     * @var
     */
    protected $user;

    /**
     * password
     * @var
     */
    protected $pwd;

    /**
     * the name of database
     * @var
     */
    protected $dbName;

    /**
     * prefix of table
     * @var
     */
    protected $dbPrefix;

    /**
     * charset of database
     * @var
     */
    protected $charset;

    /**
     * current executing sql string
     * @var
     */
    protected $sql;

    /**
     * the fields of current selected table
     * @var
     */
    protected $fields;

    /**
     * the connect source of database
     * @var
     */
    protected $connectID;

    /**
     * current selected table
     * @var
     */
    protected $tabName;

    /**
     * if cache the fields of table
     * @var bool
     */
    protected $cache = true;

    /**
     * the path of cache file
     * @var string
     */
    protected $cachePath = 'db_cache';

    /**
     * object of current class
     *
     * @var object
     */
    private static $_instance;

    /**
     * debug
     * @var int
     */
    private $debug = true;

    public function __construct(){

    }

    public function connect($dbHost, $dbUser, $dbPassword, $dbName = '', $pConnect = 0, $dbPrefix = 'ak_', $charSet = 'utf8')
    {
        $this->host = $dbHost;
        $this->user = $dbUser;
        $this->pwd = $dbPassword;
        $this->dbPrefix = $dbPrefix;
        $this->dbName = $dbName;
        $this->charset = $charSet;

        if($pConnect == 1){
            $this->connectID = @mysql_pconnect($dbHost, $dbUser, $dbPassword);
        }else{
            $this->connectID = @mysql_connect($dbHost, $dbUser, $dbPassword, true);
        }

        if(!$this->connectID) $this->halt('Can not connect to MySQL server');

        $this->charset = $charSet;
        $this->setCharset();

        if($dbName && !$this->selectDb()) $this->halt('Cannot use database '.$dbName);

        return $this;
    }

    /**
     * instance of the class
     * to avoid create multiple instance of class
     * @return Model|object
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * execute a sql string and get result data
     *
     * @param $sql
     * @return array|bool
     */
    public function query($sql){
        $row = array();

        $result = mysql_query($sql);

        if($result&&mysql_affected_rows()){
            while($row=mysql_fetch_assoc($result)){
                $rows[]=$row;
            }
        }else{
            return false;
        }

        return $rows;
    }

    /**
     * only execute the sql string, no data return but boolean
     *
     * @param $sql
     * @return bool|int
     */
    public function execute($sql){
        $result=mysql_query($sql);
        if($result&&mysql_affected_rows()){
            return mysql_affected_rows();
        }else{
            return false;
        }
    }

    /**
     * get the fields of current selected table
     *
     * @return array|mixed
     */
    protected function getFields(){
        if(file_exists($this->cachePath.$this->tabName.'.php')){
            return include $this->cachePath.$this->tabName.'.php';
        }else{
            return $this->cacheFields();
        }
    }

    /**
     * cache the fields of current table and return the fields
     *
     * @return array
     */
    protected function cacheFields(){
        $this->sql = 'desc '.$this->tabName;
        $f = $this->query($this->sql);

        $fields = array();
        foreach($f as $key => $value){
            $fields[] = $value['Field'];
            if($value['Key'] == 'PRI'){
                $fields['_pk'] = $value['Field'];
            }
            if($value['Extra'] == 'auto_increment'){
                $fields['_auto'] = $value['Field'];
            }

        }

        $this->writeCache($fields);

        return $fields;
    }

    /**
     * save the fields to static file
     * @param $f
     */
    protected function writeCache($f){
        $string="<?php \n return ".var_export($f,true)."\n ?>";
        file_put_contents($this->cachePath.$this->tabName.'.php',$string);
    }

    /**
     * set the charset of the database
     */
    protected function setCharset(){
        $charSet = $this->charset;

        if($this->version() > '4.1'){
            $serverSet = $charSet ? "character_set_connection='$charSet',character_set_results='$charSet',character_set_client=binary" : '';
            $serverSet .= $this->version() > '5.0.1' ? ((empty($serverSet) ? '' : ',')." sql_mode='' ") : '';
            $serverSet && mysql_query("SET $serverSet", $this->connectID);
        }

//        mysql_set_charset($this->charset);
    }

    /**
     * set the connection of database
     * e.g. if there is another connection of database, we can use the connection by set here.
     *
     * @param $connect
     */
    function setConnection($connect){
        $this->connectID = $connect;
    }

    /**
     * set the name of database
     *
     * @param string $dbName
     * @return bool
     */
    protected function selectDb($dbName = ''){
        if(empty($dbName)){
            $dbName = $this->dbName;
        }

        if(!@mysql_select_db($dbName, $this->connectID)) return false;

        $this->dbName = $dbName;

        return true;
    }

    /**
     * the the name of the table current operated
     * @param $tableName
     */
    public function setTable($tableName){
        $this->tabName = $this->dbPrefix.$tableName;
    }

    /**
     * insert data by array
     *
     * @param $data
     * @return bool|int
     */
    public function insert($data){
        // get keys of new data
        $keys = array_keys($data);

        // get new keys of new data which will be saved
        $newKey = array_intersect($keys, $this->fields);

        $values = array();
        foreach($data as $key => $value){
            if(!in_array($key, $newKey)){
                continue;
            }

            $values[] = $value;
        }

        $this->sql = "INSERT INTO `$this->tabName`(`".implode('`,`', $newKey)."`) VALUES('".implode("','", $values)."')";

        return $this->execute($this->sql);

    }

    /**
     * update data
     *
     * @param $data
     * @param $where
     * @return bool|int
     */
    public function update($data, $where){
        $keys = array_keys($data);
        $newKey = array_intersect($keys, $this->fields);

        $valueParam = '';
        $values = array();

        foreach($data as $key => $value){
            if(!in_array($key, $newKey)){
                continue;
            }

            $valueParam .= ", `$key`='$value'";
            $values[] = $value;
        }

        if($where){
            $valueParam = trim($valueParam, ', ');

            $this->sql = "UPDATE `$this->tabName` SET $valueParam WHERE $where";
        }
        else
        {
            $this->sql = "REPLACE INTO `$this->tabName`(`".implode('`,`', $newKey)."`) VALUES('".implode("','", $values)."')";
        }

        return $this->execute($this->sql);

    }

    /**
     * get data
     *
     * @param string $where
     * @param string $fields
     * @param string $order
     * @param string $limit
     * @param int $result_type
     * @return array|bool
     */
    public function select($where='', $fields = '', $order='', $limit='', $result_type = MYSQL_ASSOC){
        if(empty($fields)){
            $fields=join(',',array_unique($this->fields));
        }else{
            if(is_array($fields)){
                $newKey=array_intersect($fields,$this->fields);
                $fields=join(',',$newKey);
            }
        }
        if(!empty($where)){
            $where=' where '.$where;
        }
        if(!empty($order)){
            $order=' order by '.$order;
        }
        if(!empty($limit)){
            if(is_array($limit)){
                $limit=' limit '.$limit[0].','.$limit[1];
            }else{
                $limit=' limit '.$limit;
            }
        }

        $this->sql = "select $fields from $this->tabName $where $order $limit";

        return $this->query($this->sql);

    }

    /**
     * delete data
     *
     * @param array|string $data
     * @param string $where
     * @return bool|int
     */
    public function delete($data, $where=''){
        //delete from 表 where id=;
        //delete from 表 where id in();
        //delete from 表  order by  limit;
        //delete from 表 where
        if(!empty($where)){
            $where=' where '.$where;

            $this->sql="delete from ".$this->tabName.$where;
        }else{
            if(is_array($data)){
                $data=join(',',$data);
            }
            $fields=$this->fields['_pk'];   // primary key

            $this->sql="delete from ".$this->tabName." where $fields in ($data)";
        }
        return $this->execute($this->sql);
    }

    /**
     * get max one by given field
     *
     * @param $fields
     * @return bool
     */
    public function max($fields){
        if(!in_array($fields,$this->fields)){
            return false;
        }
        $this->sql="select max($fields) as ab from ".$this->tabName;
        $result=$this->query($this->sql);
        return $result[0]['ab'];
    }

    /**
     * get min one by given field
     *
     * @param $fields
     * @return bool
     */
    public function min($fields){
        if(!in_array($fields,$this->fields)){
            return false;
        }
        $this->sql="select min($fields) as ab from ".$this->tabName;
        $result=$this->query($this->sql);
        return $result[0]['ab'];
    }

    /**
     * calculate average by given field
     *
     * @param $fields
     * @return bool
     */
    public function avg($fields){
        if(!in_array($fields,$this->fields)){
            return false;
        }

        $this->sql="select avg($fields) as ab from ".$this->tabName;
        $result=$this->query($this->sql);

        return $result[0]['ab'];
    }

    /**
     * calculate sum by given field
     * @param $fields
     * @return bool
     */
    public function sum($fields){
        if(!in_array($fields,$this->fields)){
            return false;
        }
        $this->sql="select sum($fields) as ab from ".$this->tabName;
        $result=$this->query($this->sql);
        return $result[0]['ab'];
    }

    /**
     * calculate total number of ..
     * @param string $fields
     * @return bool
     */
    public function count($fields=''){
        if(empty($fields)){
            $fields=$this->fields['_pk'];
        }else{
            if(!in_array($fields,$this->fields)){
                return false;
            }
        }

        $this->sql="select count($fields) as ab from ".$this->tabName;
        $result=$this->query($this->sql);
        return $result[0]['ab'];
    }

    /**
     * get the last sql was executed
     *
     * @return mixed
     */
    public function getLastSql(){
        return $this->sql;
    }

    /**
     * get the last id of the one was inserted
     *
     * @return int
     */
    public function getInsertID()
    {
        return mysql_insert_id($this->connectID);
    }

    /**
     * escape special characters of data
     *
     * @param $string
     * @return array|mixed
     */
    public function escape($string)
    {
        if(!is_array($string)) return str_replace(array('\n', '\r'), array(chr(10), chr(13)), mysql_real_escape_string($string, $this->connectID));
        foreach($string as $key=>$val) $string[$key] = $this->escape($val);
        return $string;
    }

    /**
     * get the version of current database
     *
     * @return string
     */
    public function version()
    {
        return mysql_get_server_info($this->connectID);
    }

    /**
     * close the connection of database
     *
     * @return bool
     */
    public function close()
    {
        return mysql_close($this->connectID);
    }

    /**
     * close the connection of database when instance is destroyed
     */
    public function __destruct(){
        $this->close();
    }

    /**
     * get error message
     *
     * @return string
     */
    public function error()
    {
        return @mysql_error($this->connectID).'('.intval(@mysql_errno($this->connectID)).')';
    }

    public function debug($debug){
        $this->debug = $debug;
    }

    /**
     * if $this->debug = true, break when error happen
     *
     * @param string $message
     * @param string $sql
     * @param string $cut
     */
    public function halt($message = '', $sql = '', $cut = '')
    {
        if($this->debug)
        {
            echo "MySQL Error:{$this->error()}{$cut}";
            if(!empty($sql)) echo "MySQL Query:{$sql}{$cut}";
            if(!empty($message)) echo "Message:{$message}{$cut}";
            exit;
        }
    }

    //自动获得字段方法
    public function __call($name,$param){
        if(strtolower(substr($name,0,5))=='getby'){
            $fields=strtolower(substr($name,5));
            $value=$param[0];

            $key=join(',',array_unique($this->fields));

            $this->sql = "SELECT {$key} FROM {$this->tabName} WHERE {$fields}='{$value}'";

            return $this->query($this->sql);
        }
    }
}