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
 * Supplemental SQLite3 database driver.
 *
 * @author     David Grudl
 */
class SqliteDriver extends Nette\Object implements Nette\Database\ISupplementalDriver
{
	/** @var Nette\Database\Connection */
	private $connection;

	/** @var string  Datetime format */
	private $fmtDateTime;



	public function __construct(Nette\Database\Connection $connection, array $options)
	{
		$this->connection = $connection;
		$this->fmtDateTime = isset($options['formatDateTime']) ? $options['formatDateTime'] : 'U';
		//$connection->exec('PRAGMA foreign_keys = ON');
	}



	/********************* SQL ****************d*g**/



	/**
	 * Delimites identifier for use in a SQL statement.
	 */
	public function delimite($name)
	{
		return '[' . strtr($name, '[]', '  ') . ']';
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
		$value = addcslashes(substr($this->connection->quote($value), 1, -1), '%_\\');
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'") . " ESCAPE '\\'";
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		if ($limit >= 0 || $offset > 0) {
			$sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
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
		return $this->connection->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view')
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view')
			ORDER BY name
		")->fetchAll();
	}



	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns($table)
	{
		$meta = $this->connection->query("
			SELECT sql FROM sqlite_master WHERE type = 'table' AND name = {$this->connection->quote($table)}
			UNION ALL
			SELECT sql FROM sqlite_temp_master WHERE type = 'table' AND name = {$this->connection->quote($table)}
		")->fetch();

		$columns = array();
		foreach ($this->connection->query("PRAGMA table_info({$this->delimite($table)})") as $row) {
			$column = $row['name'];
			$pattern = "/(\"$column\"|\[$column\]|$column)\s+[^,]+\s+PRIMARY\s+KEY\s+AUTOINCREMENT/Ui";
			$type = explode('(', $row['type']);
			$columns[] = array(
				'name' => $column,
				'table' => $table,
				'fullname' => "$table.$column",
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : NULL,
				'nullable' => $row['notnull'] == '0',
				'default' => $row['dflt_value'],
				'autoincrement' => (bool) preg_match($pattern, $meta['sql']),
				'primary' => $row['pk'] == '1',
				'vendor' => (array) $row,
			);
		}
		return $columns;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 */
	public function getIndexes($table)
	{
		$indexes = array();
		foreach ($this->connection->query("PRAGMA index_list({$this->delimite($table)})") as $row) {
			$indexes[$row['name']]['name'] = $row['name'];
			$indexes[$row['name']]['unique'] = (bool) $row['unique'];
		}

		foreach ($indexes as $index => $values) {
			$res = $this->connection->query("PRAGMA index_info({$this->delimite($index)})");
			while ($row = $res->fetch(TRUE)) {
				$indexes[$index]['columns'][$row['seqno']] = $row['name'];
			}
		}

		$columns = $this->getColumns($table);
		foreach ($indexes as $index => $values) {
			$column = $indexes[$index]['columns'][0];
			$primary = FALSE;
			foreach ($columns as $info) {
				if ($column == $info['name']) {
					$primary = $info['primary'];
					break;
				}
			}
			$indexes[$index]['primary'] = (bool) $primary;
		}
		if (!$indexes) { // @see http://www.sqlite.org/lang_createtable.html#rowid
			foreach ($columns as $column) {
				if ($column['vendor']['pk']) {
					$indexes[] = array(
						'name' => 'ROWID',
						'unique' => TRUE,
						'primary' => TRUE,
						'columns' => array($column['name']),
					);
					break;
				}
			}
		}

		return array_values($indexes);
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys($table)
	{
		$keys = array();
		foreach ($this->connection->query("PRAGMA foreign_key_list({$this->delimite($table)})") as $row) {
			$keys[$row['id']]['name'] = $row['id']; // foreign key name
			$keys[$row['id']]['local'][$row['seq']] = $row['from']; // local columns
			$keys[$row['id']]['table'] = $row['table']; // referenced table
			$keys[$row['id']]['foreign'][$row['seq']] = $row['to']; // referenced columns
			$keys[$row['id']]['onDelete'] = $row['on_delete'];
			$keys[$row['id']]['onUpdate'] = $row['on_update'];

			if ($keys[$row['id']]['foreign'][0] == NULL) {
				$keys[$row['id']]['foreign'] = NULL;
			}
		}
		return array_values($keys);
	}



	/**
	 * @return bool
	 */
	public function isSupported($item)
	{
		return FALSE;
	}

}
