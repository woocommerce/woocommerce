<?php

/**
 * ApiGen 2.8.0 - API documentation generator for PHP 5.3+
 *
 * Copyright (c) 2010-2011 David Grudl (http://davidgrudl.com)
 * Copyright (c) 2011-2012 Jaroslav Hanslík (https://github.com/kukulich)
 * Copyright (c) 2011-2012 Ondřej Nešpor (https://github.com/Andrewsville)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace ApiGen;

use Nette\Utils\Neon;

/**
 * Configuration processing class.
 */
class Config
{
	/**
	 * Options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Parsed configuration.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	private static $defaultConfig = array(
		'config' => '',
		'source' => array(),
		'destination' => '',
		'extensions' => array('php'),
		'exclude' => array(),
		'skipDocPath' => array(),
		'skipDocPrefix' => array(),
		'charset' => array('auto'),
		'main' => '',
		'title' => '',
		'baseUrl' => '',
		'googleCseId' => '',
		'googleCseLabel' => '',
		'googleAnalytics' => '',
		'templateConfig' => '',
		'allowedHtml' => array('b', 'i', 'a', 'ul', 'ol', 'li', 'p', 'br', 'var', 'samp', 'kbd', 'tt'),
		'groups' => 'auto',
		'autocomplete' => array('classes', 'constants', 'functions'),
		'accessLevels' => array('public', 'protected'),
		'internal' => false,
		'php' => true,
		'tree' => true,
		'deprecated' => false,
		'todo' => false,
		'download' => false,
		'sourceCode' => true,
		'report' => '',
		'undocumented' => '',
		'wipeout' => true,
		'quiet' => false,
		'progressbar' => true,
		'colors' => true,
		'updateCheck' => true,
		'debug' => false
	);

	/**
	 * File or directory path options.
	 *
	 * @var array
	 */
	private static $pathOptions = array(
		'config',
		'source',
		'destination',
		'templateConfig',
		'report'
	);

	/**
	 * Possible values for options.
	 *
	 * @var array
	 */
	private static $possibleOptionsValues = array(
		'groups' => array('auto', 'namespaces', 'packages', 'none'),
		'autocomplete' => array('classes', 'constants', 'functions', 'methods', 'properties', 'classconstants'),
		'accessLevels' => array('public', 'protected', 'private')
	);

	/**
	 * Initializes default configuration.
	 */
	public function __construct()
	{
		$templateDir = self::isInstalledByPear() ? '@data_dir@' . DIRECTORY_SEPARATOR . 'ApiGen' : realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
		self::$defaultConfig['templateConfig'] = $templateDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'config.neon';
		self::$defaultConfig['colors'] = 'WIN' !== substr(PHP_OS, 0, 3);
		$this->config = self::$defaultConfig;
	}

	/**
	 * Processes command line options.
	 *
	 * @param array $options
	 * @return \ApiGen\Config
	 */
	public function processCliOptions(array $options)
	{
		while ($option = current($options)) {
			if (preg_match('~^--([a-z][-a-z]*[a-z])(?:=(.+))?$~', $option, $matches) || preg_match('~^-([a-z])=?(.*)~', $option, $matches)) {
				$name = $matches[1];

				if (!empty($matches[2])) {
					$value = $matches[2];
				} else {
					$next = next($options);
					if (false === $next || '-' === $next{0}) {
						prev($options);
						$value = '';
					} else {
						$value = $next;
					}
				}

				$this->options[$name][] = $value;
			}

			next($options);
		}
		$this->options = array_map(function($value) {
			return 1 === count($value) ? $value[0] : $value;
		}, $this->options);

		// Compatibility with ApiGen 1.0
		foreach (array('config', 'source', 'destination') as $option) {
			if (isset($this->options[$option{0}]) && !isset($this->options[$option])) {
				$this->options[$option] = $this->options[$option{0}];
			}
			unset($this->options[$option{0}]);
		}

		return $this;
	}

