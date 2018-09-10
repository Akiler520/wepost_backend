<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Base extends Model
{
    /**
     * init object of db
     * @var null
     */
    protected $_DB = null;


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->_DB = app("db");
    }

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp()
    {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value)
    {
        return $value;
    }

	/**
    * Get cst to pdt time
    *
	* @param Time $time
	* @return Time $time
	*
	*/
	function cst2pdt($time){
		$default = strtolower(date_default_timezone_get());

		if (!is_numeric($time)) {
			$time = strtotime($time);
		}

		if ($default == 'asia/shanghai') {
			$time = $time - 15*3600 ;
		} else if($default == 'etc/utc') {
			$time = $time - 7*3600 ; ;
		}

		return $time;
	}

	/**
    * Get model object using model name
    *
	* @param String $modelName
	* @return Object $model
	*
	*/
	public function getModel($modelName) {
		$modelName 	= 'App\\Models\\'.$modelName;
		$model 		= new $modelName();

		return $model;
	}

	/**
    * Get table name using model name
    *
	* @param String $modelName
	* @return String
	*
	*/
	public function getTableName($modelName) {
		$model = $this->getModel($modelName);

		return $model->getTable();
	}

	/**
    * Get table columns using table name
    *
	* @param String $tableName
	* @return Array table columns
	*
	*/
	public function getTableColumns($tableName) {
		return Schema::getColumnListing($tableName);
	}
}
