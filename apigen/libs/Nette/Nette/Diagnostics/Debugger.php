<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * Debugger: displays and logs errors.
 *
 * Behavior is determined by two factors: mode & output
 * - modes: production / development
 * - output: HTML / AJAX / CLI / other (e.g. XML)
 *
 * @author     David Grudl
 */
final class Debugger
{
	/** @var bool in production mode is suppressed any debugging output */
	public static $productionMode;

	/** @var bool in console mode is omitted HTML output */
	public static $consoleMode;

	/** @var int timestamp with microseconds of the start of the request */
	public static $time;

	/** @var bool is AJAX request detected? */
	private static $ajaxDetected;

	/** @var string  requested URI or command line */
	public static $source;

	/** @var string URL pattern mask to open editor */
	public static $editor = 'editor://open/?file=%file&line=%line';

	/** @var string command to open browser (use 'start ""' in Windows) */
	public static $browser;

	/********************* Debugger::dump() ****************d*g**/

	/** @var int  how many nested levels of array/object properties display {@link Debugger::dump()} */
	public static $maxDepth = 3;

	/** @var int  how long strings display {@link Debugger::dump()} */
	public static $maxLen = 150;

	/** @var bool display location? {@link Debugger::dump()} */
	public static $showLocation = FALSE;

	/** @var array */
	public static $consoleColors = array(
		'bool' => '1;33',
		'null' => '1;33',
		'int' => '1;36',
		'float' => '1;36',
		'string' => '1;32',
		'array' => '1;31',
		'key' => '1;37',
		'object' => '1;31',
		'visibility' => '1;30',
		'resource' => '1;37',
	);

	/********************* errors and exceptions reporting ****************d*g**/

	/** server modes {@link Debugger::enable()} */
	const DEVELOPMENT = FALSE,
		PRODUCTION = TRUE,
		DETECT = NULL;

	/** @var BlueScreen */
	public static $blueScreen;

	/** @var bool|int determines whether any error will cause immediate death; if integer that it's matched against error severity */
	public static $strictMode = FALSE; // $immediateDeath

	/** @var bool disables the @ (shut-up) operator so that notices and warnings are no longer hidden */
	public static $scream = FALSE;

	/** @var array of callables specifies the functions that are automatically called after fatal error */
	public static $onFatalError = array();

	/** @var bool {@link Debugger::enable()} */
	private static $enabled = FALSE;

	/** @var mixed {@link Debugger::tryError()} FALSE means catching is disabled */
	private static $lastError = FALSE;

	/********************* logging ****************d*g**/

	/** @var Logger */
	public static $logger;

	/** @var FireLogger */
	public static $fireLogger;

	/** @var string name of the directory where errors should be logged; FALSE means that logging is disabled */
	public static $logDirectory;

	/** @var string email to sent error notifications */
	public static $email;

	/** @deprecated */
	public static $mailer;

	/** @deprecated */
	public static $emailSnooze;

	/********************* debug bar ****************d*g**/

	/** @var Bar */
	public static $bar;

	/** @var DefaultBarPanel */
	private static $errorPanel;

	/** @var DefaultBarPanel */
	private static $dumpPanel;

	/********************* Firebug extension ****************d*g**/

