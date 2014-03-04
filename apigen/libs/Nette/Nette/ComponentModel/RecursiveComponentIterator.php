<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\ComponentModel;

use Nette;



/**
 * Recursive component iterator. See Container::getComponents().
 *
 * @author     David Grudl
 * @internal
 */
class RecursiveComponentIterator extends \RecursiveArrayIterator implements \Countable
{

	/**
	 * Has the current element has children?
	 * @return bool
	 */
	public function hasChildren()
	{
		return $this->current() instanceof IContainer;
	}



	/**
	 * The sub-iterator for the current element.
	 * @return \RecursiveIterator
	 */
	public function getChildren()
	{
		return $this->current()->getComponents();
	}



	/**
	 * Returns the count of elements.
	 * @return int
	 */
	public function count()
	{
		return iterator_count($this);
	}

}
