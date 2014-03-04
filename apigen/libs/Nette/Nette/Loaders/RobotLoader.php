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

use Nette,
	Nette\Utils\Strings,
	Nette\Caching\Cache;



/**
 * Nette auto loader is responsible for loading classes and interfaces.
 *
 * @author     David Grudl
 *
 * @property-read array $indexedClasses
 * @property   Nette\Caching\IStorage $cacheStorage
 */
class RobotLoader extends AutoLoader
{
	const RETRY_LIMIT = 3;

	/** @var array */
	public $scanDirs = array();

	/** @var string|array  comma separated wildcards */
	public $ignoreDirs = '.*, *.old, *.bak, *.tmp, temp';

	/** @var string|array  comma separated wildcards */
	public $acceptFiles = '*.php, *.php5';

	/** @var bool */
	public $autoRebuild = TRUE;

	/** @var array of lowered-class => [file, mtime, class] or FALSE */
	private $list = array();

	/** @var array of file => mtime */
	private $files;

	/** @var bool */
	private $rebuilt = FALSE;

	/** @var array of checked classes in this request */
	private $checked = array();

	/** @var Nette\Caching\IStorage */
	private $cacheStorage;



	/**
	 */
	public function __construct()
	{
		if (!extension_loaded('tokenizer')) {
			throw new Nette\NotSupportedException("PHP extension Tokenizer is not loaded.");
		}
	}



	/**
	 * Register autoloader.
	 * @return RobotLoader  provides a fluent interface
	 */
	public function register()
	{
		$this->list = $this->getCache()->load($this->getKey(), new Nette\Callback($this, '_rebuildCallback'));
		parent::register();
		return $this;
	}



	/**
	 * Handles autoloading of classes, interfaces or traits.
	 * @param  string
	 * @return void
	 */
	public function tryLoad($type)
	{
		$type = ltrim(strtolower($type), '\\'); // PHP namespace bug #49143
		$info = & $this->list[$type];

		if ($this->autoRebuild && empty($this->checked[$type]) && (is_array($info) ? !is_file($info[0]) : $info < self::RETRY_LIMIT)) {
			$info = is_int($info) ? $info + 1 : 0;
			$this->checked[$type] = TRUE;
			if ($this->rebuilt) {
				$this->getCache()->save($this->getKey(), $this->list, array(
					Cache::CONSTS => 'Nette\Framework::REVISION',
				));
			} else {
				$this->rebuild();
			}
		}

		if (isset($info[0])) {
			Nette\Utils\LimitedScope::load($info[0], TRUE);

			if ($this->autoRebuild && !class_exists($type, FALSE) && !interface_exists($type, FALSE) && (PHP_VERSION_ID < 50400 || !trait_exists($type, FALSE))) {
				$info = 0;
				$this->checked[$type] = TRUE;
				if ($this->rebuilt) {
					$this->getCache()->save($this->getKey(), $this->list, array(
						Cache::CONSTS => 'Nette\Framework::REVISION',
					));
				} else {
					$this->rebuild();
				}
			}
			self::$count++;
		}
	}



	/**
	 * Rebuilds class list cache.
	 * @return void
	 */
	public function rebuild()
	{
		$this->getCache()->save($this->getKey(), new Nette\Callback($this, '_rebuildCallback'));
		$this->rebuilt = TRUE;
	}



	/**
	 * @internal
	 */
	public function _rebuildCallback(& $dp)
	{
		foreach ($this->list as $pair) {
			if (is_array($pair)) {
				$this->files[$pair[0]] = $pair[1];
			}
		}
		foreach (array_unique($this->scanDirs) as $dir) {
			$this->scanDirectory($dir);
		}
		$this->files = NULL;
		$dp = array(
			Cache::CONSTS => 'Nette\Framework::REVISION'
		);
		return $this->list;
	}



	/**
	 * @return array of class => filename
	 */
	public function getIndexedClasses()
	{
		$res = array();
		foreach ($this->list as $class => $pair) {
			if (is_array($pair)) {
				$res[$pair[2]] = $pair[0];
			}
		}
		return $res;
	}



	/**
	 * Add directory (or directories) to list.
	 * @param  string|array
	 * @return RobotLoader  provides a fluent interface
	 * @throws Nette\DirectoryNotFoundException if path is not found
	 */
	public function addDirectory($path)
	{
		foreach ((array) $path as $val) {
			$real = realpath($val);
			if ($real === FALSE) {
				throw new Nette\DirectoryNotFoundException("Directory '$val' not found.");
			}
			$this->scanDirs[] = $real;
		}
		return $this;
	}



	/**
	 * Add class and file name to the list.
	 * @param  string
	 * @param  string
	 * @param  int
	 * @return void
	 */
	private function addClass($class, $file, $time)
	{
		$lClass = strtolower($class);
		if (isset($this->list[$lClass][0]) && ($file2 = $this->list[$lClass][0]) !== $file && is_file($file2)) {
			if ($this->files[$file2] !== filemtime($file2)) {
				$this->scanScript($file2);
				return $this->addClass($class, $file, $time);
			}
			$e = new Nette\InvalidStateException("Ambiguous class '$class' resolution; defined in $file and in " . $this->list[$lClass][0] . ".");
			/*5.2*if (PHP_VERSION_ID < 50300) {
				Nette\Diagnostics\Debugger::_exceptionHandler($e);
				exit;
			} else*/ {
				throw $e;
			}
		}
		$this->list[$lClass] = array($file, $time, $class);
		$this->files[$file] = $time;
	}



