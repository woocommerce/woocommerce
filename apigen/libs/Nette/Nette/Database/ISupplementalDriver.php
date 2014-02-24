<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Database;

use Nette;



/**
 * Supplemental PDO database driver.
 *
 * @author     David Grudl
 */
interface ISupplementalDriver
{
	const META = 'meta';

	/**
	 * Delimites identifier for use in a SQL statement.
	 * @param  string
	 * @return string
	 */
	function delimite($name);

	/**
	 * Formats date-time for use in a SQL statement.
	 * @param  \DateTime
	 * @return string
	 */
	function formatDateTime(\DateTime $value);

	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	function formatLike($value, $pos);

	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @param  string  SQL query that will be modified.
	 * @param  int
	 * @param  int
	 * @return void
	 */
	function applyLimit(&$sql, $limit, $offset);

	/**
	 * Normalizes result row.
	 * @param  array
	 * @param  Statement
	 * @return array
	 */
	function normalizeRow($row, $statement);


	/********************* reflection ****************d*g**/


	/**
	 * Returns list of tables.
	 * @return array of [name [, (bool) view]]
	 */
	function getTables();

	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array of [name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor]]
	 */
	function getColumns($table);

	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array of [name, (array of names) columns [, (bool) unique, (bool) primary]]
	 */
	function getIndexes($table);

	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	function getForeignKeys($table);

}
