<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @author	Jakub Holub
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid;

use NiftyGrid\FilterCondition;

class NDataSource implements IDataSource
{
	private $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getPrimaryKey()
	{
		return $this->data->getPrimary();
	}

	public function getCount($column = "*")
	{
		return $this->data->count($column);
	}

	public function orderData($by, $way)
	{
		$this->data->order($by." ".$way);
	}

	public function limitData($limit, $offset)
	{
		$this->data->limit($limit, $offset);
	}

	public function filterData(array $filters)
	{
		foreach($filters as $filter){
			if($filter["type"] == FilterCondition::WHERE){
				$column = $filter["column"];
				$value = $filter["value"];
				if(!empty($filter["columnFunction"])){
					$column = $filter["columnFunction"]."(".$filter["column"].")";
				}
				$column .= $filter["cond"];
				if(!empty($filter["valueFunction"])){
					$column .= $filter["valueFunction"]."(?)";
				}
				$this->data->where($column, $value);
			}
		}
	}
}
