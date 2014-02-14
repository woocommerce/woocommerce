<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Loaders;

use Nette;



/**
 * Auto loader is responsible for loading classes and interfaces.
 *
 * @author     David Grudl
 */
abstract class AutoLoader extends Nette\Object
{
	/** @var array  list of registered loaders */
	static private $loaders = array();

	/** @var int  for profiling purposes */
	public static $count = 0;



	/**
	 * Try to load the requested class.
	 * @param  string  class/interface name
	 * @return void
	 */
	final public static function load($type)
	{
		foreach (func_get_args() as $type) {
			if (!class_exists($type)) {
				throw new Nette\InvalidStateException("Unable to load class or interface '$type'.");
			}
		}
	}



	/**
	 * Return all registered autoloaders.
	 * @return AutoLoader[]
	 */
	final public static function getLoaders()
	{
		return array_values(self::$loaders);
	}



	/**
	 * Register autoloader.
	 * @return void
	 */
	public function register()
	{
		if (!function_exists('spl_autoload_register')) {
			throw new Nette\NotSupportedException('spl_autoload does not exist in this PHP installation.');
		}

		spl_autoload_register(array($this, 'tryLoad'));
		self::$loaders[spl_object_hash($this)] = $this;
	}



	/**
	 * Unregister autoloader.
	 * @return bool
	 */
	public function unregister()
	{
		unset(self::$loaders[spl_object_hash($this)]);
		return spl_autoload_unregister(array($this, 'tryLoad'));
	}



	/**
	 * Handles autoloading of classes or interfaces.
	 * @param  string
	 * @return void
	 */
	abstract public function tryLoad($type);

}
