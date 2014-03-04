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

use Nette;



/**
 * Single row representation.
 * ActiveRow is based on the great library NotORM http://www.notorm.com written by Jakub Vrana.
 *
 * @author     Jakub Vrana
 */
class ActiveRow extends Nette\Object implements \IteratorAggregate, \ArrayAccess
{
	/** @var Selection */
	private $table;

	/** @var array of row data */
	private $data;

	/** @var array of new values {@see ActiveRow::update()} */
	private $modified = array();



	public function __construct(array $data, Selection $table)
	{
		$this->data = $data;
		$this->table = $table;
	}



	/**
	 * @internal
	 * @ignore
	 */
	public function setTable(Selection $table)
	{
		$this->table = $table;
	}



	/**
	 * @internal
	 * @ignore
	 */
	public function getTable()
	{
		return $this->table;
	}



	public function __toString()
	{
		try {
			return (string) $this->getPrimary();
		} catch (\Exception $e) {
			Nette\Diagnostics\Debugger::toStringException($e);
		}
	}



	/**
	 * @return array
	 */
	public function toArray()
	{
		$this->access(NULL);
		return $this->data;
	}



	/**
	 * Returns primary key value.
	 * @return mixed
	 */
	public function getPrimary()
	{
		if (!isset($this->data[$this->table->getPrimary()])) {
			throw new Nette\NotSupportedException("Table {$this->table->getName()} does not have any primary key.");
		}
		return $this[$this->table->getPrimary()];
	}



	/**
	 * Returns referenced row.
	 * @param  string
	 * @param  string
	 * @return ActiveRow or NULL if the row does not exist
	 */
	public function ref($key, $throughColumn = NULL)
	{
		if (!$throughColumn) {
			list($key, $throughColumn) = $this->table->getConnection()->getDatabaseReflection()->getBelongsToReference($this->table->getName(), $key);
		}

		return $this->getReference($key, $throughColumn);
	}



	/**
	 * Returns referencing rows.
	 * @param  string
	 * @param  string
	 * @return GroupedSelection
	 */
	public function related($key, $throughColumn = NULL)
	{
		if (strpos($key, '.') !== FALSE) {
			list($key, $throughColumn) = explode('.', $key);
		} elseif (!$throughColumn) {
			list($key, $throughColumn) = $this->table->getConnection()->getDatabaseReflection()->getHasManyReference($this->table->getName(), $key);
		}

		return $this->table->getReferencingTable($key, $throughColumn, $this[$this->table->getPrimary()]);
	}



	/**
	 * Updates row.
	 * @param  array or NULL for all modified values
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function update($data = NULL)
	{
		if ($data === NULL) {
			$data = $this->modified;
		}
		return $this->table->getConnection()->table($this->table->getName())
			->where($this->table->getPrimary(), $this[$this->table->getPrimary()])
			->update($data);
	}



	/**
	 * Deletes row.
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function delete()
	{
		return $this->table->getConnection()->table($this->table->getName())
			->where($this->table->getPrimary(), $this[$this->table->getPrimary()])
			->delete();
	}



	/********************* interface IteratorAggregate ****************d*g**/



	public function getIterator()
	{
		$this->access(NULL);
		return new \ArrayIterator($this->data);
	}



	/********************* interface ArrayAccess & magic accessors ****************d*g**/



	/**
	 * Stores value in column.
	 * @param  string column name
	 * @param  string value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->__set($key, $value);
	}



	/**
	 * Returns value of column.
	 * @param  string column name
	 * @return string
	 */
	public function offsetGet($key)
	{
		return $this->__get($key);
	}



	/**
	 * Tests if column exists.
	 * @param  string column name
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->__isset($key);
	}



	/**
	 * Removes column from data.
	 * @param  string column name
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->__unset($key);
	}



	public function __set($key, $value)
	{
		$this->data[$key] = $value;
		$this->modified[$key] = $value;
	}



	public function &__get($key)
	{
		$this->access($key);
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}

		list($table, $column) = $this->table->getConnection()->getDatabaseReflection()->getBelongsToReference($this->table->getName(), $key);
		$referenced = $this->getReference($table, $column);
		if ($referenced !== FALSE) {
			$this->access($key, FALSE);
			return $referenced;
		}

		$this->access($key, NULL);
		throw new Nette\MemberAccessException("Cannot read an undeclared column \"$key\".");
	}



	public function __isset($key)
	{
		$this->access($key);
		if (array_key_exists($key, $this->data)) {
			return isset($this->data[$key]);
		}
		$this->access($key, NULL);
		return FALSE;
	}



	public function __unset($key)
	{
		unset($this->data[$key]);
		unset($this->modified[$key]);
	}



	/**
	 * @internal
	 */
	public function access($key, $cache = TRUE)
	{
		if ($this->table->getConnection()->getCache() && !isset($this->modified[$key]) && $this->table->access($key, $cache)) {
			$id = (isset($this->data[$this->table->getPrimary()]) ? $this->data[$this->table->getPrimary()] : $this->data);
			$this->data = $this->table[$id]->data;
		}
	}



	protected function getReference($table, $column)
	{
		if (array_key_exists($column, $this->data)) {
			$this->access($column);

			$value = $this->data[$column];
			$value = $value instanceof ActiveRow ? $value->getPrimary() : $value;

			$referenced = $this->table->getReferencedTable($table, $column, !empty($this->modified[$column]));
			$referenced = isset($referenced[$value]) ? $referenced[$value] : NULL; // referenced row may not exist

			if (!empty($this->modified[$column])) { // cause saving changed column and prevent regenerating referenced table for $column
				$this->modified[$column] = 0; // 0 fails on empty, pass on isset
			}

			return $referenced;
		}

		return FALSE;
	}

}
