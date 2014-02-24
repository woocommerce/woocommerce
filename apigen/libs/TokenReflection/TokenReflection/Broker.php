<?php
/**
 * PHP Token Reflection
 *
 * Version 1.3.1
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file LICENSE.
 *
 * @author Ondřej Nešpor
 * @author Jaroslav Hanslík
 */

namespace TokenReflection;

use TokenReflection\Broker, TokenReflection\Exception;
use RecursiveDirectoryIterator, RecursiveIteratorIterator;

// Detect if we have native traits support
define('NATIVE_TRAITS', defined('T_TRAIT'));
if (!NATIVE_TRAITS) {
	define('T_TRAIT', -1);
	define('T_TRAIT_C', -2);
	define('T_INSTEADOF', -3);
	define('T_CALLABLE', -4);
}

/**
 * Reflection broker.
 *
 * Parses files and directories and stores their structure.
 */
class Broker
{
	/**
	 * Turns on saving of parsed token streams.
	 *
	 * @var integer
	 */
	const OPTION_SAVE_TOKEN_STREAM = 0x0001;

	/**
	 * Turns on parsing function/method body.
	 *
	 * This effectively turns on parsing of static variables in functions/methods.
	 *
	 * @var integer
	 */
	const OPTION_PARSE_FUNCTION_BODY = 0x0002;

	/**
	 * Default options.
	 *
	 * @var integer
	 */
	const OPTION_DEFAULT = 0x0003;

	/**
	 * Cache identifier for namespaces.
	 *
	 * @var string
	 */
	const CACHE_NAMESPACE = 'namespace';

	/**
	 * Cache identifier for classes.
	 *
	 * @var string
	 */
	const CACHE_CLASS = 'class';

	/**
	 * Cache identifier for constants.
	 *
	 * @var string
	 */
	const CACHE_CONSTANT = 'constant';

	/**
	 * Cache identifier for functions.
	 *
	 * @var string
	 */
	const CACHE_FUNCTION = 'function';

	/**
	 * Namespace/class backend.
	 *
	 * @var \TokenReflection\Broker\Backend
	 */
	private $backend;

	/**
	 * Tokenized reflection objects cache.
	 *
	 * @var array
	 */
	private $cache;

	/**
	 * Broker/parser options.
	 *
	 * @var integer
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param \TokenReflection\Broker\Backend $backend Broker backend instance
	 * @param integer $options Broker/parsing options
	 */
	public function __construct(Broker\Backend $backend, $options = self::OPTION_DEFAULT)
	{
		$this->cache = array(
			self::CACHE_NAMESPACE => array(),
			self::CACHE_CLASS => array(),
			self::CACHE_CONSTANT => array(),
			self::CACHE_FUNCTION => array()
		);

		$this->options = $options;

		$this->backend = $backend
			->setBroker($this)
			->setStoringTokenStreams((bool) ($options & self::OPTION_SAVE_TOKEN_STREAM));
	}

	/**
	 * Returns broker/parser options.
	 *
	 * @return integer
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * Returns if a particular option setting is set.
	 *
	 * @param integer $option Option setting
	 * @return boolean
	 */
	public function isOptionSet($option)
	{
		return (bool) ($this->options & $option);
	}

	/**
	 * Parses a string with the PHP source code using the given file name and returns the appropriate reflection object.
	 *
	 * @param string $source PHP source code
	 * @param string $fileName Used file name
	 * @param boolean $returnReflectionFile Returns the appropriate \TokenReflection\ReflectionFile instance(s)
	 * @return boolean|\TokenReflection\ReflectionFile
	 */
	public function processString($source, $fileName, $returnReflectionFile = false)
	{
		if ($this->backend->isFileProcessed($fileName)) {
			$tokens = $this->backend->getFileTokens($fileName);
		} else {
			$tokens = new Stream\StringStream($source, $fileName);
		}

		$reflectionFile = new ReflectionFile($tokens, $this);
		if (!$this->backend->isFileProcessed($fileName)) {
			$this->backend->addFile($tokens, $reflectionFile);

			// Clear the cache - leave only tokenized reflections
			foreach ($this->cache as $type => $cached) {
				if (!empty($cached)) {
					$this->cache[$type] = array_filter($cached, function(IReflection $reflection) {
						return $reflection->isTokenized();
					});
				}
			}
		}

		return $returnReflectionFile ? $reflectionFile : true;
	}

