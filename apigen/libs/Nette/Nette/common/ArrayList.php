<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette;

use Nette;



/**
 * Provides the base class for a generic list (items can be accessed by index).
 *
 * @author     David Grudl
 *
 * @property-read \ArrayIterator $iterator
 */
class ArrayList extends Object implements \ArrayAccess, \Countable, \IteratorAggregate
{
	private $list = array();



	/**
	 * Returns an iterator over all items.
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->list);
	}



	/**
	 * Returns items count.
	 * @return int
	 */
	public function count()
	{
		return count($this->list);
	}



	/**
	 * Replaces or appends a item.
	 * @param  int
	 * @param  mixed
	 * @return void
	 * @throws OutOfRangeException
	 */
	public function offsetSet($index, $value)
	{
		if ($index === NULL) {
			$this->list[] = $value;

		} elseif ($index < 0 || $index >= count($this->list)) {
			throw new OutOfRangeException("Offset invalid or out of range");

		} else {
			$this->list[(int) $index] = $value;
		}
	}



	/**
	 * Returns a item.
	 * @param  int
	 * @return mixed
	 * @throws OutOfRangeException
	 */
	public function offsetGet($index)
	{
		if ($index < 0 || $index >= count($this->list)) {
			throw new OutOfRangeException("Offset invalid or out of range");
		}
		return $this->list[(int) $index];
	}



	/**
	 * Determines whether a item exists.
	 * @param  int
	 * @return bool
	 */
	public function offsetExists($index)
	{
		return $index >= 0 && $index < count($this->list);
	}



	/**
	 * Removes the element at the specified position in this list.
	 * @param  int
	 * @return void
	 * @throws OutOfRangeException
	 */
	public function offsetUnset($index)
	{
		if ($index < 0 || $index >= count($this->list)) {
			throw new OutOfRangeException("Offset invalid or out of range");
		}
		array_splice($this->list, (int) $index, 1);
	}

}
