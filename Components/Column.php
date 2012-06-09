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


class Column extends \Nette\Application\UI\PresenterComponent
{
	/** @var string */
	public $name;

	/** @var callback|string */
	public $label;

	/** @var string */
	public $width;

	/** @var int */
	public $truncate;

	/** @var callback */
	public $renderCallback;

	/** @var callback */
	public $formRenderer;

	/** @var callback|string */
	public $cellRenderer;

	/** @var bool */
	public $sortable = TRUE;

	/** @var bool */
	public $alias = FALSE;

	/** @var string */
	public $filterType;

	/** @var bool */
	public $autocomplete = FALSE;

	/** @var int */
	public $autocompleteResults = 10;

	/** @var bool */
	public $editable = FALSE;

	/** @var Grid */
	public $parent;

	/**
	 * @param Grid $parent
	 */
	public function injectParent(Grid $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @param string $name
	 * @return Column
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @param callback|string $label
	 * @return Column
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @param string $width
	 * @return Column
	 */
	public function setWidth($width)
	{
		$this->width = $width;

		return $this;
	}

	/**
	 * @param int $truncate
	 * @return Column
	 */
	public function setTruncate($truncate)
	{
		$this->truncate = $truncate;

		return $this;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function prepareValue($row)
	{
		if(!empty($this->renderer))
			$value = call_user_func($this->renderer, (object)$row);
		else
			$value = $row[$this->name];

		if(!empty($this->truncate))
			return \Nette\Utils\Strings::truncate($value, $this->truncate);
		else
			return $value;
	}

	/**
	 * @param callback $renderer
	 * @return Column
	 */
	public function setRenderer($renderer)
	{
		$this->renderer = $renderer;

		return $this;
	}

	/**
	 * @param callback $renderer
	 * @return Column
	 */
	public function setFormRenderer($renderer)
	{
		$this->formRenderer = $renderer;

		return $this;
	}

	/**
	 * @param callback|string $renderer
	 * @return Column
	 */
	public function setCellRenderer($renderer)
	{
		$this->cellRenderer = $renderer;

		return $this;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getCellRenderer($row)
	{
		if(is_callable($this->cellRenderer)){
			return call_user_func($this->cellRenderer, $row);
		}
		return $this->cellRenderer;
	}

	/**
	 * @return bool
	 */
	public function hasCellRenderer()
	{
		return !empty($this->cellRenderer) ? TRUE : FALSE;
	}

	/**
	 * @param int $numOfResults
	 * @return Column
	 * @throws InvalidFilterException
	 * @throws UnknownFilterException
	 */
	public function setAutocomplete($numOfResults = 10)
	{
		if(empty($this->filterType)){
			throw new UnknownFilterException("Autocomplete can't be used without filter.");
		}elseif($this->filterType != FilterCondition::TEXT){
			throw new InvalidFilterException("Autocomplete can be used only with Text filter.");
		}
		$this->parent['gridForm'][$this->parent->name]['filter'][$this->name]->getControlPrototype()
			->addClass("grid-autocomplete")
			->addData("column", $this->name)
			->addData("gridName", $this->parent->getGridPath())
			->addData("link",$this->parent->link("autocomplete!"));

		$this->autocomplete = TRUE;

		$this->autocompleteResults = $numOfResults;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAutocompleteResults()
	{
		return $this->autocompleteResults;
	}

	/**
	 * @return Column
	 * @throws DuplicateEditableColumnException
	 */
	public function setTextEditable()
	{
		if($this->editable){
			throw new DuplicateEditableColumnException("Column $this->name is already editable.");
		}

		$this->parent['gridForm'][$this->parent->name]['rowForm']->addText($this->name, NULL)->getControlPrototype()->addClass("grid-editable");

		$this->editable = TRUE;

		return $this;
	}

	/**
	 * @param array $values
	 * @param string|null $prompt
	 * @return Column
	 * @throws DuplicateEditableColumnException
	 */
	public function setSelectEditable(array $values, $prompt = NULL)
	{
		if($this->editable){
			throw new DuplicateEditableColumnException("Column $this->name is already editable.");
		}
		$this->parent['gridForm'][$this->parent->name]['rowForm']->addSelect($this->name, NULL, $values)->getControlPrototype()->addClass("grid-editable");
		if($prompt){
			$this->parent['gridForm'][$this->parent->name]['rowForm'][$this->name]->setPrompt($prompt);
		}

		$this->editable = TRUE;

		return $this;
	}

	/**
	 * @return Column
	 * @throws DuplicateEditableColumnException
	 */
	public function setBooleanEditable()
	{
		if($this->editable){
			throw new DuplicateEditableColumnException("Column $this->name is already editable.");
		}
		$this->parent['gridForm'][$this->parent->name]['rowForm']->addCheckbox($this->name, NULL)->getControlPrototype()->addClass("grid-editable");

		$this->editable = TRUE;

		return $this;
	}

	/**
	 * @return Column
	 * @throws DuplicateEditableColumnException
	 */
	public function setDateEditable()
	{
		if($this->editable){
			throw new DuplicateEditableColumnException("Column $this->name is already editable.");
		}
		$this->parent['gridForm'][$this->parent->name]['rowForm']->addText($this->name, NULL)->getControlPrototype()->addClass("grid-datepicker")->addClass("grid-editable");

		$this->editable = TRUE;

		return $this;
	}

	/**
	 * @return Column
	 */
	public function markAsAlias()
	{
		$this->alias = TRUE;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAlias()
	{
		return $this->alias;
	}

	/**
	 * @param bool $sortable
	 * @return Column
	 */
	public function setSortable($sortable = TRUE)
	{
		$this->sortable = $sortable;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSortable()
	{
		return $this->sortable;
	}

	/**
	 * @return bool
	 */
	public function hasFilter()
	{
		return (!empty($this->parent['gridForm'][$this->parent->name]['filter'][$this->name])) ? TRUE : FALSE;
	}

	/**
	 * @return string
	 */
	public function getFilterType()
	{
		return $this->filterType;
	}

	/**
	 * @return Column
	 */
	public function setTextFilter()
	{
		$this->parent['gridForm'][$this->parent->name]['filter']->addText($this->name, $this->label.":");
		$this->filterType = FilterCondition::TEXT;

		return $this;
	}

	/**
	 * @return Column
	 */
	public function setNumericFilter()
	{
		$this->parent['gridForm'][$this->parent->name]['filter']->addText($this->name, $this->label.":");
		$this->filterType = FilterCondition::NUMERIC;

		return $this;
	}

	/**
	 * @param array $values
	 * @param string $prompt
	 * @return Column
	 */
	public function setSelectFilter($values, $prompt = "-----")
	{
		$this->parent['gridForm'][$this->parent->name]['filter']->addSelect($this->name, $this->label.":", $values);
		if($prompt){
			$this->parent['gridForm'][$this->parent->name]['filter'][$this->name]->setPrompt($prompt);
		}
		$this->filterType = FilterCondition::SELECT;

		return $this;
	}

	/**
	 * @return Column
	 */
	public function setDateFilter()
	{
		$this->parent['gridForm'][$this->parent->name]['filter']->addText($this->name, $this->label.":")->getControlPrototype()->class("grid-datepicker");
		$this->filterType = FilterCondition::DATE;

		return $this;
	}

	/**
	 * @param array $values
	 * @param string $prompt
	 * @return Column
	 */
	public function setBooleanFilter($values = array(0 => "Ne", 1 => "Ano"), $prompt = "-----")
	{
		$this->setSelectFilter($values, $prompt);
		$this->filterType = FilterCondition::BOOLEAN;

		return $this;
	}
}