	/**
	 * Parses a file and returns the appropriate reflection object.
	 *
	 * @param string $fileName Filename
	 * @param boolean $returnReflectionFile Returns the appropriate \TokenReflection\ReflectionFile instance(s)
	 * @return boolean|\TokenReflection\ReflectionFile
	 * @throws \TokenReflection\Exception\BrokerException If the file could not be processed.
	 */
	public function processFile($fileName, $returnReflectionFile = false)
	{
		try {
			if ($this->backend->isFileProcessed($fileName)) {
				$tokens = $this->backend->getFileTokens($fileName);
			} else {
				$tokens = new Stream\FileStream($fileName);
			}

			$reflectionFile = new ReflectionFile($tokens, $this);
			if (!$this->backend->isFileProcessed($fileName)) {
				$this->backend->addFile($tokens, $reflectionFile);

				// Clear the cache - leave only tokenized reflections
				foreach ($this->cache as $type => $cached) {
					if (!empty($cached)) {
						$this->cache[$type] = array_filter($cached, function(IReflection $reflection) {
							return $reflection->isTokenized();
						});
					}
				}
			}

			return $returnReflectionFile ? $reflectionFile : true;
		} catch (Exception\ParseException $e) {
			throw $e;
		} catch (Exception\StreamException $e) {
			throw new Exception\BrokerException($this, 'Could not process the file.', 0, $e);
		}
	}

	/**
	 * Processes a PHAR archive.
	 *
	 * @param string $fileName Archive filename.
	 * @param boolean $returnReflectionFile Returns the appropriate \TokenReflection\ReflectionFile instance(s)
	 * @return boolean|array of \TokenReflection\ReflectionFile
	 * @throws \TokenReflection\Exception\BrokerException If the PHAR PHP extension is not loaded.
	 * @throws \TokenReflection\Exception\BrokerException If the given archive could not be read.
	 * @throws \TokenReflection\Exception\BrokerException If the given archive could not be processed.
	 */
	public function processPhar($fileName, $returnReflectionFile = false)
	{
		if (!is_file($fileName)) {
			throw new Exception\BrokerException($this, 'File does not exist.', Exception\BrokerException::DOES_NOT_EXIST);
		}

		if (!extension_loaded('Phar')) {
			throw new Exception\BrokerException($this, 'The PHAR PHP extension is not loaded.', Exception\BrokerException::PHP_EXT_MISSING);
		}

		try {
			$result = array();
			foreach (new RecursiveIteratorIterator(new \Phar($fileName)) as $entry) {
				if ($entry->isFile()) {
					$result[$entry->getPathName()] = $this->processFile($entry->getPathName(), $returnReflectionFile);
				}
			}

			return $returnReflectionFile ? $result : true;
		} catch (Exception\ParseException $e) {
			throw $e;
		} catch (Exception\StreamException $e) {
			throw new Exception\BrokerException($this, 'Could not process the archive.', 0, $e);
		}
	}

