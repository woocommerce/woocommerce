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
 * Supplemental PostgreSQL database driver.
 *
 * @author     David Grudl
 */
class PgSqlDriver extends Nette\Object implements Nette\Database\ISupplementalDriver
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
		// @see http://www.postgresql.org/docs/8.2/static/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
		return '"' . str_replace('"', '""', $name) . '"';
	}



	/**
	 * Formats date-time for use in a SQL statement.
	 */
	public function formatDateTime(\DateTime $value)
	{
		return $value->format("'Y-m-d H:i:s'");
	}



	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function formatLike($value, $pos)
	{
		$value = strtr($value, array("'" => "''", '\\' => '\\\\', '%' => '\\\\%', '_' => '\\\\_'));
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		if ($limit >= 0)
			$sql .= ' LIMIT ' . (int) $limit;

		if ($offset > 0)
			$sql .= ' OFFSET ' . (int) $offset;
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
		foreach ($this->connection->query("
			SELECT
				table_name AS name,
				table_type = 'VIEW' AS view
			FROM
				information_schema.tables
			WHERE
				table_schema = current_schema()
		") as $row) {
			$tables[] = (array) $row;
		}

		return $tables;
	}



	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns($table)
	{
		$columns = array();
		foreach ($this->connection->query("
			SELECT
				c.column_name AS name,
				c.table_name AS table,
				upper(c.udt_name) AS nativetype,
				greatest(c.character_maximum_length, c.numeric_precision) AS size,
				FALSE AS unsigned,
				c.is_nullable = 'YES' AS nullable,
				c.column_default AS default,
				coalesce(tc.constraint_type = 'PRIMARY KEY', FALSE) AND strpos(c.column_default, 'nextval') = 1 AS autoincrement,
				coalesce(tc.constraint_type = 'PRIMARY KEY', FALSE) AS primary
			FROM
				information_schema.columns AS c
				LEFT JOIN information_schema.constraint_column_usage AS ccu USING(table_catalog, table_schema, table_name, column_name)
				LEFT JOIN information_schema.table_constraints AS tc USING(constraint_catalog, constraint_schema, constraint_name)
			WHERE
				c.table_name = {$this->connection->quote($table)}
				AND
				c.table_schema = current_schema()
				AND
				(tc.constraint_type IS NULL OR tc.constraint_type = 'PRIMARY KEY')
			ORDER BY
				c.ordinal_position
		") as $row) {
			$row['vendor'] = array();
			$columns[] = (array) $row;
		}

		return $columns;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 */
	public function getIndexes($table)
	{
		/* There is no information about all indexes in information_schema, so pg catalog must be used */
		$indexes = array();
		foreach ($this->connection->query("
			SELECT
				c2.relname AS name,
				indisunique AS unique,
				indisprimary AS primary,
				attname AS column
			FROM
				pg_class AS c1
				JOIN pg_namespace ON c1.relnamespace = pg_namespace.oid
				JOIN pg_index ON c1.oid = indrelid
				JOIN pg_class AS c2 ON indexrelid = c2.oid
				LEFT JOIN pg_attribute ON c1.oid = attrelid AND attnum = ANY(indkey)
			WHERE
				nspname = current_schema()
				AND
				c1.relkind = 'r'
				AND
				c1.relname = {$this->connection->quote($table)}
		") as $row) {
			$indexes[$row['name']]['name'] = $row['name'];
			$indexes[$row['name']]['unique'] = $row['unique'];
			$indexes[$row['name']]['primary'] = $row['primary'];
			$indexes[$row['name']]['columns'][] = $row['column'];
		}

		return array_values($indexes);
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys($table)
	{
		return $this->connection->query("
			SELECT tc.table_name AS name, kcu.column_name AS local, ccu.table_name AS table, ccu.column_name AS foreign
			FROM information_schema.table_constraints AS tc
			JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.constraint_schema = kcu.constraint_schema
			JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.constraint_schema = tc.constraint_schema
			WHERE
				constraint_type = 'FOREIGN KEY' AND
				tc.table_name = {$this->connection->quote($table)} AND
				tc.constraint_schema = current_schema()
		")->fetchAll();
	}



	/**
	 * @return bool
	 */
	public function isSupported($item)
	{
		return $item === self::META;
	}

}
