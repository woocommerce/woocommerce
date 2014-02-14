<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Database\Table;

use Nette,
	PDO;



/**
 * Filtered table representation.
 * Selection is based on the great library NotORM http://www.notorm.com written by Jakub Vrana.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 *
 * @property-read string $sql
 */
class Selection extends Nette\Object implements \Iterator, \ArrayAccess, \Countable
{
	/** @var Nette\Database\Connection */
	protected $connection;

	/** @var SqlBuilder */
	protected $sqlBuilder;

	/** @var string table name */
	protected $name;

	/** @var string primary key field name */
	protected $primary;

	/** @var ActiveRow[] data read from database in [primary key => ActiveRow] format */
	protected $rows;

	/** @var ActiveRow[] modifiable data in [primary key => ActiveRow] format */
	protected $data;

	/** @var Selection[] */
	protected $referenced = array();

	/** @var array of [sqlQuery-hash => grouped data]; used by GroupedSelection */
	protected $referencing = array();

	/** @var GroupedSelection[] cached array of GroupedSelection prototypes */
	protected $referencingPrototype = array();

	/** @var array of [conditions => [key => ActiveRow]]; used by GroupedSelection */
	protected $aggregation = array();

	/** @var array of touched columns */
	protected $accessed;

	/** @var array of earlier touched columns */
	protected $prevAccessed;

	/** @var bool should instance observe accessed columns caching */
	protected $observeCache = FALSE;

	/** @var bool recheck referencing keys */
	protected $checkReferenced = FALSE;

	/** @var array of primary key values */
	protected $keys = array();



	/**
	 * Creates filtered table representation.
	 * @param  string  database table name
	 * @param  Nette\Database\Connection
	 */
	public function __construct($table, Nette\Database\Connection $connection)
	{
		$this->name = $table;
		$this->connection = $connection;
		$this->primary = $connection->getDatabaseReflection()->getPrimary($table);
		$this->sqlBuilder = new SqlBuilder($this);
	}



	public function __destruct()
	{
		$this->saveCacheState();
	}



	public function __clone()
	{
		$this->sqlBuilder = clone $this->sqlBuilder;
		$this->sqlBuilder->setSelection($this);
	}



	/**
	 * @return Nette\Database\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return string
	 */
	public function getPrimary()
	{
		return $this->primary;
	}



	/**
	 * @return string
	 */
	public function getSql()
	{
		return $this->sqlBuilder->buildSelectQuery();
	}



	/**
	 * Loads cache of previous accessed columns and returns it
	 * @internal
	 * @return array|false
	 */
	public function getPreviousAccessed()
	{
		$cache = $this->connection->getCache();
		if ($this->rows === NULL && $cache && !is_string($this->prevAccessed)) {
			$this->accessed = $this->prevAccessed = $cache->load(array(__CLASS__, $this->name, $this->sqlBuilder->getConditions()));
		}

		return $this->prevAccessed;
	}



	/**
	 * @internal
	 * @return SqlBuilder
	 */
	public function getSqlBuilder()
	{
		return $this->sqlBuilder;
	}



	/********************* quick access ****************d*g**/



	/**
	 * Returns row specified by primary key.
	 * @param  mixed primary key
	 * @return ActiveRow or FALSE if there is no such row
	 */
	public function get($key)
	{
		$clone = clone $this;
		$clone->where($this->primary, $key);
		return $clone->fetch();
	}



	/**
	 * Returns next row of result.
	 * @return ActiveRow or FALSE if there is no row
	 */
	public function fetch()
	{
		$this->execute();
		$return = current($this->data);
		next($this->data);
		return $return;
	}



	/**
	 * Returns all rows as associative array.
	 * @param  string
	 * @param  string column name used for an array value or NULL for the whole row
	 * @return array
	 */
	public function fetchPairs($key, $value = NULL)
	{
		$return = array();
		foreach ($this as $row) {
			$return[is_object($row[$key]) ? (string) $row[$key] : $row[$key]] = ($value ? $row[$value] : $row);
		}
		return $return;
	}



	/********************* sql selectors ****************d*g**/