	/**
	 * Processes recursively a directory and returns an array of file reflection objects.
	 *
	 * @param string $path Directora path
	 * @param string|array $filters Filename filters
	 * @param boolean $returnReflectionFile Returns the appropriate \TokenReflection\ReflectionFile instance(s)
	 * @return boolean|array of \TokenReflection\ReflectionFile
	 * @throws \TokenReflection\Exception\BrokerException If the given directory does not exist.
	 * @throws \TokenReflection\Exception\BrokerException If the given directory could not be processed.
	 */
	public function processDirectory($path, $filters = array(), $returnReflectionFile = false)
	{
		$realPath = realpath($path);
		if (!is_dir($realPath)) {
			throw new Exception\BrokerException($this, 'File does not exist.', Exception\BrokerException::DOES_NOT_EXIST);
		}

		try {
			$result = array();
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($realPath)) as $entry) {
				if ($entry->isFile()) {
					$process = empty($filters);
					if (!$process) {
						foreach ((array) $filters as $filter) {
							$whitelisting = '!' !== $filter{0};
							if (fnmatch($whitelisting ? $filter : substr($filter, 1), $entry->getPathName(), FNM_NOESCAPE)) {
								$process = $whitelisting;
							}
						}
					}

					if ($process) {
						$result[$entry->getPathName()] = $this->processFile($entry->getPathName(), $returnReflectionFile);
					}
				}
			}

