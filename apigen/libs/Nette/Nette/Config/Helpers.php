<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Config;

use Nette;



/**
 * Configuration helpers.
 *
 * @author     David Grudl
 */
class Helpers
{
	const EXTENDS_KEY = '_extends',
		OVERWRITE = TRUE;


	/**
	 * Merges configurations. Left has higher priority than right one.
	 * @return array
	 */
	public static function merge($left, $right)
	{
		if (is_array($left) && is_array($right)) {
			foreach ($left as $key => $val) {
				if (is_int($key)) {
					$right[] = $val;
				} else {
					if (is_array($val) && isset($val[self::EXTENDS_KEY])) {
						if ($val[self::EXTENDS_KEY] === self::OVERWRITE) {
							unset($val[self::EXTENDS_KEY]);
						}
					} elseif (isset($right[$key])) {
						$val = static::merge($val, $right[$key]);
					}
					$right[$key] = $val;
				}
			}
			return $right;

		} elseif ($left === NULL && is_array($right)) {
			return $right;

		} else {
			return $left;
		}
	}



	/**
	 * Finds out and removes information about the parent.
	 * @return mixed
	 */
	public static function takeParent(& $data)
	{
		if (is_array($data) && isset($data[self::EXTENDS_KEY])) {
			$parent = $data[self::EXTENDS_KEY];
			unset($data[self::EXTENDS_KEY]);
			return $parent;
		}
	}



	/**
	 * @return bool
	 */
	public static function isOverwriting(& $data)
	{
		return is_array($data) && isset($data[self::EXTENDS_KEY]) && $data[self::EXTENDS_KEY] === self::OVERWRITE;
	}



	/**
	 * @return bool
	 */
	public static function isInheriting(& $data)
	{
		return is_array($data) && isset($data[self::EXTENDS_KEY]) && $data[self::EXTENDS_KEY] !== self::OVERWRITE;
	}

}