	/**
	 * Adds select clause, more calls appends to the end.
	 * @param  string for example "column, MD5(column) AS column_md5"
	 * @return Selection provides a fluent interface
	 */
	public function select($columns)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->addSelect($columns);
		return $this;
	}



	/**
	 * Selects by primary key.
	 * @param  mixed
	 * @return Selection provides a fluent interface
	 */
	public function find($key)
	{
		return $this->where($this->primary, $key);
	}



	/**
	 * Adds where condition, more calls appends with AND.
	 * @param  string condition possibly containing ?
	 * @param  mixed
	 * @param  mixed ...
	 * @return Selection provides a fluent interface
	 */
	public function where($condition, $parameters = array())
	{
		if (is_array($condition)) { // where(array('column1' => 1, 'column2 > ?' => 2))
			foreach ($condition as $key => $val) {
				if (is_int($key)) {
					$this->where($val);	// where('full condition')
				} else {
					$this->where($key, $val);	// where('column', 1)
				}
			}
			return $this;
		}

		$args = func_get_args();
		if (call_user_func_array(array($this->sqlBuilder, 'addWhere'), $args)) {
			$this->emptyResultSet();
		}

		return $this;
	}



	/**
	 * Adds order clause, more calls appends to the end.
	 * @param  string for example 'column1, column2 DESC'
	 * @return Selection provides a fluent interface
	 */
	public function order($columns)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->addOrder($columns);
		return $this;
	}



	/**
	 * Sets limit clause, more calls rewrite old values.
	 * @param  int
	 * @param  int
	 * @return Selection provides a fluent interface
	 */
	public function limit($limit, $offset = NULL)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->setLimit($limit, $offset);
		return $this;
	}



	/**
	 * Sets offset using page number, more calls rewrite old values.
	 * @param  int
	 * @param  int
	 * @return Selection provides a fluent interface
	 */
	public function page($page, $itemsPerPage)
	{
		return $this->limit($itemsPerPage, ($page - 1) * $itemsPerPage);
	}



	/**
	 * Sets group clause, more calls rewrite old values.
	 * @param  string
	 * @param  string
	 * @return Selection provides a fluent interface
	 */
	public function group($columns, $having = NULL)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->setGroup($columns, $having);
		return $this;
	}



	/********************* aggregations ****************d*g**/



	/**
	 * Executes aggregation function.
	 * @param  string select call in "FUNCTION(column)" format
	 * @return string
	 */
	public function aggregation($function)
	{
		$selection = $this->createSelectionInstance();
		$selection->getSqlBuilder()->importConditions($this->getSqlBuilder());
		$selection->select($function);
		foreach ($selection->fetch() as $val) {
			return $val;
		}
	}



	/**
	 * Counts number of rows.
	 * @param  string  if it is not provided returns count of result rows, otherwise runs new sql counting query
	 * @return int
	 */
	public function count($column = NULL)
	{
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return $this->aggregation("COUNT($column)");
	}



	/**
	 * Returns minimum value from a column.
	 * @param  string
	 * @return int
	 */
	public function min($column)
	{
		return $this->aggregation("MIN($column)");
	}



	/**
	 * Returns maximum value from a column.
	 * @param  string
	 * @return int
	 */
	public function max($column)
	{
		return $this->aggregation("MAX($column)");
	}



	/**
	 * Returns sum of values in a column.
	 * @param  string
	 * @return int
	 */
	public function sum($column)
	{
		return $this->aggregation("SUM($column)");
	}



	/********************* internal ****************d*g**/



	protected function execute()
	{
		if ($this->rows !== NULL) {
			return;
		}

		$this->observeCache = TRUE;

		try {
			$result = $this->query($this->sqlBuilder->buildSelectQuery());

		} catch (\PDOException $exception) {
			if (!$this->sqlBuilder->getSelect() && $this->prevAccessed) {
				$this->prevAccessed = '';
				$this->accessed = array();
				$result = $this->query($this->sqlBuilder->buildSelectQuery());
			} else {
				throw $exception;
			}
		}

		$this->rows = array();
		$result->setFetchMode(PDO::FETCH_ASSOC);
		foreach ($result as $key => $row) {
			$row = $result->normalizeRow($row);
			$this->rows[isset($row[$this->primary]) ? $row[$this->primary] : $key] = $this->createRow($row);
		}
		$this->data = $this->rows;

		if (isset($row[$this->primary]) && !is_string($this->accessed)) {
			$this->accessed[$this->primary] = TRUE;
		}
	}



	protected function createRow(array $row)
	{
		return new ActiveRow($row, $this);
	}



	protected function createSelectionInstance($table = NULL)
	{
		return new Selection($table ?: $this->name, $this->connection);
	}



	protected function createGroupedSelectionInstance($table, $column)
	{
		return new GroupedSelection($this, $table, $column);
	}



	protected function query($query)
	{
		return $this->connection->queryArgs($query, $this->sqlBuilder->getParameters());
	}



	protected function emptyResultSet()
	{
		if ($this->rows === NULL) {
			return;
		}

		$this->rows = NULL;
		$this->saveCacheState();
	}



	protected function saveCacheState()
	{
		if ($this->observeCache && ($cache = $this->connection->getCache()) && !$this->sqlBuilder->getSelect() && $this->accessed != $this->prevAccessed) {
			$cache->save(array(__CLASS__, $this->name, $this->sqlBuilder->getConditions()), $this->accessed);
		}
	}



	/**
	 * Returns Selection parent for caching
	 * @return Selection
	 */
	protected function getRefTable(& $refPath)
	{
		return $this;
	}



	/**
	 * @internal
	 * @param  string column name
	 * @param  bool|NULL TRUE - cache, FALSE - don't cache, NULL - remove
	 * @return bool
	 */
	public function access($key, $cache = TRUE)
	{
		if ($cache === NULL) {
			if (is_array($this->accessed)) {
				$this->accessed[$key] = FALSE;
			}
			return FALSE;
		}

		if ($key === NULL) {
			$this->accessed = '';

		} elseif (!is_string($this->accessed)) {
			$this->accessed[$key] = $cache;
		}

		if ($cache && !$this->sqlBuilder->getSelect() && $this->prevAccessed && ($key === NULL || !isset($this->prevAccessed[$key]))) {
			$this->prevAccessed = '';
			$this->emptyResultSet();
			return TRUE;
		}

		return FALSE;
	}



	/********************* manipulation ****************d*g**/



	/**
	 * Inserts row in a table.
	 * @param  mixed array($column => $value)|Traversable for single row insert or Selection|string for INSERT ... SELECT
	 * @return ActiveRow or FALSE in case of an error or number of affected rows for INSERT ... SELECT
	 */
	public function insert($data)
	{
		if ($data instanceof Selection) {
			$data = $data->getSql();

		} elseif ($data instanceof \Traversable) {
			$data = iterator_to_array($data);
		}

		$return = $this->connection->query($this->sqlBuilder->buildInsertQuery(), $data);
		$this->checkReferenced = TRUE;

		if (!is_array($data)) {
			return $return->rowCount();
		}

		if (!isset($data[$this->primary]) && ($id = $this->connection->lastInsertId())) {
			$data[$this->primary] = $id;
			return $this->rows[$id] = $this->createRow($data);

		} else {
			return $this->createRow($data);

		}
	}



	/**
	 * Updates all rows in result set.
	 * Joins in UPDATE are supported only in MySQL
	 * @param  array|\Traversable ($column => $value)
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function update($data)
	{
		if ($data instanceof \Traversable) {
			$data = iterator_to_array($data);

		} elseif (!is_array($data)) {
			throw new Nette\InvalidArgumentException;
		}

		if (!$data) {
			return 0;
		}

		return $this->connection->queryArgs(
			$this->sqlBuilder->buildUpdateQuery(),
			array_merge(array($data), $this->sqlBuilder->getParameters())
		)->rowCount();
	}



	/**
	 * Deletes all rows in result set.
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function delete()
	{
		return $this->query($this->sqlBuilder->buildDeleteQuery())->rowCount();
	}



	/********************* references ****************d*g**/



	/**
	 * Returns referenced row.
	 * @param  string
	 * @param  string
	 * @param  bool  checks if rows contains the same primary value relations
	 * @return Selection or array() if the row does not exist
	 */
	public function getReferencedTable($table, $column, $checkReferenced = FALSE)
	{
		$referenced = & $this->getRefTable($refPath)->referenced[$refPath . "$table.$column"];
		if ($referenced === NULL || $checkReferenced || $this->checkReferenced) {
			$this->execute();
			$this->checkReferenced = FALSE;
			$keys = array();
			foreach ($this->rows as $row) {
				if ($row[$column] === NULL)
					continue;

				$key = $row[$column] instanceof ActiveRow ? $row[$column]->getPrimary() : $row[$column];
				$keys[$key] = TRUE;
			}

			if ($referenced !== NULL && array_keys($keys) === array_keys($referenced->rows)) {
				return $referenced;
			}

			if ($keys) {
				$referenced = $this->createSelectionInstance($table);
				$referenced->where($referenced->primary, array_keys($keys));
			} else {
				$referenced = array();
			}
		}

		return $referenced;
	}



	/**
	 * Returns referencing rows.
	 * @param  string
	 * @param  string
	 * @param  int primary key
	 * @return GroupedSelection
	 */
	public function getReferencingTable($table, $column, $active = NULL)
	{
		$prototype = & $this->getRefTable($refPath)->referencingPrototype[$refPath . "$table.$column"];
		if (!$prototype) {
			$prototype = $this->createGroupedSelectionInstance($table, $column);
			$prototype->where("$table.$column", array_keys((array) $this->rows));
		}

		$clone = clone $prototype;
		$clone->setActive($active);
		return $clone;
	}



	/********************* interface Iterator ****************d*g**/



	public function rewind()
	{
		$this->execute();
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}



	/** @return ActiveRow */
	public function current()
	{
		return $this->data[current($this->keys)];
	}



	/**
	 * @return string row ID
	 */
	public function key()
	{
		return current($this->keys);
	}



	public function next()
	{
		next($this->keys);
	}



	public function valid()
	{
		return current($this->keys) !== FALSE;
	}



	/********************* interface ArrayAccess ****************d*g**/



	/**
	 * Mimic row.
	 * @param  string row ID
	 * @param  ActiveRow
	 * @return NULL
	 */
	public function offsetSet($key, $value)
	{
		$this->execute();
		$this->data[$key] = $value;
	}



	/**
	 * Returns specified row.
	 * @param  string row ID
	 * @return ActiveRow or NULL if there is no such row
	 */
	public function offsetGet($key)
	{
		$this->execute();
		return $this->data[$key];
	}



	/**
	 * Tests if row exists.
	 * @param  string row ID
	 * @return bool
	 */
	public function offsetExists($key)
	{
		$this->execute();
		return isset($this->data[$key]);
	}



	/**
	 * Removes row from result set.
	 * @param  string row ID
	 * @return NULL
	 */
	public function offsetUnset($key)
	{
		$this->execute();
		unset($this->data[$key]);
	}

}