	/**
	 * Prepares configuration.
	 *
	 * @return \ApiGen\Config
	 * @throws \ApiGen\ConfigException If something in configuration is wrong.
	 */
	public function prepare()
	{
		// Command line options
		$cli = array();
		$translator = array();
		foreach ($this->options as $option => $value) {
			$converted = preg_replace_callback('~-([a-z])~', function($matches) {
				return strtoupper($matches[1]);
			}, $option);

			$cli[$converted] = $value;
			$translator[$converted] = $option;
		}

		$unknownOptions = array_keys(array_diff_key($cli, self::$defaultConfig));
		if (!empty($unknownOptions)) {
			$originalOptions = array_map(function($option) {
				return (1 === strlen($option) ? '-' : '--') . $option;
			}, array_values(array_diff_key($translator, self::$defaultConfig)));

			$message = count($unknownOptions) > 1
				? sprintf('Unknown command line options "%s"', implode('", "', $originalOptions))
				: sprintf('Unknown command line option "%s"', $originalOptions[0]);
			throw new ConfigException($message);
		}

		// Config file
		$neon = array();
		if (empty($this->options) && $this->defaultConfigExists()) {
			$this->options['config'] = $this->getDefaultConfigPath();
		}
		if (isset($this->options['config']) && is_file($this->options['config'])) {
			$neon = Neon::decode(file_get_contents($this->options['config']));
			foreach (self::$pathOptions as $option) {
				if (!empty($neon[$option])) {
					if (is_array($neon[$option])) {
						foreach ($neon[$option] as $key => $value) {
							$neon[$option][$key] = $this->getAbsolutePath($value);
						}
					} else {
						$neon[$option] = $this->getAbsolutePath($neon[$option]);
					}
				}
			}

			$unknownOptions = array_keys(array_diff_key($neon, self::$defaultConfig));
			if (!empty($unknownOptions)) {
				$message = count($unknownOptions) > 1
					? sprintf('Unknown config file options "%s"', implode('", "', $unknownOptions))
					: sprintf('Unknown config file option "%s"', $unknownOptions[0]);
				throw new ConfigException($message);
			}
		}

		// Merge options
		$this->config = array_merge(self::$defaultConfig, $neon, $cli);

		// Compatibility with old option name "undocumented"
		if (!isset($this->config['report']) && isset($this->config['undocumented'])) {
			$this->config['report'] = $this->config['undocumented'];
			unset($this->config['undocumented']);
		}

		foreach (self::$defaultConfig as $option => $valueDefinition) {
			if (is_array($this->config[$option]) && !is_array($valueDefinition)) {
				throw new ConfigException(sprintf('Option "%s" must be set only once', $option));
			}

			if (is_bool($this->config[$option]) && !is_bool($valueDefinition)) {
				throw new ConfigException(sprintf('Option "%s" expects value', $option));
			}

			if (is_bool($valueDefinition) && !is_bool($this->config[$option])) {
				// Boolean option
				$value = strtolower($this->config[$option]);
				if ('on' === $value || 'yes' === $value || 'true' === $value || '' === $value) {
					$value = true;
				} elseif ('off' === $value || 'no' === $value || 'false' === $value) {
					$value = false;
				}
				$this->config[$option] = (bool) $value;
			} elseif (is_array($valueDefinition)) {
				// Array option
				$this->config[$option] = array_unique((array) $this->config[$option]);
				foreach ($this->config[$option] as $key => $value) {
					$value = explode(',', $value);
					while (count($value) > 1) {
						array_push($this->config[$option], array_shift($value));
					}
					$this->config[$option][$key] = array_shift($value);
				}
				$this->config[$option] = array_filter($this->config[$option]);
			}

			// Check posssible values
			if (!empty(self::$possibleOptionsValues[$option])) {
				$values = self::$possibleOptionsValues[$option];

				if (is_array($valueDefinition)) {
					$this->config[$option] = array_filter($this->config[$option], function($value) use ($values) {
						return in_array($value, $values);
					});
				} elseif (!in_array($this->config[$option], $values)) {
					$this->config[$option] = '';
				}
			}
		}

		// Unify character sets
		$this->config['charset'] = array_map('strtoupper', $this->config['charset']);

		// Process options that specify a filesystem path
		foreach (self::$pathOptions as $option) {
			if (is_array($this->config[$option])) {
				array_walk($this->config[$option], function(&$value) {
					if (file_exists($value)) {
						$value = realpath($value);
					}
				});
				usort($this->config[$option], 'strcasecmp');
			} else {
				if (file_exists($this->config[$option])) {
					$this->config[$option] = realpath($this->config[$option]);
				}
			}
		}

		// Unify directory separators
		foreach (array('exclude', 'skipDocPath') as $option) {
			$this->config[$option] = array_map(function($mask) {
				return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $mask);
			}, $this->config[$option]);
			usort($this->config[$option], 'strcasecmp');
		}

