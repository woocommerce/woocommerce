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
 * Callback recursive iterator filter.
 *
 * @author     David Grudl
 */
class RecursiveFilter extends \FilterIterator implements \RecursiveIterator
{
	/** @var callable */
	private $callback;

	/** @var callable */
	private $childrenCallback;


	/**
	 * Constructs a filter around another iterator.
	 * @param
	 * @param  callable
	 */
	public function __construct(\RecursiveIterator $iterator, $callback, $childrenCallback = NULL)
	{
		parent::__construct($iterator);
		$this->callback = $callback === NULL ? NULL : new Nette\Callback($callback);
		$this->childrenCallback = $childrenCallback === NULL ? NULL : new Nette\Callback($childrenCallback);
	}



	public function accept()
	{
		return $this->callback === NULL || $this->callback->invoke($this);
	}



	public function hasChildren()
	{
		return $this->getInnerIterator()->hasChildren()
			&& ($this->childrenCallback === NULL || $this->childrenCallback->invoke($this));
	}



	public function getChildren()
	{
		return new static($this->getInnerIterator()->getChildren(), $this->callback, $this->childrenCallback);
	}

}
