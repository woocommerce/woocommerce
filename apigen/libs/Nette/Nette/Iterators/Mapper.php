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
 * Applies the callback to the elements of the inner iterator.
 *
 * @author     David Grudl
 */
class Mapper extends \IteratorIterator
{
	/** @var callable */
	private $callback;


	/**
	 * Constructs a filter around another iterator.
	 * @param
	 * @param  callable
	 */
	public function __construct(\Traversable $iterator, $callback)
	{
		parent::__construct($iterator);
		$this->callback = new Nette\Callback($callback);
	}



	public function current()
	{
		return $this->callback->invoke(parent::current(), parent::key());
	}

}
