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
 * PHP files cache storage.
 *
 * @author     David Grudl
 */
class PhpFileStorage extends FileStorage
{
	/** @var string */
	public $hint;


	/**
	 * Reads cache data from disk.
	 * @param  array
	 * @return mixed
	 */
	protected function readData($meta)
	{
		return array(
			'file' => $meta[self::FILE],
			'handle' => $meta[self::HANDLE],
		);
	}



	/**
	 * Returns file name.
	 * @param  string
	 * @return string
	 */
	protected function getCacheFile($key)
	{
		return parent::getCacheFile(substr_replace(
			$key,
			trim(strtr($this->hint, '\\/@', '.._'), '.') . '-',
			strpos($key, Nette\Caching\Cache::NAMESPACE_SEPARATOR) + 1,
			0
		)) . '.php';
	}

}
