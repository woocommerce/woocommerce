<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Caching\Storages;

use Nette;



/**
 * Memory cache storage.
 *
 * @author     David Grudl
 */
class MemoryStorage extends Nette\Object implements Nette\Caching\IStorage
{
	/** @var array */
	private $data = array();



	/**
	 * Read from cache.
	 * @param  string key
	 * @return mixed|NULL
	 */
	public function read($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : NULL;
	}



	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 * @param  string key
	 * @return void
	 */
	public function lock($key)
	{
	}



	/**
	 * Writes item into the cache.
	 * @param  string key
	 * @param  mixed  data
	 * @param  array  dependencies
	 * @return void
	 */
	public function write($key, $data, array $dp)
	{
		$this->data[$key] = $data;
	}



	/**
	 * Removes item from the cache.
	 * @param  string key
	 * @return void
	 */
	public function remove($key)
	{
		unset($this->data[$key]);
	}



	/**
	 * Removes items from the cache by conditions & garbage collector.
	 * @param  array  conditions
	 * @return void
	 */
	public function clean(array $conds)
	{
		if (!empty($conds[Nette\Caching\Cache::ALL])) {
			$this->data = array();
		}
	}

}
