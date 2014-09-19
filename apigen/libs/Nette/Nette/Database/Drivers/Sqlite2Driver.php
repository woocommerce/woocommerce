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
 * Supplemental SQLite2 database driver.
 *
 * @author     David Grudl
 */
class Sqlite2Driver extends SqliteDriver
{

	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function formatLike($value, $pos)
	{
		throw new Nette\NotSupportedException;
	}



	/**
	 * Normalizes result row.
	 */
	public function normalizeRow($row, $statement)
	{
		if (!is_object($row)) {
			$iterator = $row;
		} elseif ($row instanceof \Traversable) {
			$iterator = iterator_to_array($row);
		} else {
			$iterator = (array) $row;
		}
		foreach ($iterator as $key => $value) {
			unset($row[$key]);
			if ($key[0] === '[' || $key[0] === '"') {
				$key = substr($key, 1, -1);
			}
			$row[$key] = $value;
		}
		return $row;
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys($table)
	{
		throw new NotSupportedException; // @see http://www.sqlite.org/foreignkeys.html
	}

}