		// Unify prefixes
		$this->config['skipDocPrefix'] = array_map(function($prefix) {
			return ltrim($prefix, '\\');
		}, $this->config['skipDocPrefix']);
		usort($this->config['skipDocPrefix'], 'strcasecmp');

		// Base url without slash at the end
		$this->config['baseUrl'] = rtrim($this->config['baseUrl'], '/');

		// No progressbar in quiet mode
		if ($this->config['quiet']) {
			$this->config['progressbar'] = false;
		}

		// Check
		$this->check();

		// Default template config
		$this->config['template'] = array(
			'require' => array(),
			'resources' => array(),
			'templates' => array(
				'common' => array(),
				'optional' => array()
			)
		);

		// Merge template config
		$this->config = array_merge_recursive($this->config, array('template' => Neon::decode(file_get_contents($fileName = $this->config['templateConfig']))));
		$this->config['template']['config'] = realpath($fileName);

		// Check template
		$this->checkTemplate();

		return $this;
	}

	/**
	 * Checks configuration.
	 *
	 * @return \ApiGen\Config
	 * @throws \ApiGen\ConfigException If something in configuration is wrong.
	 */
	private function check()
	{
		if (!empty($this->config['config']) && !is_file($this->config['config'])) {
			throw new ConfigException(sprintf('Config file "%s" doesn\'t exist', $this->config['config']));
		}

		if (empty($this->config['source'])) {
			throw new ConfigException('Source is not set');
		}
		foreach ($this->config['source'] as $source) {
			if (!file_exists($source)) {
				throw new ConfigException(sprintf('Source "%s" doesn\'t exist', $source));
			}
		}

		if (empty($this->config['destination'])) {
			throw new ConfigException('Destination is not set');
		}

		foreach ($this->config['extensions'] as $extension) {
			if (!preg_match('~^[a-z\\d]+$~i', $extension)) {
				throw new ConfigException(sprintf('Invalid file extension "%s"', $extension));
			}
		}

		if (!is_file($this->config['templateConfig'])) {
			throw new ConfigException(sprintf('Template config "%s" doesn\'t exist', $this->config['templateConfig']));
		}

		if (!empty($this->config['baseUrl']) && !preg_match('~^https?://(?:[-a-z0-9]+\.)+[a-z]{2,6}(?:/.*)?$~i', $this->config['baseUrl'])) {
			throw new ConfigException(sprintf('Invalid base url "%s"', $this->config['baseUrl']));
		}

		if (!empty($this->config['googleCseId']) && !preg_match('~^\d{21}:[-a-z0-9_]{11}$~', $this->config['googleCseId'])) {
			throw new ConfigException(sprintf('Invalid Google Custom Search ID "%s"', $this->config['googleCseId']));
		}

		if (!empty($this->config['googleAnalytics']) && !preg_match('~^UA\\-\\d+\\-\\d+$~', $this->config['googleAnalytics'])) {
			throw new ConfigException(sprintf('Invalid Google Analytics tracking code "%s"', $this->config['googleAnalytics']));
		}

		if (empty($this->config['groups'])) {
			throw new ConfigException('No supported groups value given');
		}

		if (empty($this->config['autocomplete'])) {
			throw new ConfigException('No supported autocomplete value given');
		}

		if (empty($this->config['accessLevels'])) {
			throw new ConfigException('No supported access level given');
		}

		return $this;
	}

	/**
	 * Checks template configuration.
	 *
	 * @return \ApiGen\Config
	 * @throws \ApiGen\ConfigException If something in template configuration is wrong.
	 */
	private function checkTemplate()
	{
		$require = $this->config['template']['require'];
		if (isset($require['min']) && !preg_match('~^\\d+(?:\\.\\d+){0,2}$~', $require['min'])) {
			throw new ConfigException(sprintf('Invalid minimal version definition "%s"', $require['min']));
		}
		if (isset($require['max']) && !preg_match('~^\\d+(?:\\.\\d+){0,2}$~', $require['max'])) {
			throw new ConfigException(sprintf('Invalid maximal version definition "%s"', $require['max']));
		}

		$isMinOk = function($min) {
			$min .= str_repeat('.0', 2 - substr_count($min, '.'));
			return version_compare($min, Generator::VERSION, '<=');
		};
		$isMaxOk = function($max) {
			$max .= str_repeat('.0', 2 - substr_count($max, '.'));
			return version_compare($max, Generator::VERSION, '>=');
		};

		if (isset($require['min'], $require['max']) && (!$isMinOk($require['min']) || !$isMaxOk($require['max']))) {
			throw new ConfigException(sprintf('The template requires version from "%s" to "%s", you are using version "%s"', $require['min'], $require['max'], Generator::VERSION));
		} elseif (isset($require['min']) && !$isMinOk($require['min'])) {
			throw new ConfigException(sprintf('The template requires version "%s" or newer, you are using version "%s"', $require['min'], Generator::VERSION));
		} elseif (isset($require['max']) && !$isMaxOk($require['max'])) {
			throw new ConfigException(sprintf('The template requires version "%s" or older, you are using version "%s"', $require['max'], Generator::VERSION));
		}

		foreach (array('main', 'optional') as $section) {
			foreach ($this->config['template']['templates'][$section] as $type => $config) {
				if (!isset($config['filename'])) {
					throw new ConfigException(sprintf('Filename for "%s" is not defined', $type));
				}
				if (!isset($config['template'])) {
					throw new ConfigException(sprintf('Template for "%s" is not defined', $type));
				}
				if (!is_file(dirname($this->config['templateConfig']) . DIRECTORY_SEPARATOR . $config['template'])) {
					throw new ConfigException(sprintf('Template for "%s" doesn\'t exist', $type));
				}
			}
		}

		return $this;
	}

	/**
	 * Returns default configuration file path.
	 *
	 * @return string
	 */
	private function getDefaultConfigPath()
	{
		return getcwd() . DIRECTORY_SEPARATOR . 'apigen.neon';
	}

	/**
	 * Checks if default configuration file exists.
	 *
	 * @return boolean
	 */
	private function defaultConfigExists()
	{
		return is_file($this->getDefaultConfigPath());
	}

	/**
	 * Returns absolute path.
	 *
	 * @param string $path Path
	 * @return string
	 */
	private function getAbsolutePath($path)
	{
		if (preg_match('~/|[a-z]:~Ai', $path)) {
			return $path;
		}

		return dirname($this->options['config']) . DIRECTORY_SEPARATOR . $path;
	}

	/**
	 * Checks if a configuration option exists.
	 *
	 * @param string $name Option name
	 * @return boolean
	 */
	public function __isset($name)
	{
		return isset($this->config[$name]);
	}

	/**
	 * Returns a configuration option value.
	 *
	 * @param string $name Option name
	 * @return mixed
	 */
	public function __get($name)
	{
		return isset($this->config[$name]) ? $this->config[$name] : null;
	}

	/**
	 * If the user requests help.
	 *
	 * @return boolean
	 */
	public function isHelpRequested()
	{
		if (empty($this->options) && !$this->defaultConfigExists()) {
			return true;
		}

		if (isset($this->options['h']) || isset($this->options['help'])) {
			return true;
		}

		return false;
	}

	/**
	 * Returns help.
	 *
	 * @return string
	 */
	public function getHelp()
	{
		return <<<"HELP"
Usage:
	apigen @option@--config@c <@value@path@c> [options]
	apigen @option@--source@c <@value@dir@c|@value@file@c> @option@--destination@c <@value@dir@c> [options]

Options:
	@option@--config@c|@option@-c@c        <@value@file@c>      Config file
	@option@--source@c|@option@-s@c        <@value@dir@c|@value@file@c>  Source file or directory to parse (can be used multiple times)
	@option@--destination@c|@option@-d@c   <@value@dir@c>       Directory where to save the generated documentation
	@option@--extensions@c       <@value@list@c>      List of allowed file extensions, default "@value@php@c"
	@option@--exclude@c          <@value@mask@c>      Mask (case sensitive) to exclude file or directory from processing (can be used multiple times)
	@option@--skip-doc-path@c    <@value@mask@c>      Don't generate documentation for elements from file or directory with this (case sensitive) mask (can be used multiple times)
	@option@--skip-doc-prefix@c  <@value@value@c>     Don't generate documentation for elements with this (case sensitive) name prefix (can be used multiple times)
	@option@--charset@c          <@value@list@c>      Character set of source files, default "@value@auto@c"
	@option@--main@c             <@value@value@c>     Main project name prefix
	@option@--title@c            <@value@value@c>     Title of generated documentation
	@option@--base-url@c         <@value@value@c>     Documentation base URL
	@option@--google-cse-id@c    <@value@value@c>     Google Custom Search ID
	@option@--google-cse-label@c <@value@value@c>     Google Custom Search label
	@option@--google-analytics@c <@value@value@c>     Google Analytics tracking code
	@option@--template-config@c  <@value@file@c>      Template config file, default "@value@{$this->config['templateConfig']}@c"
	@option@--allowed-html@c     <@value@list@c>      List of allowed HTML tags in documentation, default "@value@b,i,a,ul,ol,li,p,br,var,samp,kbd,tt@c"
	@option@--groups@c           <@value@value@c>     How should elements be grouped in the menu. Default value is "@value@auto@c" (namespaces if available, packages otherwise)
	@option@--autocomplete@c     <@value@list@c>      Element types for search input autocomplete. Default value is "@value@classes,constants,functions@c"
	@option@--access-levels@c    <@value@list@c>      Generate documentation for methods and properties with given access level, default "@value@public,protected@c"
	@option@--internal@c         <@value@yes@c|@value@no@c>    Generate documentation for elements marked as internal and display internal documentation parts, default "@value@no@c"
	@option@--php@c              <@value@yes@c|@value@no@c>    Generate documentation for PHP internal classes, default "@value@yes@c"
	@option@--tree@c             <@value@yes@c|@value@no@c>    Generate tree view of classes, interfaces, traits and exceptions, default "@value@yes@c"
	@option@--deprecated@c       <@value@yes@c|@value@no@c>    Generate documentation for deprecated elements, default "@value@no@c"
	@option@--todo@c             <@value@yes@c|@value@no@c>    Generate documentation of tasks, default "@value@no@c"
	@option@--source-code@c      <@value@yes@c|@value@no@c>    Generate highlighted source code files, default "@value@yes@c"
	@option@--download@c         <@value@yes@c|@value@no@c>    Add a link to download documentation as a ZIP archive, default "@value@no@c"
	@option@--report@c           <@value@file@c>      Save a checkstyle report of poorly documented elements into a file
	@option@--wipeout@c          <@value@yes@c|@value@no@c>    Wipe out the destination directory first, default "@value@yes@c"
	@option@--quiet@c            <@value@yes@c|@value@no@c>    Don't display scaning and generating messages, default "@value@no@c"
	@option@--progressbar@c      <@value@yes@c|@value@no@c>    Display progressbars, default "@value@yes@c"
	@option@--colors@c           <@value@yes@c|@value@no@c>    Use colors, default "@value@no@c" on Windows, "@value@yes@c" on other systems
	@option@--update-check@c     <@value@yes@c|@value@no@c>    Check for update, default "@value@yes@c"
	@option@--debug@c            <@value@yes@c|@value@no@c>    Display additional information in case of an error, default "@value@no@c"
	@option@--help@c|@option@-h@c                      Display this help

Only source and destination directories are required - either set explicitly or using a config file. Configuration parameters passed via command line have precedence over parameters from a config file.

Boolean options (those with possible values @value@yes@c|@value@no@c) do not have to have their values defined explicitly. Using @option@--debug@c and @option@--debug@c=@value@yes@c is exactly the same.

Some options can have multiple values. You can do so either by using them multiple times or by separating values by a comma. That means that writing @option@--source@c=@value@file1.php@c @option@--source@c=@value@file2.php@c or @option@--source@c=@value@file1.php,file2.php@c is exactly the same.

Files or directories specified by @option@--exclude@c will not be processed at all.
Elements from files within @option@--skip-doc-path@c or with @option@--skip-doc-prefix@c will be parsed but will not have their documentation generated. However if classes have any child classes, the full class tree will be generated and their inherited methods, properties and constants will be displayed (but will not be clickable).

HELP;
	}

	/**
	 * Checks if ApiGen is installed by PEAR.
	 *
	 * @return boolean
	 */
	public static function isInstalledByPear()
	{
		return false === strpos('@data_dir@', '@data_dir');
	}

	/**
	 * Checks if ApiGen is installed from downloaded archive.
	 *
	 * @return boolean
	 */
	public static function isInstalledByDownload()
	{
		return !self::isInstalledByPear();
	}
}
