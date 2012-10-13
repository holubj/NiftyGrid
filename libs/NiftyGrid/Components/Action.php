<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @author	Jakub Holub
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid\Components;

use Nette;
use NiftyGrid;

class Action extends \Nette\Application\UI\PresenterComponent
{
	/** @var string */
	public $name;

	/** @var string */
	public $label;

	/** @var callback|string */
	public $dialog;

	/** @var callback */
	public $callback;

	/** @var boolean */
	public $ajax = TRUE;

	/**
	 * @param string $name
	 * @return Action
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @param string $label
	 * @return Action
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @param callback|string $dialog
	 * @return Action
	 */
	public function setConfirmationDialog($dialog)
	{
		$this->dialog = $dialog;

		return $this;
	}

	/**
	 * @param callback $callback
	 * @return Action
	 */
	public function setCallback($callback)
	{
		$this->callback = $callback;

		return $this;
	}

	/**
	 * @return callback
	 */
	public function getCallback()
	{
		return $this->callback;
	}


	/**
	 * @param string $ajax
	 * @return Action
	 */
	public function setAjax($ajax)
	{
		$this->ajax = $ajax;

		return $this;
	}

	/**
	 * @return mixed
	 * @throws NiftyGrid\UnknownActionCallbackException
	 */
	public function getAction()
	{
		if(empty($this->callback)){
			throw new UnknownActionCallbackException("Action $this->name doesn't have callback.");
		}

		$option = \Nette\Utils\Html::el('option')->setValue($this->name)->setText($this->label);

		if($this->ajax){
			$option->addClass('grid-ajax');
		}

		if(!empty($this->dialog)){
			$option->addData("grid-confirm", $this->dialog);
		}

		return $option;
	}
}
