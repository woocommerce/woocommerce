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
 * Callback iterator filter.
 *
 * @author     David Grudl
 */
class Filter extends \FilterIterator
{
	/** @var callable */
	private $callback;


	/**
	 * Constructs a filter around another iterator.
	 * @param
	 * @param  callable
	 */
	public function __construct(\Iterator $iterator, $callback)
	{
		parent::__construct($iterator);
		$this->callback = new Nette\Callback($callback);
	}



	public function accept()
	{
		return $this->callback->invoke($this);
	}

}