			return $returnReflectionFile ? $result : true;
		} catch (Exception\ParseException $e) {
			throw $e;
		} catch (Exception\StreamException $e) {
			throw new Exception\BrokerException($this, 'Could not process the directory.', 0, $e);
		}
	}

	/**
	 * Process a file, directory or a PHAR archive.
	 *
	 * @param string $path Path
	 * @param boolean $returnReflectionFile Returns the appropriate \TokenReflection\ReflectionFile instance(s)
	 * @return boolean|array|\TokenReflection\ReflectionFile
	 * @throws \TokenReflection\Exception\BrokerException If the target does not exist.
	 */
	public function process($path, $returnReflectionFile = false)
	{
		if (is_dir($path)) {
			return $this->processDirectory($path, array(), $returnReflectionFile);
		} elseif (is_file($path)) {
			if (preg_match('~\\.phar(?:$|\\.)~i', $path)) {
				return $this->processPhar($path, $returnReflectionFile);
			}

			return $this->processFile($path, $returnReflectionFile);
		} else {
			throw new Exception\BrokerException($this, 'The given directory/file does not exist.', Exception\BrokerException::DOES_NOT_EXIST);
		}
	}

	/**
	 * Returns if the broker contains a namespace of the given name.
	 *
	 * @param string $namespaceName Namespace name
	 * @return boolean
	 */
	public function hasNamespace($namespaceName)
	{
		return isset($this->cache[self::CACHE_NAMESPACE][$namespaceName]) || $this->backend->hasNamespace($namespaceName);
	}

	/**
	 * Returns a reflection object of the given namespace.
	 *
	 * @param string $namespaceName Namespace name
	 * @return \TokenReflection\ReflectionNamespace|null
	 */
	public function getNamespace($namespaceName)
	{
		$namespaceName = ltrim($namespaceName, '\\');

		if (isset($this->cache[self::CACHE_NAMESPACE][$namespaceName])) {
			return $this->cache[self::CACHE_NAMESPACE][$namespaceName];
		}

		$namespace = $this->backend->getNamespace($namespaceName);
		if (null !== $namespace) {
			$this->cache[self::CACHE_NAMESPACE][$namespaceName] = $namespace;
		}

		return $namespace;
	}

	/**
	 * Returns if the broker contains a class of the given name.
	 *
	 * @param string $className Class name
	 * @return boolean
	 */
	public function hasClass($className)
	{
		return isset($this->cache[self::CACHE_CLASS][$className]) || $this->backend->hasClass($className);
	}

	/**
	 * Returns a reflection object of the given class (FQN expected).
	 *
	 * @param string $className CLass bame
	 * @return \TokenReflection\ReflectionClass|null
	 */
	public function getClass($className)
	{
		$className = ltrim($className, '\\');

		if (isset($this->cache[self::CACHE_CLASS][$className])) {
			return $this->cache[self::CACHE_CLASS][$className];
		}

		$this->cache[self::CACHE_CLASS][$className] = $this->backend->getClass($className);
		return $this->cache[self::CACHE_CLASS][$className];
	}

	/**
	 * Returns all classes from all namespaces.
	 *
	 * @param integer $types Returned class types (multiple values may be OR-ed)
	 * @return array
	 */
	public function getClasses($types = Broker\Backend::TOKENIZED_CLASSES)
	{
		return $this->backend->getClasses($types);
	}

	/**
	 * Returns if the broker contains a constant of the given name.
	 *
	 * @param string $constantName Constant name
	 * @return boolean
	 */
	public function hasConstant($constantName)
	{
		return isset($this->cache[self::CACHE_CONSTANT][$constantName]) || $this->backend->hasConstant($constantName);
	}

	/**
	 * Returns a reflection object of a constant (FQN expected).
	 *
	 * @param string $constantName Constant name
	 * @return \TokenReflection\ReflectionConstant|null
	 */
	public function getConstant($constantName)
	{
		$constantName = ltrim($constantName, '\\');

		if (isset($this->cache[self::CACHE_CONSTANT][$constantName])) {
			return $this->cache[self::CACHE_CONSTANT][$constantName];
		}

		if ($constant = $this->backend->getConstant($constantName)) {
			$this->cache[self::CACHE_CONSTANT][$constantName] = $constant;
		}

		return $constant;
	}

	/**
	 * Returns all constants from all namespaces.
	 *
	 * @return array
	 */
	public function getConstants()
	{
		return $this->backend->getConstants();
	}

	/**
	 * Returns if the broker contains a function of the given name.
	 *
	 * @param string $functionName Function name
	 * @return boolean
	 */
	public function hasFunction($functionName)
	{
		return isset($this->cache[self::CACHE_FUNCTION][$functionName]) || $this->backend->hasFunction($functionName);
	}

	/**
	 * Returns a reflection object of a function (FQN expected).
	 *
	 * @param string $functionName Function name
	 * @return \TokenReflection\ReflectionFunction|null
	 */
	public function getFunction($functionName)
	{
		$functionName = ltrim($functionName, '\\');

		if (isset($this->cache[self::CACHE_FUNCTION][$functionName])) {
			return $this->cache[self::CACHE_FUNCTION][$functionName];
		}

		if ($function = $this->backend->getFunction($functionName)) {
			$this->cache[self::CACHE_FUNCTION][$functionName] = $function;
		}

		return $function;
	}

	/**
	 * Returns all functions from all namespaces.
	 *
	 * @return array
	 */
	public function getFunctions()
	{
		return $this->backend->getFunctions();
	}

	/**
	 * Returns if the broker contains a file reflection of the given name.
	 *
	 * @param string $fileName File name
	 * @return boolean
	 */
	public function hasFile($fileName)
	{
		return $this->backend->hasFile($fileName);
	}

	/**
	 * Returns a reflection object of a file.
	 *
	 * @param string $fileName File name
	 * @return \TokenReflection\ReflectionFile|null
	 */
	public function getFile($fileName)
	{
		return $this->backend->getFile($fileName);
	}

	/**
	 * Returns all processed files reflections.
	 *
	 * @return array
	 */
	public function getFiles()
	{
		return $this->backend->getFiles();
	}

	/**
	 * Returns an array of tokens from a processed file.
	 *
	 * @param string $fileName File name
	 * @return \TokenReflection\Stream\StreamBase|null
	 */
	public function getFileTokens($fileName)
	{
		return $this->backend->getFileTokens($fileName);
	}

	/**
	 * Returns a real system path.
	 *
	 * @param string $path Source path
	 * @return string|boolean
	 */
	public static function getRealPath($path)
	{
		if (0 === strpos($path, 'phar://')) {
			return is_file($path) || is_dir($path) ? $path : false;
		} else {
			return realpath($path);
		}
	}
}
