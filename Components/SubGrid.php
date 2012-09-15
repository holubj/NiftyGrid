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

class SubGrid extends \Nette\Application\UI\PresenterComponent
{
	/** @var string */
	public $name;

	/** @var callback|string */
	public $label;

	/** @var callback|string */
	private $link;

	/** @var callback */
	private $settings;

	/** @var bool */
	public $ajax = TRUE;

	/** @var callback|string */
	public $class;

	/** @var callback|string */
	public $cellStyle;

	/** @var callback|string */
	public $show = TRUE;

	/**
	 * @param string $name
	 * @return SubGrid
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @param callback|string $label
	 * @return SubGrid
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @param array $row
	 * @return mixed|string
	 */
	public function getLabel($row)
	{
		if(is_callable($this->label)){
			return call_user_func($this->label, $row);
		}
		return $this->label;
	}

	/**
	 * @param callback|string $class
	 * @return SubGrid
	 */
	public function setClass($class)
	{
		$this->class = $class;

		return $this;
	}

	/**
	 * @param $row
	 * @return string
	 */
	public function getClass($row)
	{
		if(is_callable($this->class)){
			return call_user_func($this->class, $row);
		}
		return $this->class;
	}

	/**
	 * @param callback|string $link
	 * @return SubGrid
	 */
	public function setLink($link)
	{
		$this->link = $link;

		return $this;
	}

	/**
	 * @param array $row
	 * @return mixed
	 */
	public function getLink($row)
	{
		return call_user_func($this->link, $row);
	}

	/**
	 * @param callback|string $cellStyle
	 * @return \Nifty\Grid\SubGrid
	 */
	public function setCellStyle($cellStyle)
	{
		$this->cellStyle = $cellStyle;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasCellStyle()
	{
		return !empty($this->cellStyle) ? TRUE : FALSE;
	}

	/**
	 * @return string
	 */
	public function getCellStyle()
	{
		if(is_callable($this->cellStyle)){
			return call_user_func($this->cellStyle, $this->grid);
		}
		return $this->cellStyle;
	}

	/**
	 * @param callback|string $show
	 * @return Button
	 */
	public function setShow($show)
	{
		$this->show = $show;

		return $this;
	}

	/**
	 * @param array $row
	 * @return callback|mixed|string
	 */
	public function getShow($row)
	{
		if(is_callable($this->show)){
			return (boolean) call_user_func($this->show, $row);
		}
		return $this->show;
	}

	/**
	 * @param Grid $grid
	 * @return SubGrid
	 */
	public function setGrid(Grid $grid)
	{
		$this->grid = $grid;

		return $this;
	}

	/**
	 * @param bool $ajax
	 * @return SubGrid
	 */
	public function setAjax($ajax = TRUE)
	{
		$this->ajax = $ajax;

		return $this;
	}

	/**
	 * @param callback $settings
	 * @return SubGrid
	 */
	public function settings($settings)
	{
		$this->settings = $settings;

		return $this;
	}

	/**
	 * @return Grid
	 */
	public function getGrid()
	{
		$this->grid->isSubGrid = TRUE;
		$this->grid->afterConfigureSettings = $this->settings;
		return $this->grid;
	}

	/**
	 * @param array $row
	 */
	public function render($row)
	{
		if(!$this->getShow($row)){
			return false;
		}

		$el = \Nette\Utils\Html::el("a")
			->href($this->getLink($row))
			->addClass($this->getClass($row))
			->addClass("grid-button")
			->setTitle($this->getLabel($row));

		if($this->ajax){
			$el->addClass("grid-ajax");
		}
		echo $el;
	}
}
