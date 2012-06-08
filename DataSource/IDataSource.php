<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @author	akub Holub
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid;

interface IDataSource
{
	public function __construct($data);

	public function getData();

	public function getCount($column = "*");

	public function getSelectedRowsCount();

	public function orderData($by, $way);

	public function limitData($limit, $offset);

	public function filterData(array $filters);
}
