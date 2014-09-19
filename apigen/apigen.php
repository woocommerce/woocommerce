#!/usr/bin/env php
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

use Nette\Diagnostics\Debugger;
use TokenReflection;

// Safe locale and timezone
setlocale(LC_ALL, 'C');
if (!ini_get('date.timezone')) {
	date_default_timezone_set('UTC');
}

if (false === strpos('@php_dir@', '@php_dir')) {
	// PEAR package

	@include '@php_dir@/Nette/loader.php';
	@include '@php_dir@/Texy/texy.php';
} else {
	// Downloaded package

	set_include_path(
		__DIR__ . PATH_SEPARATOR .
		__DIR__ . '/libs/FSHL' . PATH_SEPARATOR .
		__DIR__ . '/libs/TokenReflection' . PATH_SEPARATOR .
		get_include_path()
	);

	@include __DIR__ . '/libs/Nette/Nette/loader.php';
	@include __DIR__ . '/libs/Texy/texy/texy.php';
}

// Autoload
spl_autoload_register(function($class) {
	$class = trim($class, '\\');
	require sprintf('%s.php', str_replace('\\', DIRECTORY_SEPARATOR, $class));
});

try {

	// Check dependencies
	foreach (array('json', 'iconv', 'mbstring', 'tokenizer') as $extension) {
		if (!extension_loaded($extension)) {
			printf("Required extension missing: %s\n", $extension);
			die(1);
		}
	}
	if (!class_exists('Nette\\Diagnostics\\Debugger')) {
		echo "Required dependency missing: Nette Framework\n";
		die(1);
	}
	if (!class_exists('Texy')) {
		echo "Required dependency missing: Texy library\n";
		die(1);
	}
	if (!class_exists('FSHL\\Highlighter')) {
		echo "Required dependency missing: FSHL library\n";
		die(1);
	}
	if (!class_exists('TokenReflection\\Broker')) {
		echo "Required dependency missing: TokenReflection library\n";
		die(1);
	}

	Debugger::$strictMode = true;
	Debugger::$onFatalError[] = function() {
		echo "\nFor more information turn on the debug mode using the --debug option.\n";
	};
	Debugger::enable(Debugger::PRODUCTION, false);

	$start = new \DateTime();

	$options = $_SERVER['argv'];
	array_shift($options);

	$config = new Config();
	$config->processCliOptions($options);
	$generator = new Generator($config);

	// Help
	if ($config->isHelpRequested()) {
		echo $generator->colorize($generator->getHeader());
		echo $generator->colorize($config->getHelp());
		die();
	}

	// Prepare configuration
	$config->prepare();

	if ($config->debug) {
		Debugger::$onFatalError = array();
		Debugger::enable(Debugger::DEVELOPMENT, false);
	}

	$generator->output($generator->getHeader());

	// Check for update (only in production mode)
	if ($config->updateCheck && !$config->debug) {
		ini_set('default_socket_timeout', 5);
		$latestVersion = @file_get_contents('http://pear.apigen.org/rest/r/apigen/latest.txt');
		if (false !== $latestVersion && version_compare(trim($latestVersion), Generator::VERSION, '>')) {
			$generator->output(sprintf("New version @header@%s@c available\n\n", $latestVersion));
		}
	}

	// Scan
	if (count($config->source) > 1) {
		$generator->output(sprintf("Scanning\n @value@%s@c\n", implode("\n ", $config->source)));
	} else {
		$generator->output(sprintf("Scanning @value@%s@c\n", $config->source[0]));
	}
	if (count($config->exclude) > 1) {
		$generator->output(sprintf("Excluding\n @value@%s@c\n", implode("\n ", $config->exclude)));
	} elseif (!empty($config->exclude)) {
		$generator->output(sprintf("Excluding @value@%s@c\n", $config->exclude[0]));
	}

	$parsed = $generator->parse();

	if (count($parsed->errors) > 1) {
		$generator->output(sprintf("@error@Found %d errors@c\n\n", count($parsed->errors)));

		$no = 1;
		foreach ($parsed->errors as $e) {

			if ($e instanceof TokenReflection\Exception\ParseException) {
				$generator->output(sprintf("@error@%d.@c The TokenReflection library threw an exception while parsing the file @value@%s@c.\n", $no, $e->getFileName()));
				if ($config->debug) {
					$generator->output("\nThis can have two reasons: a) the source code in the file is not valid or b) you have just found a bug in the TokenReflection library.\n\n");
					$generator->output("If the license allows it please send the whole file or at least the following fragment describing where exacly is the problem along with the backtrace to apigen@apigen.org. Thank you!\n\n");

					$token = $e->getToken();
					$sender = $e->getSender();
					if (!empty($token)) {
						$generator->output(
							sprintf(
								"The cause of the exception \"%s\" was the @value@%s@c token (line @count@%d@c) in following part of %s source code:\n\n",
								$e->getMessage(),
								$e->getTokenName(),
								$e->getExceptionLine(),
								$sender && $sender->getName() ? '@value@' . $sender->getPrettyName() . '@c' : 'the'
							)
						);
					} else {
						$generator->output(
							sprintf(
								"The exception \"%s\" was thrown when processing %s source code:\n\n",
								$e->getMessage(),
								$sender && $sender->getName() ? '@value@' . $sender->getPrettyName() . '@c' : 'the'
							)
						);
					}

					$generator->output($e->getSourcePart(true) . "\n\nThe exception backtrace is following:\n\n" . $e->getTraceAsString() . "\n\n");
				}
			} elseif ($e instanceof TokenReflection\Exception\FileProcessingException) {
				$generator->output(sprintf("@error@%d.@c %s\n", $no, $e->getMessage()));
				if ($config->debug) {
					$generator->output("\n" . $e->getDetail() . "\n\n");
				}
			} else {
				$generator->output(sprintf("@error@%d.@c %s\n", $no, $e->getMessage()));
				if ($config->debug) {
					$trace = $e->getTraceAsString();
					while ($e = $e->getPrevious()) {
						$generator->output(sprintf("\n%s", $e->getMessage()));
						$trace = $e->getTraceAsString();
					}
					$generator->output(sprintf("\n%s\n\n", $trace));
				}
			}

			$no++;
		}

		if (!$config->debug) {
			$generator->output("\nEnable the debug mode (@option@--debug@c) to see more details.\n\n");
		}
	}

	$generator->output(sprintf("Found @count@%d@c classes, @count@%d@c constants, @count@%d@c functions and other @count@%d@c used PHP internal classes\n", $parsed->classes, $parsed->constants, $parsed->functions, $parsed->internalClasses));
	$generator->output(sprintf("Documentation for @count@%d@c classes, @count@%d@c constants, @count@%d@c functions and other @count@%d@c used PHP internal classes will be generated\n", $parsed->documentedClasses, $parsed->documentedConstants, $parsed->documentedFunctions, $parsed->documentedInternalClasses));

	// Generating
	$generator->output(sprintf("Using template config file @value@%s@c\n", $config->templateConfig));

	if ($config->wipeout && is_dir($config->destination)) {
		$generator->output("Wiping out destination directory\n");
		if (!$generator->wipeOutDestination()) {
			throw new \RuntimeException('Cannot wipe out destination directory');
		}
	}

	$generator->output(sprintf("Generating to directory @value@%s@c\n", $config->destination));
	$skipping = array_merge($config->skipDocPath, $config->skipDocPrefix);
	if (count($skipping) > 1) {
		$generator->output(sprintf("Will not generate documentation for\n @value@%s@c\n", implode("\n ", $skipping)));
	} elseif (!empty($skipping)) {
		$generator->output(sprintf("Will not generate documentation for @value@%s@c\n", $skipping[0]));
	}
	$generator->generate();

	// End
	$end = new \DateTime();
	$interval = $end->diff($start);
	$parts = array();
	if ($interval->h > 0) {
		$parts[] = sprintf('@count@%d@c hours', $interval->h);
	}
	if ($interval->i > 0) {
		$parts[] = sprintf('@count@%d@c min', $interval->i);
	}
	if ($interval->s > 0) {
		$parts[] = sprintf('@count@%d@c sec', $interval->s);
	}
	if (empty($parts)) {
		$parts[] = sprintf('@count@%d@c sec', 1);
	}

	$duration = implode(' ', $parts);
	$generator->output(sprintf("Done. Total time: %s, used: @count@%d@c MB RAM\n", $duration, round(memory_get_peak_usage(true) / 1024 / 1024)));

} catch (ConfigException $e) {
	// Configuration error
	echo $generator->colorize($generator->getHeader() . sprintf("\n@error@%s@c\n\n", $e->getMessage()) . $config->getHelp());

	die(2);
} catch (\Exception $e) {
	// Everything else
	if ($config->debug) {
		do {
			echo $generator->colorize(sprintf("\n%s(%d): @error@%s@c", $e->getFile(), $e->getLine(), $e->getMessage()));
			$trace = $e->getTraceAsString();
		} while ($e = $e->getPrevious());

		printf("\n\n%s\n", $trace);
	} else {
		echo $generator->colorize(sprintf("\n@error@%s@c\n", $e->getMessage()));
	}

	die(1);
}