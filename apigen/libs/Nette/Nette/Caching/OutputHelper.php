<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Caching;

use Nette;



/**
 * Output caching helper.
 *
 * @author     David Grudl
 */
class OutputHelper extends Nette\Object
{
	/** @var array */
	public $dependencies;

	/** @var Cache */
	private $cache;

	/** @var string */
	private $key;



	public function __construct(Cache $cache, $key)
	{
		$this->cache = $cache;
		$this->key = $key;
		ob_start();
	}



	/**
	 * Stops and saves the cache.
	 * @param  array  dependencies
	 * @return void
	 */
	public function end(array $dp = NULL)
	{
		if ($this->cache === NULL) {
			throw new Nette\InvalidStateException('Output cache has already been saved.');
		}
		$this->cache->save($this->key, ob_get_flush(), (array) $dp + (array) $this->dependencies);
		$this->cache = NULL;
	}

}
