<?php
/**
 *	tree manager
 *
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2020
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2010-09-29 14:06:12
 */
class AkTree
{
	/**
	 * parent id key
	 *
	 * @var string
	 */
	public $parentKey;
	
	/**
	 * child id key
	 *
	 * @var string
	 */
	public $childKey;
	
	/**
	 * id key
	 *
	 * @var string
	 */
	public $idKey;
	
	function __construct($parentKey = 'parentid', $childKey = 'childid', $idKey = 'id'){
		$this->setParentKey($parentKey);
		$this->setChildKey($childKey);
		$this->setIdKey($idKey);
	}
	
	/**
	 * set parent id key
	 *
	 * @param string $key
	 */
	function setParentKey($key) {
		$this->parentKey = $key;
	}
	
	/**
	 * set child id key
	 *
	 * @param string $key
	 */
	function setChildKey($key) {
		$this->childKey = $key;
	}
	
	/**
	 * set id key
	 *
	 * @param string $key
	 */
	function setIdKey($key) {
		$this->idKey = $key;
	}
	
	/**
	 * find child by id
	 *
	 * @param array $arr
	 * @param integer $id
	 * @return array or string
	 */
	protected function findChild(&$arr, $id){
	    $childs = array();
	     foreach ($arr as $k => $v){
	         if($v[$this->parentKey] == $id){
	              $childs[] = $v;
	         }
	    }

	    return $childs;
	}
	
	/**
	 * build tree
	 *
	 * @param array $rows		input data
	 * @param integer $root_id	root id, first parent.
	 * @return array
	 */
	function build($rows, $root_id){
	    $childs = $this->findChild($rows, $root_id);
	    if(empty($childs)){
	        return null;
	    }
	   foreach ($childs as $k => $v){
	       $rescurTree = $this->build($rows, $v[$this->idKey]);
	       if( null != $rescurTree){
	       		$childs[$k][$this->childKey] = $rescurTree;
	       } else {
	       		$childs[$k][$this->childKey] = null;
	       }
	   }
	   
	   return $childs;
	}
}