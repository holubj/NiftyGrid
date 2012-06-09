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

class Grid extends \Nette\Application\UI\Control
{
	const ROW_FORM = "rowForm";

	/** @persistent array */
	public $filter;

	/** @persistent string */
	public $order;

	/** @persistent int */
	public $perPage;

	/** @persistent int */
	public $activeSubGridId;

	/** @persistent string */
	public $activeSubGridName;

	/** @var array */
	protected $perPageValues = array(20 => 20, 50 => 50, 100 => 100);

	/** @var bool */
	public $paginate = TRUE;

	/** @var string */
	protected $defaultOrder;

	/** @var IDataSource */
	protected $dataSource;

	/** @var int */
	protected $count;

	/** @var string */
	public $width;

	/** @var bool */
	public $enableSorting = TRUE;

	/** @var int */
	public $activeRowForm;

	/** @var callback */
	public $rowFormCallback;

	/** @var bool */
	public $isSubGrid = FALSE;

	/** @var array */
	public $subGrids = array();

	/** @var callback */
	public $afterConfigureSettings;

	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);

		$this->addComponent(New \Nette\ComponentModel\Container(), "columns");
		$this->addComponent(New \Nette\ComponentModel\Container(), "buttons");
		$this->addComponent(New \Nette\ComponentModel\Container(), "actions");
		$this->addComponent(New \Nette\ComponentModel\Container(), "subGrids");

		if($presenter->isAjax()){
			$this->invalidateControl();
		}

		$this->configure($presenter);

		if($this->isSubGrid && !empty($this->afterConfigureSettings)){
			call_user_func($this->afterConfigureSettings, $this);
		}

		if($this->hasActiveSubGrid()){
			$subGrid = $this->addComponent($this['subGrids']->components[$this->activeSubGridName]->getGrid(), "subGrid".$this->activeSubGridName);
			$subGrid->registerSubGrid("subGrid".$this->activeSubGridName);
		}

		if($this->hasActionForm()){
			$actions = array();
			foreach($this['actions']->components as $name => $action){
				$actions[$name] = $action->getAction();
			}
			$this['gridForm'][$this->name]['action']['action_name']->setItems($actions);
		}
		if($this->paginate){
			if($this->hasActiveItemPerPage()){
				if(in_array($this->perPage, $this['gridForm'][$this->name]['perPage']['perPage']->items)){
					$this['gridForm'][$this->name]['perPage']->setDefaults(array("perPage" => $this->perPage));
				}else{
					$items = $this['gridForm'][$this->name]['perPage']['perPage']->getItems();
					$this->perPage = reset($items);
				}
			}else{
				$items = $this['gridForm'][$this->name]['perPage']['perPage']->getItems();
				$this->perPage = reset($items);
			}
			$this->getPaginator()->itemsPerPage = $this->perPage;
		}
		if($this->hasActiveFilter()){
			$this->filterData();
			$this['gridForm'][$this->name]['filter']->setDefaults($this->filter);
		}
		if($this->hasActiveOrder() && $this->hasEnabledSorting()){
			$this->orderData($this->order);
		}
		if(!$this->hasActiveOrder() && $this->hasDefaultOrder() && $this->hasEnabledSorting()){
			$order = explode(" ", $this->defaultOrder);
			$this->dataSource->orderData($order[0], $order[1]);
		}
		$this->count = $this->getCount();
	}

	/**
	 * @param string $subGrid
	 */
	public function registerSubGrid($subGrid)
	{
		if(!$this->isSubGrid){
			$this->subGrids[] = $subGrid;
		}else{
			$this->parent->registerSubGrid($this->name."-".$subGrid);
		}
	}

	/**
	 * @return array
	 */
	public function getSubGrids()
	{
		if($this->isSubGrid){
			return $this->parent->getSubGrids();
		}else{
			return $this->subGrids;
		}
	}

	/**
	 * @param null|string $gridName
	 * @return string
	 */
	public function getGridPath($gridName = NULL)
	{
		if(empty($gridName)){
			$gridName = $this->name;
		}else{
			$gridName = $this->name."-".$gridName;
		}
		if($this->isSubGrid){
			return $this->parent->getGridPath($gridName);
		}else{
			return $gridName;
		}
	}

	public function findSubGridPath($gridName)
	{
		foreach($this->subGrids as $subGrid){
			$path = explode("-", $subGrid);
			if(end($path) == $gridName){
				return $subGrid;
			}
		}
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @param null|string $width
	 * @param null|int $truncate
	 * @return Column
	 * @throws DuplicateColumnException
	 * @return \Nifty\Grid\Column
	 */
	protected function addColumn($name, $label = NULL, $width = NULL, $truncate = NULL)
	{
		if(!empty($this['columns']->components[$name])){
			throw new DuplicateColumnException("Column $name already exists.");
		}
		$column = new Column($this['columns'], $name);
		$column->setName($name)
			->setLabel($label)
			->setWidth($width)
			->setTruncate($truncate)
			->injectParent($this);

		return $column;
	}

	/**
	 * @param string $name
	 * @return Button
	 * @throws DuplicateButtonException
	 */
	protected function addButton($name, $label = NULL)
	{
		if($name == self::ROW_FORM){
			$button = new Button($this['buttons'], $name);
			$self = $this;
			$button->setLink(function($row) use($self){
				return $self->link("showRowForm!", $row['id']);
			});
		}else{
			if(!empty($this['buttons']->components[$name])){
				throw new DuplicateButtonException("Button $name already exists.");
			}
			$button = new Button($this['buttons'], $name);
		}
		$button->setLabel($label);
		return $button;
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @return Action
	 * @throws DuplicateActionException
	 */
	public function addAction($name, $label = NULL)
	{
		if(!empty($this['actions']->components[$name])){
			throw new DuplicateActionException("Action $name already exists.");
		}
		$action = new Action($this['actions'], $name);
		$action->setName($name)
			->setLabel($label);

		return $action;
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @return SubGrid
	 * @throws DuplicateSubGridException
	 */
	public function addSubGrid($name, $label = NULL)
	{
		if(!empty($this['subGrids']->components[$name]) || in_array($name, $this->getSubGrids())){
			throw new DuplicateSubGridException("SubGrid $name already exists.");
		}
		$self = $this;
		$subGrid = new SubGrid($this['subGrids'], $name);
		$subGrid->setName($name)
			->setLabel($label);
		if($this->activeSubGridName == $name){
			$subGrid->setClass("grid-subgrid-close");
			$subGrid->setClass(function($row) use ($self){
				return $row['id'] == $self->activeSubGridId ? "grid-subgrid-close" : "grid-subgrid-open";
			});
			$subGrid->setLink(function($row) use ($self, $name){
				$link = $row['id'] == $self->activeSubGridId ? array("activeSubGridId" => NULL, "activeSubGridName" => NULL) : array("activeSubGridId" => $row['id'], "activeSubGridName" => $name);
				return $self->link("this", $link);
			});
		}
		else{
			$subGrid->setClass("grid-subgrid-open")
			->setLink(function($row) use ($self, $name){
				return $self->link("this", array("activeSubGridId" => $row['id'], "activeSubGridName" => $name));
			});
		}
		return $subGrid;
	}

	/**
	 * @return array
	 */
	public function getColumnNames()
	{
		foreach($this['columns']->components as $column){
			$columns[] = $column->name;
		}
		return $columns;
	}

	/**
	 * @return int $count
	 */
	public function getColsCount()
	{
		$count = count($this['columns']->components);
		$this->hasActionForm() ? $count++ : $count;
		($this->hasButtons() || $this->hasFilterForm()) ? $count++ : $count;
		$count += count($this['subGrids']->components);

		return $count;
	}

	/**
	 * @param IDataSource $dataSource
	 */
	protected function setDataSource(IDataSource $dataSource)
	{
		$this->dataSource = $dataSource;
	}

	/**
	 * @param string $width
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}

	/**
	 * @param string $order
	 */
	public function setDefaultOrder($order)
	{
		$this->defaultOrder = $order;
	}

	/**
	 * @param array $values
	 * @return array
	 */
	protected function setPerPageValues(array $values)
	{
		$perPageValues = array();
		foreach($values as $value){
			$perPageValues[$value] = $value;
		}
		$this->perPageValues = $perPageValues;
	}

	/**
	 * @return bool
	 */
	public function hasButtons()
	{
		return count($this['buttons']->components) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasFilterForm()
	{
		foreach($this['columns']->components as $column){
			if(!empty($column->filterType))
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActionForm()
	{
		return count($this['actions']->components) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActiveFilter()
	{
		return count($this->filter) ? TRUE : FALSE;
	}

	/**
	 * @param string $filter
	 * @return bool
	 */
	public function isSpecificFilterActive($filter)
	{
		return !empty($this->filter[$filter]) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActiveOrder()
	{
		return !empty($this->order) ? TRUE: FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasDefaultOrder()
	{
		return !empty($this->defaultOrder) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasEnabledSorting()
	{
		return $this->enableSorting;
	}

	/**
	 * @return bool
	 */
	public function hasActiveItemPerPage()
	{
		return !empty($this->perPage) ? TRUE : FALSE;
	}

	public function hasActiveRowForm()
	{
		return !empty($this->activeRowForm) ? TRUE : FALSE;
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function columnExists($column)
	{
		return isset($this['columns']->components[$column]);
	}

	/**
	 * @param string $subGrid
	 * @return bool
	 */
	public function subGridExists($subGrid)
	{
		return isset($this['subGrids']->components[$subGrid]);
	}

	/**
	 * @return bool
	 */
	public function isEditable()
	{
		foreach($this['columns']->components as $component){
			if($component->editable)
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActiveSubGrid()
	{
		return (!empty($this->activeSubGridId) && !empty($this->activeSubGridName) && $this->subGridExists($this->activeSubGridName)) ? TRUE : FALSE;
	}

	/**
	 * @return mixed
	 * @throws InvalidFilterException
	 * @throws UnknownColumnException
	 * @throws UnknownFilterException
	 */
	protected function filterData()
	{
		try{
			$filters = array();
			foreach($this->filter as $name => $value){
				if(!$this->columnExists($name)){
					throw new UnknownColumnException("Neexistující sloupec $name");

				}
				if(!$this['columns-'.$name]->hasFilter()){
					throw new UnknownFilterException("Neexistující filtr pro sloupec $name");
				}

				$alias = $this['columns']->components[$name]->isAlias();
				$type = $this['columns-'.$name]->getFilterType();
				$filter = FilterCondition::prepareFilter($value, $type);

				if(method_exists("\\NiftyGrid\\FilterCondition", $filter["condition"])){
					$filter = call_user_func("\\NiftyGrid\\FilterCondition::".$filter["condition"], $filter["value"]);
					if(!empty($this['gridForm'][$this->name]['filter'][$name])){
						$filter["column"] = $name;
						if($alias){
							$filter["type"] = FilterCondition::HAVING;
							$filter["cond"] = str_replace("?","", $filter["cond"]);
						}
						$filters[] = $filter;
					}else{
						throw new InvalidFilterException("Neplatný filtr");
					}
				}else{
					throw new InvalidFilterException("Neplatný filtr");
				}
			}
			return $this->dataSource->filterData($filters);
		}
		catch(UnknownColumnException $e){
			$this->flashMessage($e->getMessage(), "grid-error");
			$this->redirect("this", array("filter" => NULL));
		}
		catch(UnknownFilterException $e){
			$this->flashMessage($e->getMessage(), "grid-error");
			$this->redirect("this", array("filter" => NULL));
		}
	}

	/**
	 * @param string $order
	 * @throws InvalidOrderException
	 */
	protected function orderData($order)
	{
		try{
			$order = explode(" ", $order);
			if(in_array($order[0], $this->getColumnNames()) && in_array($order[1], array("ASC", "DESC")) && $this['columns']->components[$order[0]]->isSortable()){
				$this->dataSource->orderData($order[0], $order[1]);
			}else{
				throw new InvalidOrderException("Neplatné seřazení.");
			}
		}
		catch(InvalidOrderException $e){
			$this->flashMessage($e->getMessage(), "grid-error");
			$this->redirect("this", array("order" => NULL));
		}
	}

	/**
	 * @return int
	 */
	protected function getCount()
	{
		if($this->paginate){
			if($this->hasActiveFilter()){
				$count = $this->dataSource->getSelectedRowsCount();
				$this->getPaginator()->itemCount = $count;
				$this->dataSource->limitData($this->getPaginator()->itemsPerPage, $this->getPaginator()->offset);
				return $count;
			}else{
				$count = $this->dataSource->getCount();
				$this->getPaginator()->itemCount = $count;
				$this->dataSource->limitData($this->getPaginator()->itemsPerPage, $this->getPaginator()->offset);
				return $count;
			}
		}else{
			$count = $this->dataSource->getCount();
			$this->getPaginator()->itemCount = $count;
			return $count;
		}
	}

	/**
	 * @return GridPaginator
	 */
	protected function createComponentPaginator()
	{
		return  new GridPaginator;
	}

	/**
	 * @return \Nette\Utils\Paginator
	 */
	public function getPaginator()
	{
		return $this['paginator']->paginator;
	}

	/**
	 * @param int $page
	 */
	public function handleChangeCurrentPage($page)
	{
		if($this->presenter->isAjax()){
			$this->redirect("this", array("paginator-page" => $page));
		}
	}

	/**
	 * @param int $perPage
	 */
	public function handleChangePerPage($perPage)
	{
		if($this->presenter->isAjax()){
			$this->redirect("this", array("perPage" => $perPage));
		}
	}

	/**
	 * @param string $column
	 * @param string $term
	 */
	public function handleAutocomplete($column, $term)
	{
		if($this->presenter->isAjax()){
			if(!empty($this['columns']->components[$column]) && $this['columns']->components[$column]->autocomplete){
				$this->filter[$column] = $term."%";
				$this->filterData();
				$this->dataSource->limitData($this['columns']->components[$column]->getAutocompleteResults(), NULL);
				$data = $this->dataSource->getData();
				$results = array();
				foreach($data as $row){
					$value = $row[$column];
					if(!in_array($value, $results)){
						$results[] = $row[$column];
					}
				}
				$this->presenter->payload->payload = $results;
				$this->presenter->sendPayload();
			}
		}
	}

	/**
	 * @param int $id
	 */
	public function handleShowRowForm($id)
	{
		$this->activeRowForm = $id;
	}

	/**
	 * @param $callback
	 */
	public function setRowFormCallback($callback)
	{
		$this->rowFormCallback = $callback;
	}

	/**
	 * @param int $id
	 * @return \Nette\Forms\Controls\Checkbox
	 */
	public function assignCheckboxToRow($id)
	{
		$this['gridForm'][$this->name]['action']->addCheckbox("row_".$id);
		$this['gridForm'][$this->name]['action']["row_".$id]->getControlPrototype()->class[] = "grid-action-checkbox";
		return $this['gridForm'][$this->name]['action']["row_".$id]->getControl();
	}

	protected function createComponentGridForm()
	{
		$form = new \Nette\Application\UI\Form;
		$form->method = "POST";
		$form->getElementPrototype()->class[] = "grid-gridForm";

		$form->addContainer($this->name);

		$form[$this->name]->addContainer("rowForm");
		$form[$this->name]['rowForm']->addSubmit("send","Uložit");
		$form[$this->name]['rowForm']['send']->getControlPrototype()->addClass("grid-editable");

		$form[$this->name]->addContainer("filter");
		$form[$this->name]['filter']->addSubmit("send","Filtrovat");

		$form[$this->name]->addContainer("action");
		$form[$this->name]['action']->addSelect("action_name","Označené:");
		$form[$this->name]['action']->addSubmit("send","Potvrdit")
			->getControlPrototype()
			->addData("select", $form[$this->name]["action"]["action_name"]->getControl()->name);

		$form[$this->name]->addContainer('perPage');
		$form[$this->name]['perPage']->addSelect("perPage","Záznamů na stranu:", $this->perPageValues)
			->getControlPrototype()
			->addClass("grid-changeperpage")
			->addData("gridname", $this->getGridPath())
			->addData("link", $this->link("changePerPage!"));
		$form[$this->name]['perPage']->addSubmit("send","Ok")->getControlPrototype()->addClass("grid-perpagesubmit");

		$form->onSuccess[] = callback($this, "processGridForm");

		return $form;
	}

	/**
	 * @param array $values
	 */
	public function processGridForm($values)
	{
		$values = $values->getHttpData();
		foreach($values as $gridName => $grid){
			foreach($grid as $section => $container){
				foreach($container as $key => $value){
					if($key == "send"){
						unset($container[$key]);
						$subGrids = $this->subGrids;
						foreach($subGrids as $subGrid){
							$path = explode("-", $subGrid);
							if(end($path) == $gridName){
								$gridName = $subGrid;
								break;
							}
						}
						//$gridName = $this->findSubGridPath($gridName);

						if($section == "filter"){
							$this->filterFormSubmitted($values);
						}
						$section = ($section == "rowForm") ? "row" : $section;
						if(method_exists($this, $section."FormSubmitted")){
							call_user_func("self::".$section."FormSubmitted", $container, $gridName);
						}else{
							$this->redirect("this");
						}
						break 3;
					}
				}
			}
		}
	}

	/**
	 * @param array $values
	 * @param string $gridName
	 */
	public function rowFormSubmitted($values, $gridName)
	{
		$subGrid = ($gridName == $this->name) ? FALSE : TRUE;
		if($subGrid){
			call_user_func($this[$gridName]->rowFormCallback, (array) $values);
		}else{
			call_user_func($this->rowFormCallback, (array) $values);
		}
		$this->redirect("this");
	}

	/**
	 * @param array $values
	 * @param string $gridName
	 */
	public function perPageFormSubmitted($values, $gridName)
	{
		$perPage = ($gridName == $this->name) ? "perPage" : $gridName."-perPage";

		$this->redirect("this", array($perPage => $values["perPage"]));
	}

	/**
	 * @param array $values
	 * @param string $gridName
	 * @throws NoRowSelectedException
	 */
	public function actionFormSubmitted($values, $gridName)
	{
		try{
			$rows = array();
			foreach($values as $name => $value){
				if(\Nette\Utils\Strings::startsWith($name, "row")){
					$vals = explode("_", $name);
					if((boolean) $value){
						$rows[] = $vals[1];
					}
				}
			}
			$subGrid = ($gridName == $this->name) ? FALSE : TRUE;
			if(!count($rows)){
				throw new NoRowSelectedException("Nebyl vybrán žádný záznam.");
			}
			if($subGrid){
				call_user_func($this[$gridName]['actions']->components[$values['action_name']]->getCallback(), $rows);
			}else{
				call_user_func($this['actions']->components[$values['action_name']]->getCallback(), $rows);
			}
			$this->redirect("this");
		}
		catch(NoRowSelectedException $e){
			if($subGrid){
				$this[$gridName]->flashMessage("Nebyl vybrán žádný záznam.","grid-error");
			}else{
				$this->flashMessage("Nebyl vybrán žádný záznam.","grid-error");
			}
			$this->redirect("this");
		}
	}

	/**
	 * @param array $values
	 */
	public function filterFormSubmitted($values)
	{
		$filters = array();
		$paginators = array();
		foreach($values as $gridName => $grid){
			foreach($grid['filter'] as $name => $value){
				if(!empty($value)){
					if($name == "send"){
						continue;
					}
					$isSubGrid = ($gridName == $this->name) ? FALSE : TRUE;
					if($isSubGrid){
						$gridName = $this->findSubGridPath($gridName);
						$filters[$this->name."-".$gridName."-filter"][$name] = $value;
					}else{
						$filters[$this->name."-filter"][$name] = $value;
					}
				}
			}
			$paginators[$gridName."-paginator-page"] = NULL;
		}
		$this->presenter->redirect("this", array_merge($filters, $paginators));
	}

	public function render()
	{
		$this->getPaginator()->itemCount = $this->count;
		$this->template->results = $this->count;
		$this->template->columns = $this['columns']->components;
		$this->template->buttons = $this['buttons']->components;
		$this->template->subGrids = $this['subGrids']->components;
		$this->template->paginate = $this->paginate;
		$this->template->colsCount = $this->getColsCount();
		$rows = $this->dataSource->getData();
		$this->template->rows = $rows;
		if($this->hasActiveRowForm()){
			$row = $rows[$this->activeRowForm];
			foreach($row as $name => $value){
				if($this->columnExists($name) && !empty($this['columns']->components[$name]->formRenderer)){
					$row[$name] = call_user_func($this['columns']->components[$name]->formRenderer, $row);
				}
				if(isset($this['gridForm'][$this->name]['rowForm'][$name])){
					$input = $this['gridForm'][$this->name]['rowForm'][$name];
					if($input instanceof \Nette\Forms\Controls\SelectBox){
						$items = $this['gridForm'][$this->name]['rowForm'][$name]->getItems();
						if(in_array($row[$name], $items)){
							$row[$name] = array_search($row[$name], $items);
						}
					}
				}
			}
			$this['gridForm'][$this->name]['rowForm']->setDefaults($row);
			$this['gridForm'][$this->name]['rowForm']->addHidden("id", $this->activeRowForm);
		}
		if($this->paginate){
			$this->template->viewedFrom = ((($this->getPaginator()->getPage()-1)*$this->perPage)+1);
			$this->template->viewedTo = ($this->getPaginator()->getLength()+(($this->getPaginator()->getPage()-1)*$this->perPage));
		}
		$this->template->setFile(__DIR__."/templates/grid.latte");
		$this->template->render();
	}
}