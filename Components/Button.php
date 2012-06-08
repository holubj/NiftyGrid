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

use Nette\Utils\Html;

class Button extends \Nette\Application\UI\PresenterComponent
{
	/** @var callback|string */
	private $label;

	/** @var callback|string */
	private $link;

	/** @var callback|string */
	private $class;

	/** @var callback|string */
	private $dialog;

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
	 * @param array $row
	 * @return string
	 */
	private function getLabel($row)
	{
		if(is_callable($this->label)){
			return call_user_func($this->label, $row);
		}
		return $this->label;
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
	 * @param array $row
	 * @return string
	 */
	private function getLink($row)
	{
		if(is_callable($this->link)){
			return call_user_func($this->link, $row);
		}
		return $this->link;
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
	 * @param array $row
	 * @return callback|mixed|string
	 */
	private function getClass($row)
	{
		if(is_callable($this->class)){
			return call_user_func($this->class, $row);
		}
		return $this->class;
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

	/**
	 * @param callback|string $message
	 * @return Button
	 */
	public function setConfirmationDialog($message)
	{
		$this->dialog = $message;

		return $this;
	}

	/**
	 * @param array $row
	 * @return callback|mixed|string
	 */
	public function getConfirmationDialog($row)
	{
		if(is_callable($this->dialog)){
			return call_user_func($this->dialog, $row);
		}
		return $this->dialog;
	}

	/**
	 * @return bool
	 */
	private function hasConfirmationDialog()
	{
		return (!empty($this->dialog)) ? TRUE : FALSE;
	}

	/**
	 * @param array $row
	 */
	public function render($row)
	{
		$el = Html::el("a")
			->href($this->getLink($row))
			->setClass($this->getClass($row))
			->addClass("grid-button")
			->setTitle($this->getLabel($row));

		if($this->hasConfirmationDialog()){
			$el->addClass("grid-confirm")
				->addData("grid-confirm", $this->getConfirmationDialog($row));
		}

		if($this->ajax){
			$el->addClass("grid-ajax");
		}
		echo $el;
	}

}
