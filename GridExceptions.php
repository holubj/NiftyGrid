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

class GridException extends \Exception
{

}

class InvalidFilterException extends GridException
{

}

class UnknownColumnException extends GridException
{

}

class DuplicateColumnException extends GridException
{

}

class DuplicateEditableColumnException extends GridException
{

}

class DuplicateButtonException extends GridException
{

}

class DuplicateActionException extends GridException
{

}

class UnknownActionCallbackException extends GridException
{

}

class DuplicateSubGridException extends GridException
{

}

class UnknownFilterException extends GridException
{

}

class NoRowSelectedException extends GridException
{

}

class InvalidOrderException extends GridException
{

}