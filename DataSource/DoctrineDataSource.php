<?php
/**
 * Doctrine DataSource for NiftyGrid - DataGrid for Nette
 *
 * @author	Nikolas Tsiongas
 * @copyright	Copyright (c) 2012 Nikolas Tsiongas
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid;

use NiftyGrid\FilterCondition,
	Nette\Utils\Strings;

use Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrineDataSource implements IDataSource
{
	private $qb;

	public function __construct($qb, array $aliases)
	{
		// Query builder
		$this->qb = $qb;

		// Mapped column to entities
		/**
		 * array("name for grid (alphanumeric)" => "name for doctrine, as query builder")
		 * exaple: array("published" => "a.published")
		 */
		$this->aliases = $aliases;
	}

	public function getQuery()
	{
		return $this->qb->getQuery();
	}

	public $aliases = array();


	public function getData()
	{
		return $this->getQuery()->getArrayResult();
	}

	public function getCount($column = "*")
	{
        return $this->getSelectedRowsCount();
	}

	public function getSelectedRowsCount()
	{
        $paginator = new Paginator($this->getQuery());

        return $paginator->count();
	}

	public function orderData($by, $way)
	{
		$this->qb->orderBy($this->aliases[$by], $way);
	}

	public function limitData($limit, $offset)
	{
		$this->qb->setFirstResult($offset)
				 ->setMaxResults($limit);
	}

	public function filterData(array $filters)
	{
		foreach($filters as $filter){
			if($filter["type"] == FilterCondition::WHERE){

				$column = $this->aliases[$filter['column']];
				$value = $filter["value"];
				$expr = $this->qb->expr();
				$cond = false;

				switch($filter['cond']) {
					case ' LIKE ?':
						$cond = $expr->like($column, $expr->literal($value));
						break;

					case ' = ?':
						$cond = $expr->eq($column, $expr->literal($value));
						break;

					case ' > ?':
						$cond = $expr->gt($column, $expr->literal($value));
						break;

					case ' >= ?':
						$cond = $expr->gte($column, $expr->literal($value));
						break;

					case ' < ?':
						$cond = $expr->lt($column, $expr->literal($value));
						break;

					case ' <= ?':
						$cond = $expr->lte($column, $expr->literal($value));
						break;

					case ' <> ?':
						$cond = $expr->neq($column, $expr->literal($value));
						break;
				}

				if(!$cond) {
					try {
						$datetime = new \DateTime($value);
						$value = $datetime->format('Y-m-d H:i:s');
					} catch(\Exception $e) {}

					if(isset($datetime)) {
						switch($filter['cond']) {
							/** Dates */
							case ' = ':
								$cond = $expr->like($column, $expr->literal($datetime->format('Y-m-d') . '%'));
								break;

							case ' > ':
								$cond = $expr->gt($column, $expr->literal($value));
								break;

							case ' >= ':
								$cond = $expr->gte($column, $expr->literal($value));
								break;

							case ' < ':
								$cond = $expr->lt($column, $expr->literal($value));
								break;

							case ' <= ':
								$cond = $expr->lte($column, $expr->literal($value));
								break;

							case ' <> ':
								$cond = $expr->neq($column, $expr->literal($value));
								break;
						}
					}
				}

				if($cond) {
					$this->qb->andWhere($cond);
				}

			}elseif($filter["type"] == FilterCondition::HAVING){
				$having[$this->aliases[$filter["column"]]] = $filter;
			}
		}

		if(!empty($having)){
			$stringHaving = "";
			$i = new \Nette\Iterators\CachingIterator($having);
			foreach($i as $filter){
				if(!empty($filter["columnFunction"])){
					$stringHaving .= $filter["columnFunction"]."(".$this->aliases[$filter["column"]].")".$filter["cond"]."'".Strings::upper($filter["value"])."'";
				}else{
					$stringHaving .= $this->aliases[$filter["column"]].$filter["cond"]."'".Strings::upper($filter["value"])."'";
				}
				if(!$i->isLast()){
					$stringHaving .= " AND ";
				}
			}
			$this->qb->groupBy($stringHaving);
		}
	}
}