	/**
	 * Scan a directory for PHP files, subdirectories and 'netterobots.txt' file.
	 * @param  string
	 * @return void
	 */
	private function scanDirectory($dir)
	{
		if (is_dir($dir)) {
			$ignoreDirs = is_array($this->ignoreDirs) ? $this->ignoreDirs : Strings::split($this->ignoreDirs, '#[,\s]+#');
			$disallow = array();
			foreach ($ignoreDirs as $item) {
				if ($item = realpath($item)) {
					$disallow[$item] = TRUE;
				}
			}
			$iterator = Nette\Utils\Finder::findFiles(is_array($this->acceptFiles) ? $this->acceptFiles : Strings::split($this->acceptFiles, '#[,\s]+#'))
				->filter(function($file) use (&$disallow){
					return !isset($disallow[$file->getPathname()]);
				})
				->from($dir)
				->exclude($ignoreDirs)
				->filter($filter = function($dir) use (&$disallow){
					$path = $dir->getPathname();
					if (is_file("$path/netterobots.txt")) {
						foreach (file("$path/netterobots.txt") as $s) {
							if ($matches = Strings::match($s, '#^(?:disallow\\s*:)?\\s*(\\S+)#i')) {
								$disallow[$path . str_replace('/', DIRECTORY_SEPARATOR, rtrim('/' . ltrim($matches[1], '/'), '/'))] = TRUE;
							}
						}
					}
					return !isset($disallow[$path]);
				});
			$filter(new \SplFileInfo($dir));
		} else {
			$iterator = new \ArrayIterator(array(new \SplFileInfo($dir)));
		}

		foreach ($iterator as $entry) {
			$path = $entry->getPathname();
			if (!isset($this->files[$path]) || $this->files[$path] !== $entry->getMTime()) {
				$this->scanScript($path);
			}
		}
	}



	/**
	 * Analyse PHP file.
	 * @param  string
	 * @return void
	 */
	private function scanScript($file)
	{
		$T_NAMESPACE = PHP_VERSION_ID < 50300 ? -1 : T_NAMESPACE;
		$T_NS_SEPARATOR = PHP_VERSION_ID < 50300 ? -1 : T_NS_SEPARATOR;
		$T_TRAIT = PHP_VERSION_ID < 50400 ? -1 : T_TRAIT;

		$expected = FALSE;
		$namespace = '';
		$level = $minLevel = 0;
		$time = filemtime($file);
		$s = file_get_contents($file);

		foreach ($this->list as $class => $pair) {
			if (is_array($pair) && $pair[0] === $file) {
				unset($this->list[$class]);
			}
		}

		if ($matches = Strings::match($s, '#//nette'.'loader=(\S*)#')) {
			foreach (explode(',', $matches[1]) as $name) {
				$this->addClass($name, $file, $time);
			}
			return;
		}

		foreach (@token_get_all($s) as $token) { // intentionally @
			if (is_array($token)) {
				switch ($token[0]) {
				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_WHITESPACE:
					continue 2;

				case $T_NS_SEPARATOR:
				case T_STRING:
					if ($expected) {
						$name .= $token[1];
					}
					continue 2;

				case $T_NAMESPACE:
				case T_CLASS:
				case T_INTERFACE:
				case $T_TRAIT:
					$expected = $token[0];
					$name = '';
					continue 2;
				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
					$level++;
				}
			}

			if ($expected) {
				switch ($expected) {
				case T_CLASS:
				case T_INTERFACE:
				case $T_TRAIT:
					if ($level === $minLevel) {
						$this->addClass($namespace . $name, $file, $time);
					}
					break;

				case $T_NAMESPACE:
					$namespace = $name ? $name . '\\' : '';
					$minLevel = $token === '{' ? 1 : 0;
				}

				$expected = NULL;
			}

			if ($token === '{') {
				$level++;
			} elseif ($token === '}') {
				$level--;
			}
		}
	}



	/********************* backend ****************d*g**/



	/**
	 * @param  Nette\Caching\IStorage
	 * @return RobotLoader
	 */
	public function setCacheStorage(Nette\Caching\IStorage $storage)
	{
		$this->cacheStorage = $storage;
		return $this;
	}



	/**
	 * @return Nette\Caching\IStorage
	 */
	public function getCacheStorage()
	{
		return $this->cacheStorage;
	}



	/**
	 * @return Nette\Caching\Cache
	 */
	protected function getCache()
	{
		if (!$this->cacheStorage) {
			trigger_error('Missing cache storage.', E_USER_WARNING);
			$this->cacheStorage = new Nette\Caching\Storages\DevNullStorage;
		}
		return new Cache($this->cacheStorage, 'Nette.RobotLoader');
	}



	/**
	 * @return string
	 */
	protected function getKey()
	{
		return array($this->ignoreDirs, $this->acceptFiles, $this->scanDirs);
	}

}