	/** {@link Debugger::log()} and {@link Debugger::fireLog()} */
	const DEBUG = 'debug',
		INFO = 'info',
		WARNING = 'warning',
		ERROR = 'error',
		CRITICAL = 'critical';



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}



	/**
	 * Static class constructor.
	 * @internal
	 */
	public static function _init()
	{
		self::$time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(TRUE);
		self::$consoleMode = PHP_SAPI === 'cli';
		self::$productionMode = self::DETECT;
		if (self::$consoleMode) {
			self::$source = empty($_SERVER['argv']) ? 'cli' : 'cli: ' . implode(' ', $_SERVER['argv']);
		} else {
			self::$ajaxDetected = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
			if (isset($_SERVER['REQUEST_URI'])) {
				self::$source = (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
					. (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : ''))
					. $_SERVER['REQUEST_URI'];
			}
		}

		self::$logger = new Logger;
		self::$logDirectory = & self::$logger->directory;
		self::$email = & self::$logger->email;
		self::$mailer = & self::$logger->mailer;
		self::$emailSnooze = & Logger::$emailSnooze;

		self::$fireLogger = new FireLogger;

		self::$blueScreen = new BlueScreen;
		self::$blueScreen->addPanel(function($e) {
			if ($e instanceof Nette\Templating\FilterException) {
				return array(
					'tab' => 'Template',
					'panel' => '<p><b>File:</b> ' . Helpers::editorLink($e->sourceFile, $e->sourceLine)
					. '&nbsp; <b>Line:</b> ' . ($e->sourceLine ? $e->sourceLine : 'n/a') . '</p>'
					. ($e->sourceLine ? BlueScreen::highlightFile($e->sourceFile, $e->sourceLine) : '')
				);
			} elseif ($e instanceof Nette\Utils\NeonException && preg_match('#line (\d+)#', $e->getMessage(), $m)) {
				if ($item = Helpers::findTrace($e->getTrace(), 'Nette\Config\Adapters\NeonAdapter::load')) {
					return array(
						'tab' => 'NEON',
						'panel' => '<p><b>File:</b> ' . Helpers::editorLink($item['args'][0], $m[1]) . '&nbsp; <b>Line:</b> ' . $m[1] . '</p>'
							. BlueScreen::highlightFile($item['args'][0], $m[1])
					);
				} elseif ($item = Helpers::findTrace($e->getTrace(), 'Nette\Utils\Neon::decode')) {
					return array(
						'tab' => 'NEON',
						'panel' => BlueScreen::highlightPhp($item['args'][0], $m[1])
					);
				}
			}
		});

		self::$bar = new Bar;
		self::$bar->addPanel(new DefaultBarPanel('time'));
		self::$bar->addPanel(new DefaultBarPanel('memory'));
		self::$bar->addPanel(self::$errorPanel = new DefaultBarPanel('errors')); // filled by _errorHandler()
		self::$bar->addPanel(self::$dumpPanel = new DefaultBarPanel('dumps')); // filled by barDump()
	}



	/********************* errors and exceptions reporting ****************d*g**/



	/**
	 * Enables displaying or logging errors and exceptions.
	 * @param  mixed         production, development mode, autodetection or IP address(es) whitelist.
	 * @param  string        error log directory; enables logging in production mode, FALSE means that logging is disabled
	 * @param  string        administrator email; enables email sending in production mode
	 * @return void
	 */
	public static function enable($mode = NULL, $logDirectory = NULL, $email = NULL)
	{
		error_reporting(E_ALL | E_STRICT);

		// production/development mode detection
		if (is_bool($mode)) {
			self::$productionMode = $mode;

		} elseif ($mode !== self::DETECT || self::$productionMode === NULL) { // IP addresses or computer names whitelist detection
			$list = is_string($mode) ? preg_split('#[,\s]+#', $mode) : (array) $mode;
			if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$list[] = '127.0.0.1';
				$list[] = '::1';
			}
			self::$productionMode = !in_array(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : php_uname('n'), $list, TRUE);
		}

		// logging configuration
		if (is_string($logDirectory)) {
			self::$logDirectory = realpath($logDirectory);
			if (self::$logDirectory === FALSE) {
				throw new Nette\DirectoryNotFoundException("Directory '$logDirectory' is not found.");
			}
		} elseif ($logDirectory === FALSE) {
			self::$logDirectory = FALSE;

		} elseif (self::$logDirectory === NULL) {
			self::$logDirectory = defined('APP_DIR') ? APP_DIR . '/../log' : getcwd() . '/log';
		}
		if (self::$logDirectory) {
			ini_set('error_log', self::$logDirectory . '/php_error.log');
		}

		// php configuration
		if (function_exists('ini_set')) {
			ini_set('display_errors', !self::$productionMode); // or 'stderr'
			ini_set('html_errors', FALSE);
			ini_set('log_errors', FALSE);

		} elseif (ini_get('display_errors') != !self::$productionMode && ini_get('display_errors') !== (self::$productionMode ? 'stderr' : 'stdout')) { // intentionally ==
			throw new Nette\NotSupportedException('Function ini_set() must be enabled.');
		}

		if ($email) {
			if (!is_string($email)) {
				throw new Nette\InvalidArgumentException('Email address must be a string.');
			}
			self::$email = $email;
		}

		if (!defined('E_DEPRECATED')) {
			define('E_DEPRECATED', 8192);
		}

		if (!defined('E_USER_DEPRECATED')) {
			define('E_USER_DEPRECATED', 16384);
		}

		if (!self::$enabled) {
			register_shutdown_function(array(__CLASS__, '_shutdownHandler'));
			set_exception_handler(array(__CLASS__, '_exceptionHandler'));
			set_error_handler(array(__CLASS__, '_errorHandler'));
			self::$enabled = TRUE;
		}
	}



	/**
	 * Is Debug enabled?
	 * @return bool
	 */
	public static function isEnabled()
	{
		return self::$enabled;
	}



	/**
	 * Logs message or exception to file (if not disabled) and sends email notification (if enabled).
	 * @param  string|Exception
	 * @param  int  one of constant Debugger::INFO, WARNING, ERROR (sends email), CRITICAL (sends email)
	 * @return string logged error filename
	 */
	public static function log($message, $priority = self::INFO)
	{
		if (self::$logDirectory === FALSE) {
			return;

		} elseif (!self::$logDirectory) {
			throw new Nette\InvalidStateException('Logging directory is not specified in Nette\Diagnostics\Debugger::$logDirectory.');
		}

		if ($message instanceof \Exception) {
			$exception = $message;
			$message = ($message instanceof Nette\FatalErrorException
				? 'Fatal error: ' . $exception->getMessage()
				: get_class($exception) . ": " . $exception->getMessage())
				. " in " . $exception->getFile() . ":" . $exception->getLine();

			$hash = md5($exception /*5.2*. (method_exists($exception, 'getPrevious') ? $exception->getPrevious() : (isset($exception->previous) ? $exception->previous : ''))*/);
			$exceptionFilename = "exception-" . @date('Y-m-d-H-i-s') . "-$hash.html";
			foreach (new \DirectoryIterator(self::$logDirectory) as $entry) {
				if (strpos($entry, $hash)) {
					$exceptionFilename = $entry;
					$saved = TRUE;
					break;
				}
			}
		}

		self::$logger->log(array(
			@date('[Y-m-d H-i-s]'),
			trim($message),
			self::$source ? ' @  ' . self::$source : NULL,
			!empty($exceptionFilename) ? ' @@  ' . $exceptionFilename : NULL
		), $priority);

		if (!empty($exceptionFilename)) {
			$exceptionFilename = self::$logDirectory . '/' . $exceptionFilename;
			if (empty($saved) && $logHandle = @fopen($exceptionFilename, 'w')) {
				ob_start(); // double buffer prevents sending HTTP headers in some PHP
				ob_start(function($buffer) use ($logHandle) { fwrite($logHandle, $buffer); }, 4096);
				self::$blueScreen->render($exception);
				ob_end_flush();
				ob_end_clean();
				fclose($logHandle);
			}
			return strtr($exceptionFilename, '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
		}
	}



	/**
	 * Shutdown handler to catch fatal errors and execute of the planned activities.
	 * @return void
	 * @internal
	 */
	public static function _shutdownHandler()
	{
		if (!self::$enabled) {
			return;
		}

		// fatal error handler
		static $types = array(
			E_ERROR => 1,
			E_CORE_ERROR => 1,
			E_COMPILE_ERROR => 1,
			E_PARSE => 1,
		);
		$error = error_get_last();
		if (isset($types[$error['type']])) {
			self::_exceptionHandler(new Nette\FatalErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'], NULL));
		}

		// debug bar (require HTML & development mode)
		if (self::$bar && !self::$productionMode && self::isHtmlMode()) {
			self::$bar->render();
		}
	}



	/**
	 * Handler to catch uncaught exception.
	 * @param  \Exception
	 * @return void
	 * @internal
	 */
	public static function _exceptionHandler(\Exception $exception)
	{
		if (!headers_sent()) { // for PHP < 5.2.4
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
			header($protocol . ' 500', TRUE, 500);
		}

		try {
			if (self::$productionMode) {
				try {
					self::log($exception, self::ERROR);
				} catch (\Exception $e) {
					echo 'FATAL ERROR: unable to log error';
				}

				if (self::$consoleMode) {
					echo "ERROR: the server encountered an internal error and was unable to complete your request.\n";

				} elseif (self::isHtmlMode()) {
					require __DIR__ . '/templates/error.phtml';
				}

			} else {
				if (self::$consoleMode) { // dump to console
					echo "$exception\n";
					if ($file = self::log($exception)) {
						echo "(stored in $file)\n";
						if (self::$browser) {
							exec(self::$browser . ' ' . escapeshellarg($file));
						}
					}

				} elseif (self::isHtmlMode()) { // dump to browser
					self::$blueScreen->render($exception);
					if (self::$bar) {
						self::$bar->render();
					}

				} elseif (!self::fireLog($exception, self::ERROR)) { // AJAX or non-HTML mode
					$file = self::log($exception);
					if (!headers_sent()) {
						header("X-Nette-Error-Log: $file");
					}
				}
			}

			foreach (self::$onFatalError as $handler) {
				call_user_func($handler, $exception);
			}

		} catch (\Exception $e) {
			if (self::$productionMode) {
				echo self::isHtmlMode() ? '<meta name=robots content=noindex>FATAL ERROR' : 'FATAL ERROR';
			} else {
				echo "FATAL ERROR: thrown ", get_class($e), ': ', $e->getMessage(),
					"\nwhile processing ", get_class($exception), ': ', $exception->getMessage(), "\n";
			}
		}

		self::$enabled = FALSE; // un-register shutdown function
		exit(255);
	}



	/**
	 * Handler to catch warnings and notices.
	 * @param  int    level of the error raised
	 * @param  string error message
	 * @param  string file that the error was raised in
	 * @param  int    line number the error was raised at
	 * @param  array  an array of variables that existed in the scope the error was triggered in
	 * @return bool   FALSE to call normal error handler, NULL otherwise
	 * @throws Nette\FatalErrorException
	 * @internal
	 */
	public static function _errorHandler($severity, $message, $file, $line, $context)
	{
		if (self::$scream) {
			error_reporting(E_ALL | E_STRICT);
		}

		if (self::$lastError !== FALSE && ($severity & error_reporting()) === $severity) { // tryError mode
			self::$lastError = new \ErrorException($message, 0, $severity, $file, $line);
			return NULL;
		}

		if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
			throw new Nette\FatalErrorException($message, 0, $severity, $file, $line, $context);

		} elseif (($severity & error_reporting()) !== $severity) {
			return FALSE; // calls normal error handler to fill-in error_get_last()

		} elseif (!self::$productionMode && (is_bool(self::$strictMode) ? self::$strictMode : ((self::$strictMode & $severity) === $severity))) {
			self::_exceptionHandler(new Nette\FatalErrorException($message, 0, $severity, $file, $line, $context));
		}

		static $types = array(
			E_WARNING => 'Warning',
			E_COMPILE_WARNING => 'Warning', // currently unable to handle
			E_USER_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_USER_NOTICE => 'Notice',
			E_STRICT => 'Strict standards',
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'Deprecated',
		);

		$message = 'PHP ' . (isset($types[$severity]) ? $types[$severity] : 'Unknown error') . ": $message";
		$count = & self::$errorPanel->data["$message|$file|$line"];

		if ($count++) { // repeated error
			return NULL;

		} elseif (self::$productionMode) {
			self::log("$message in $file:$line", self::ERROR);
			return NULL;

		} else {
			$ok = self::fireLog(new \ErrorException($message, 0, $severity, $file, $line), self::WARNING);
			return !self::isHtmlMode() || (!self::$bar && !$ok) ? FALSE : NULL;
		}

		return FALSE; // call normal error handler
	}



	/**
	 * Handles exception thrown in __toString().
	 * @param  \Exception
	 * @return void
	 */
	public static function toStringException(\Exception $exception)
	{
		if (self::$enabled) {
			self::_exceptionHandler($exception);
		} else {
			trigger_error($exception->getMessage(), E_USER_ERROR);
		}
	}



	/**
	 * Starts catching potential errors/warnings.
	 * @return void
	 */
	public static function tryError()
	{
		if (!self::$enabled && self::$lastError === FALSE) {
			set_error_handler(array(__CLASS__, '_errorHandler'));
		}
		self::$lastError = NULL;
	}



	/**
	 * Returns catched error/warning message.
	 * @param  \ErrorException  catched error
	 * @return bool
	 */
	public static function catchError(& $error)
	{
		if (!self::$enabled && self::$lastError !== FALSE) {
			restore_error_handler();
		}
		$error = self::$lastError;
		self::$lastError = FALSE;
		return (bool) $error;
	}



	/********************* useful tools ****************d*g**/



	/**
	 * Dumps information about a variable in readable format.
	 * @param  mixed  variable to dump
	 * @param  bool   return output instead of printing it? (bypasses $productionMode)
	 * @return mixed  variable itself or dump
	 */
	public static function dump($var, $return = FALSE)
	{
		if (!$return && self::$productionMode) {
			return $var;
		}

		$output = "<pre class=\"nette-dump\">" . Helpers::htmlDump($var) . "</pre>\n";

		if (!$return) {
			$trace = /*5.2*PHP_VERSION_ID < 50205 ? debug_backtrace() : */debug_backtrace(FALSE);
			$i = Helpers::findTrace($trace, 'dump') ? 1 : 0;
			if (isset($trace[$i]['file'], $trace[$i]['line']) && is_file($trace[$i]['file'])) {
				$lines = file($trace[$i]['file']);
				preg_match('#dump\((.*)\)#', $lines[$trace[$i]['line'] - 1], $m);
				$output = substr_replace(
					$output,
					' title="' . htmlspecialchars((isset($m[0]) ? "$m[0] \n" : '') . "in file {$trace[$i]['file']} on line {$trace[$i]['line']}") . '"',
					4, 0);

				if (self::$showLocation) {
					$output = substr_replace(
						$output,
						' <small>in ' . Helpers::editorLink($trace[$i]['file'], $trace[$i]['line']) . ":{$trace[$i]['line']}</small>",
						-8, 0);
				}
			}
		}

		if (self::$consoleMode) {
			if (self::$consoleColors && substr(getenv('TERM'), 0, 5) === 'xterm') {
				$output = preg_replace_callback('#<span class="php-(\w+)">|</span>#', function($m) {
					return "\033[" . (isset($m[1], Debugger::$consoleColors[$m[1]]) ? Debugger::$consoleColors[$m[1]] : '0') . "m";
				}, $output);
			}
			$output = htmlspecialchars_decode(strip_tags($output), ENT_QUOTES);
		}

		if ($return) {
			return $output;

		} else {
			echo $output;
			return $var;
		}
	}



	/**
	 * Starts/stops stopwatch.
	 * @param  string  name
	 * @return float   elapsed seconds
	 */
	public static function timer($name = NULL)
	{
		static $time = array();
		$now = microtime(TRUE);
		$delta = isset($time[$name]) ? $now - $time[$name] : 0;
		$time[$name] = $now;
		return $delta;
	}



	/**
	 * Dumps information about a variable in Nette Debug Bar.
	 * @param  mixed  variable to dump
	 * @param  string optional title
	 * @return mixed  variable itself
	 */
	public static function barDump($var, $title = NULL)
	{
		if (!self::$productionMode) {
			$dump = array();
			foreach ((is_array($var) ? $var : array('' => $var)) as $key => $val) {
				$dump[$key] = Helpers::clickableDump($val);
			}
			self::$dumpPanel->data[] = array('title' => $title, 'dump' => $dump);
		}
		return $var;
	}



	/**
	 * Sends message to FireLogger console.
	 * @param  mixed   message to log
	 * @return bool    was successful?
	 */
	public static function fireLog($message)
	{
		if (!self::$productionMode) {
			return self::$fireLogger->log($message);
		}
	}



	private static function isHtmlMode()
	{
		return !self::$ajaxDetected && !self::$consoleMode
			&& !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));
	}



	/** @deprecated */
	public static function addPanel(IBarPanel $panel, $id = NULL)
	{
		return self::$bar->addPanel($panel, $id);
	}

}
