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

use NiftyGrid\FilterCondition,
	Nette\Utils\Strings;

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

	public function getCount($column = "*")
	{
		return $this->data->count($column);
	}

	public function getSelectedRowsCount()
	{
		return $this->data->count();
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
			}elseif($filter["type"] == FilterCondition::HAVING){
				$having[$filter["column"]] = $filter;
			}
		}

		if(!empty($having)){
			$stringHaving = "";
			$i = new \Nette\Iterators\CachingIterator($having);
			foreach($i as $filter){
				if(!empty($filter["columnFunction"])){
					$stringHaving .= $filter["columnFunction"]."(".$filter["column"].")".$filter["cond"]."'".Strings::upper($filter["value"])."'";
				}else{
					$stringHaving .= $filter["column"].$filter["cond"]."'".Strings::upper($filter["value"])."'";
				}
				if(!$i->isLast()){
					$stringHaving .= " AND ";
				}
			}
			$this->data->group("id", $stringHaving);
		}
	}
}
