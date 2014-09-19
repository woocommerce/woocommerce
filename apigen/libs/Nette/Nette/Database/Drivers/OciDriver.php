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
 * Supplemental Oracle database driver.
 *
 * @author     David Grudl
 */
class OciDriver extends Nette\Object implements Nette\Database\ISupplementalDriver
{
	/** @var Nette\Database\Connection */
	private $connection;

	/** @var string  Datetime format */
	private $fmtDateTime;



	public function __construct(Nette\Database\Connection $connection, array $options)
	{
		$this->connection = $connection;
		$this->fmtDateTime = isset($options['formatDateTime']) ? $options['formatDateTime'] : 'U';
	}



	/********************* SQL ****************d*g**/



	/**
	 * Delimites identifier for use in a SQL statement.
	 */
	public function delimite($name)
	{
		// @see http://download.oracle.com/docs/cd/B10500_01/server.920/a96540/sql_elements9a.htm
		return '"' . str_replace('"', '""', $name) . '"';
	}



	/**
	 * Formats date-time for use in a SQL statement.
	 */
	public function formatDateTime(\DateTime $value)
	{
		return $value->format($this->fmtDateTime);
	}



	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function formatLike($value, $pos)
	{
		throw new Nette\NotImplementedException;
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		if ($offset > 0) {
			// see http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
			$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t '
				. ($limit >= 0 ? 'WHERE ROWNUM <= ' . ((int) $offset + (int) $limit) : '')
				. ') WHERE "__rnum" > '. (int) $offset;

		} elseif ($limit >= 0) {
			$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . (int) $limit;
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
		$tables = array();
		foreach ($this->connection->query('SELECT * FROM cat') as $row) {
			if ($row[1] === 'TABLE' || $row[1] === 'VIEW') {
				$tables[] = array(
					'name' => $row[0],
					'view' => $row[1] === 'VIEW',
				);
			}
		}
		return $tables;
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
