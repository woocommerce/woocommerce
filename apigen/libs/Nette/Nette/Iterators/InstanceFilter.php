<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Iterators;

use Nette;



/**
 * Instance iterator filter.
 *
 * @author     David Grudl
 */
class InstanceFilter extends \FilterIterator implements \Countable
{
	/** @var string */
	private $type;


	/**
	 * Constructs a filter around another iterator.
	 * @param  \Iterator
	 * @param  string  class/interface name
	 */
	public function __construct(\Iterator $iterator, $type)
	{
		$this->type = $type;
		parent::__construct($iterator);
	}



	/**
	 * Expose the current element of the inner iterator?
	 * @return bool
	 */
	public function accept()
	{
		return $this->current() instanceof $this->type;
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
