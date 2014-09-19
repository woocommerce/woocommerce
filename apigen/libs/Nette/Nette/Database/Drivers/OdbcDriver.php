<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Database\Drivers;

use Nette;



/**
 * Supplemental ODBC database driver.
 *
 * @author     David Grudl
 */
class OdbcDriver extends Nette\Object implements Nette\Database\ISupplementalDriver
{
	/** @var Nette\Database\Connection */
	private $connection;



	public function __construct(Nette\Database\Connection $connection, array $options)
	{
		$this->connection = $connection;
	}



	/********************* SQL ****************d*g**/



	/**
	 * Delimites identifier for use in a SQL statement.
	 */
	public function delimite($name)
	{
		return '[' . str_replace(array('[', ']'), array('[[', ']]'), $name) . ']';
	}



	/**
	 * Formats date-time for use in a SQL statement.
	 */
	public function formatDateTime(\DateTime $value)
	{
		return $value->format("#m/d/Y H:i:s#");
	}



	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function formatLike($value, $pos)
	{
		$value = strtr($value, array("'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]'));
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		// offset support is missing
		if ($limit >= 0) {
			$sql = 'SELECT TOP ' . (int) $limit . ' * FROM (' . $sql . ')';
		}

		if ($offset) {
			throw new Nette\InvalidArgumentException('Offset is not implemented in driver odbc.');
		}
	}



	/**
	 * Normalizes result row.
	 */
	public function normalizeRow($row, $statement)
	{
		return $row;
	}



	/********************* reflection ****************d*g**/



	/**
	 * Returns list of tables.
	 */
	public function getTables()
	{
		throw new NotImplementedException;
	}



	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns($table)
	{
		throw new NotImplementedException;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 */
	public function getIndexes($table)
	{
		throw new NotImplementedException;
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys($table)
	{
		throw new NotImplementedException;
	}



	/**
	 * @return bool
	 */
	public function isSupported($item)
	{
		return $item === self::META;
	}

}
