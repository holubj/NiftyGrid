<?php

namespace NiftyGrid;

use Nette,
	DibiFluent;



/**
 * DibiFluent datasource for Nifty's grid.
 *
 * <code>
 * $db = new DibiConnection($dbConfig);
 * $fluent = $db->select('id, name, surname')->from('employee')->where('is_active = %b', TRUE);
 * $dataSource = new DibiFluentDataSource($fluent, 'id');
 * </code>
 *
 * <code>
 * $dataSource = new DibiFluentDataSource(dibi::select('*')->from('employee'), 'id');
 * </code>
 *
 * @author  Miloslav Hůla
 * @version 1.1
 * @licence LGPL
 * @see https://github.com/Niftyx/NiftyGrid
 */
class DibiFluentDataSource extends Nette\Object implements IDataSource
{
	/** @var DibiFluent */
	private $fluent;



	/** @var string  Primary key column name */
	private $pKeyColumn;



	/** @var int  LIMIT clause value */
	private $limit;


	/** @var int  OFFSET clause value */
	private $offset;



	/**
	 * @param DibiFluent
	 * @param string  Primary key column name
	 */
	public function __construct(DibiFluent $fluent, $pKeyColumn)
	{
		$this->fluent = clone $fluent;
		$this->pKeyColumn = $pKeyColumn;
	}



	/* --- NiftyGrid\IDataSource implementation ----------------------------- */



	public function getData()
	{
		return $this->fluent->getConnection()->query('%SQL %lmt %ofs', (string) $this->fluent, $this->limit, $this->offset)->fetchAssoc($this->pKeyColumn);
	}



	public function getPrimaryKey()
	{
		return $this->pKeyColumn;
	}



	public function getCount($column = '*')
	{
		$fluent = clone $this->fluent;
		$fluent->removeClause('SELECT')->removeClause('ORDER BY');

		$modifiers = \DibiFluent::$modifiers;
		\DibiFluent::$modifiers['SELECT'] = '%sql';
		$fluent->select(array('COUNT(%n)', $column));
		\DibiFluent::$modifiers = $modifiers;

		return $fluent->fetchSingle();
	}



	public function orderData($by, $way)
	{
		$this->fluent->orderBy(array($by => $way));
	}



	public function limitData($limit, $offset)
	{
		$this->limit = $limit;
		$this->offset = $offset;
	}



	public function filterData(array $filters)
	{
		static $typeToModifier = array(
			FilterCondition::NUMERIC => '%f',
			FilterCondition::DATE => '%d',
		);

		$where = array();
		foreach ($filters as $filter) {
			$cond = array();

			// Column
			if (isset($filter['columnFunction'])) {
				$cond[] = $filter['columnFunction'] . '(';
			}

			$cond[] = '%n';
			$cond[] = $filter['column'];

			if (isset($filter['columnFunction'])) {
				$cond[] = ')';
			}


			// Operator
			$cond[] = trim(strtoupper(str_replace('?', '', $filter['cond'])));


			// Value
			if (isset($filter['valueFunction'])) {
				$cond[] = $filter['valueFunction'] . '(';
			}

			$cond[] = isset($typeToModifier[$filter['datatype']]) ? $typeToModifier[$filter['datatype']] : '%s';
			$cond[] = $filter['value'];

			if (isset($filter['valueFunction'])) {
				$cond[] = ')';
			}

			if ($filter['type'] === FilterCondition::WHERE) {
				$where[] = $cond;

			} else {
				trigger_error("Unknown filter type '$filter[type]'.", E_USER_NOTICE);
			}
		}

		if (count($where)) {
			$this->fluent->where($where);
		}
	}

}
