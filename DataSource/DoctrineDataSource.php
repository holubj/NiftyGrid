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

	private $primary;

	public function __construct($qb, $primary)
	{
		// Query builder
		$this->qb = $qb;

		// Primary id
		$this->primary = $primary;
	}

	public function getQuery()
	{
		return $this->qb->getQuery();
	}


	public function getData()
	{
		$result = array();
		foreach($this->getQuery()->getScalarResult() as $values) {
			$id = $result[$values[$this->primary]]['id'] = $values[$this->primary];

			foreach($values as $column => $value) {
				$result[$id][$column] = $value;
			}
		}

		return $result;
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
		$this->qb->orderBy($this->columnName($by), $way);
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

				$column = $this->columnName($filter['column']);
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

			}
		}
	}

	private function columnName($full)
	{
		$name = explode("_", $full);
		$entity = $name[0];
		unset($name[0]);
		return $entity.".".implode("_", $name);
	}
}
