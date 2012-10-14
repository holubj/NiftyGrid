<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @author	Jakub Holub
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license	New BSD Licence
 * @link	http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid;

use Nette\Utils\Paginator;

class GridPaginator extends \Nette\Application\UI\Control
{
	/** @persistent int */
	public $page = 1;

	/**
	 * @var Paginator
	*/
	public $paginator;

	public function __construct()
	{
		parent::__construct();
		$this->paginator = new Paginator;
	}

	public function render()
	{
		$this->template->paginator = $this->paginator;
		$this->template->setFile(__DIR__ . '/../../templates/paginator.latte');
		$this->template->render();
	}

	/**
	 * @param array $params
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->paginator->page = $this->page;
	}
}