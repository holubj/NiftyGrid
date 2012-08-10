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

use Nette\Utils\Html,
	NiftyGrid\Grid; // For constant only

class GlobalButton extends \Nette\Application\UI\PresenterComponent
{
	/** @var string */
	private $label;

	/** @var string */
	private $class;

	/** @var callback|string */
	private $link;

	/** @var bool */
	private $ajax = TRUE;

	/**
	 * @param string $label
	 * @return Button
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @param callback|string $class
	 * @return Button
	 */
	public function setClass($class)
	{
		$this->class = $class;

		return $this;
	}

	/**
	 * @param callback|string $link
	 * @return Button
	 */
	public function setLink($link)
	{
		$this->link = $link;

		return $this;
	}

	/**
	 * @return string
	 */
	private function getLink()
	{
		if(is_callable($this->link)){
			return call_user_func($this->link);
		}
		return $this->link;
	}

	/**
	 * @param bool $ajax
	 * @return Button
	 */
	public function setAjax($ajax = TRUE)
	{
		$this->ajax = $ajax;

		return $this;
	}

	public function render()
	{
		$el = Html::el("a")
			->href($this->getLink())
			->setClass($this->class)
			->addClass("grid-button")
			->addClass("grid-global-button")
			->setTitle($this->label);

		if($this->getName() == Grid::ADD_ROW) {
			$el->addClass("grid-add-row");
		}

		if($this->ajax){
			$el->addClass("grid-ajax");
		}
		echo $el;
	}
}
