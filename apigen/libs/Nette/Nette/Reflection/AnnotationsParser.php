<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Reflection;

use Nette,
	Nette\Utils\Strings;



/**
 * Annotations support for PHP.
 *
 * @author     David Grudl
 * @Annotation
 */
final class AnnotationsParser
{
	/** @internal single & double quoted PHP string */
	const RE_STRING = '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"';

	/** @internal identifier */
	const RE_IDENTIFIER = '[_a-zA-Z\x7F-\xFF][_a-zA-Z0-9\x7F-\xFF-\\\]*';

	/** @var bool */
	public static $useReflection;

	/** @var array */
	public static $inherited = array('description', 'param', 'return');

	/** @var array */
	private static $cache;

	/** @var array */
	private static $timestamps;

	/** @var Nette\Caching\IStorage */
	private static $cacheStorage;



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}



	/**
	 * Returns annotations.
	 * @param  \ReflectionClass|\ReflectionMethod|\ReflectionProperty
	 * @return array
	 */
	public static function getAll(\Reflector $r)
	{
		if ($r instanceof \ReflectionClass) {
			$type = $r->getName();
			$member = '';

		} elseif ($r instanceof \ReflectionMethod) {
			$type = $r->getDeclaringClass()->getName();
			$member = $r->getName();

		} else {
			$type = $r->getDeclaringClass()->getName();
			$member = '$' . $r->getName();
		}

		if (!self::$useReflection) { // auto-expire cache
			$file = $r instanceof \ReflectionClass ? $r->getFileName() : $r->getDeclaringClass()->getFileName(); // will be used later
			if ($file && isset(self::$timestamps[$file]) && self::$timestamps[$file] !== filemtime($file)) {
				unset(self::$cache[$type]);
			}
			unset(self::$timestamps[$file]);
		}

		if (isset(self::$cache[$type][$member])) { // is value cached?
			return self::$cache[$type][$member];
		}

		if (self::$useReflection === NULL) { // detects whether is reflection available
			self::$useReflection = (bool) ClassType::from(__CLASS__)->getDocComment();
		}

		if (self::$useReflection) {
			$annotations = self::parseComment($r->getDocComment());

		} else {
			if (!self::$cacheStorage) {
				// trigger_error('Set a cache storage for annotations parser via Nette\Reflection\AnnotationParser::setCacheStorage().', E_USER_WARNING);
				self::$cacheStorage = new Nette\Caching\Storages\DevNullStorage;
			}
			$outerCache = new Nette\Caching\Cache(self::$cacheStorage, 'Nette.Reflection.Annotations');

			if (self::$cache === NULL) {
				self::$cache = (array) $outerCache->offsetGet('list');
				self::$timestamps = isset(self::$cache['*']) ? self::$cache['*'] : array();
			}

			if (!isset(self::$cache[$type]) && $file) {
				self::$cache['*'][$file] = filemtime($file);
				self::parseScript($file);
				$outerCache->save('list', self::$cache);
			}

			if (isset(self::$cache[$type][$member])) {
				$annotations = self::$cache[$type][$member];
			} else {
				$annotations = array();
			}
		}

		if ($r instanceof \ReflectionMethod && !$r->isPrivate()
			&& (!$r->isConstructor() || !empty($annotations['inheritdoc'][0])))
		{
			try {
				$inherited = self::getAll(new \ReflectionMethod(get_parent_class($type), $member));
			} catch (\ReflectionException $e) {
				try {
					$inherited = self::getAll($r->getPrototype());
				} catch (\ReflectionException $e) {
					$inherited = array();
				}
			}
			$annotations += array_intersect_key($inherited, array_flip(self::$inherited));
		}

		return self::$cache[$type][$member] = $annotations;
	}



	/**
	 * Parses phpDoc comment.
	 * @param  string
	 * @return array
	 */
	private static function parseComment($comment)
	{
		static $tokens = array('true' => TRUE, 'false' => FALSE, 'null' => NULL, '' => TRUE);

		$res = array();
		$comment = preg_replace('#^\s*\*\s?#ms', '', trim($comment, '/*'));
		$parts = preg_split('#^\s*(?=@'.self::RE_IDENTIFIER.')#m', $comment, 2);

		$description = trim($parts[0]);
		if ($description !== '') {
			$res['description'] = array($description);
		}

		$matches = Strings::matchAll(
			isset($parts[1]) ? $parts[1] : '',
			'~
				(?<=\s|^)@('.self::RE_IDENTIFIER.')[ \t]*      ##  annotation
				(
					\((?>'.self::RE_STRING.'|[^\'")@]+)+\)|  ##  (value)
					[^(@\r\n][^@\r\n]*|)                     ##  value
			~xi'
		);

		foreach ($matches as $match) {
			list(, $name, $value) = $match;

			if (substr($value, 0, 1) === '(') {
				$items = array();
				$key = '';
				$val = TRUE;
				$value[0] = ',';
				while ($m = Strings::match(
					$value,
					'#\s*,\s*(?>(' . self::RE_IDENTIFIER . ')\s*=\s*)?(' . self::RE_STRING . '|[^\'"),\s][^\'"),]*)#A')
				) {
					$value = substr($value, strlen($m[0]));
					list(, $key, $val) = $m;
					if ($val[0] === "'" || $val[0] === '"') {
						$val = substr($val, 1, -1);

					} elseif (is_numeric($val)) {
						$val = 1 * $val;

					} else {
						$lval = strtolower($val);
						$val = array_key_exists($lval, $tokens) ? $tokens[$lval] : $val;
					}

					if ($key === '') {
						$items[] = $val;

					} else {
						$items[$key] = $val;
					}
				}

				$value = count($items) < 2 && $key === '' ? $val : $items;

			} else {
				$value = trim($value);
				if (is_numeric($value)) {
					$value = 1 * $value;

				} else {
					$lval = strtolower($value);
					$value = array_key_exists($lval, $tokens) ? $tokens[$lval] : $value;
				}
			}

			$class = $name . 'Annotation';
			if (class_exists($class)) {
				$res[$name][] = new $class(is_array($value) ? $value : array('value' => $value));

			} else {
				$res[$name][] = is_array($value) ? new \ArrayObject($value, \ArrayObject::ARRAY_AS_PROPS) : $value;
			}
		}

		return $res;
	}



	/**
	 * Parses PHP file.
	 * @param  string
	 * @return void
	 */
	private static function parseScript($file)
	{
		$T_NAMESPACE = PHP_VERSION_ID < 50300 ? -1 : T_NAMESPACE;
		$T_NS_SEPARATOR = PHP_VERSION_ID < 50300 ? -1 : T_NS_SEPARATOR;

		$s = file_get_contents($file);

		if (Strings::match($s, '#//nette'.'loader=(\S*)#')) {
			return; // TODO: allways ignore?
		}

		$expected = $namespace = $class = $docComment = NULL;
		$level = $classLevel = 0;

		foreach (token_get_all($s) as $token) {

			if (is_array($token)) {
				switch ($token[0]) {
				case T_DOC_COMMENT:
					$docComment = $token[1];
				case T_WHITESPACE:
				case T_COMMENT:
					continue 2;

				case T_STRING:
				case $T_NS_SEPARATOR:
				case T_VARIABLE:
					if ($expected) {
						$name .= $token[1];
					}
					continue 2;

				case T_FUNCTION:
				case T_VAR:
				case T_PUBLIC:
				case T_PROTECTED:
				case $T_NAMESPACE:
				case T_CLASS:
				case T_INTERFACE:
					$expected = $token[0];
					$name = NULL;
					continue 2;

				case T_STATIC:
				case T_ABSTRACT:
				case T_FINAL:
					continue 2; // ignore in expectation

				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
					$level++;
				}
			}

			if ($expected) {
				switch ($expected) {
				case T_CLASS:
				case T_INTERFACE:
					$class = $namespace . $name;
					$classLevel = $level;
					$name = '';
					// break intentionally omitted
				case T_FUNCTION:
					if ($token === '&') {
						continue 2; // ignore
					}
				case T_VAR:
				case T_PUBLIC:
				case T_PROTECTED:
					if ($class && $name !== NULL && $docComment) {
						self::$cache[$class][$name] = self::parseComment($docComment);
					}
					break;

				case $T_NAMESPACE:
					$namespace = $name . '\\';
				}

				$expected = $docComment = NULL;
			}

			if ($token === ';') {
				$docComment = NULL;
			} elseif ($token === '{') {
				$docComment = NULL;
				$level++;
			} elseif ($token === '}') {
				$level--;
				if ($level === $classLevel) {
					$class = NULL;
				}
			}
		}
	}



	/********************* backend ****************d*g**/



	/**
	 * @param  Nette\Caching\IStorage
	 * @return void
	 */
	public static function setCacheStorage(Nette\Caching\IStorage $storage)
	{
		self::$cacheStorage = $storage;
	}



	/**
	 * @return Nette\Caching\IStorage
	 */
	public static function getCacheStorage()
	{
		return self::$cacheStorage;
	}

}
