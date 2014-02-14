<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Utils;

use Nette;



/**
 * Array tools library.
 *
 * @author     David Grudl
 */
final class Arrays
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}



	/**
	 * Returns array item or $default if item is not set.
	 * Example: $val = Arrays::get($arr, 'i', 123);
	 * @param  mixed  array
	 * @param  mixed  key
	 * @param  mixed  default value
	 * @return mixed
	 */
	public static function get(array $arr, $key, $default = NULL)
	{
		foreach (is_array($key) ? $key : array($key) as $k) {
			if (is_array($arr) && array_key_exists($k, $arr)) {
				$arr = $arr[$k];
			} else {
				if (func_num_args() < 3) {
					throw new Nette\InvalidArgumentException("Missing item '$k'.");
				}
				return $default;
			}
		}
		return $arr;
	}



	/**
	 * Returns reference to array item or $default if item is not set.
	 * @param  mixed  array
	 * @param  mixed  key
	 * @return mixed
	 */
	public static function & getRef(& $arr, $key)
	{
		foreach (is_array($key) ? $key : array($key) as $k) {
			if (is_array($arr) || $arr === NULL) {
				$arr = & $arr[$k];
			} else {
				throw new Nette\InvalidArgumentException('Traversed item is not an array.');
			}
		}
		return $arr;
	}



	/**
	 * Recursively appends elements of remaining keys from the second array to the first.
	 * @param  array
	 * @param  array
	 * @return array
	 */
	public static function mergeTree($arr1, $arr2)
	{
		$res = $arr1 + $arr2;
		foreach (array_intersect_key($arr1, $arr2) as $k => $v) {
			if (is_array($v) && is_array($arr2[$k])) {
				$res[$k] = self::mergeTree($v, $arr2[$k]);
			}
		}
		return $res;
	}



	/**
	 * Searches the array for a given key and returns the offset if successful.
	 * @param  array  input array
	 * @param  mixed  key
	 * @return int    offset if it is found, FALSE otherwise
	 */
	public static function searchKey($arr, $key)
	{
		$foo = array($key => NULL);
		return array_search(key($foo), array_keys($arr), TRUE);
	}



	/**
	 * Inserts new array before item specified by key.
	 * @param  array  input array
	 * @param  mixed  key
	 * @param  array  inserted array
	 * @return void
	 */
	public static function insertBefore(array &$arr, $key, array $inserted)
	{
		$offset = self::searchKey($arr, $key);
		$arr = array_slice($arr, 0, $offset, TRUE) + $inserted + array_slice($arr, $offset, count($arr), TRUE);
	}



	/**
	 * Inserts new array after item specified by key.
	 * @param  array  input array
	 * @param  mixed  key
	 * @param  array  inserted array
	 * @return void
	 */
	public static function insertAfter(array &$arr, $key, array $inserted)
	{
		$offset = self::searchKey($arr, $key);
		$offset = $offset === FALSE ? count($arr) : $offset + 1;
		$arr = array_slice($arr, 0, $offset, TRUE) + $inserted + array_slice($arr, $offset, count($arr), TRUE);
	}



	/**
	 * Renames key in array.
	 * @param  array
	 * @param  mixed  old key
	 * @param  mixed  new key
	 * @return void
	 */
	public static function renameKey(array &$arr, $oldKey, $newKey)
	{
		$offset = self::searchKey($arr, $oldKey);
		if ($offset !== FALSE) {
			$keys = array_keys($arr);
			$keys[$offset] = $newKey;
			$arr = array_combine($keys, $arr);
		}
	}



	/**
	 * Returns array entries that match the pattern.
	 * @param  array
	 * @param  string
	 * @param  int
	 * @return array
	 */
	public static function grep(array $arr, $pattern, $flags = 0)
	{
		Nette\Diagnostics\Debugger::tryError();
		$res = preg_grep($pattern, $arr, $flags);
		if (Nette\Diagnostics\Debugger::catchError($e) || preg_last_error()) { // compile error XOR run-time error
			throw new RegexpException($e ? $e->getMessage() : NULL, $e ? NULL : preg_last_error(), $pattern);
		}
		return $res;
	}



	/**
	 * Returns flattened array.
	 * @param  array
	 * @return array
	 */
	public static function flatten(array $arr)
	{
		$res = array();
		array_walk_recursive($arr, function($a) use (& $res) { $res[] = $a; });
		return $res;
	}

}
