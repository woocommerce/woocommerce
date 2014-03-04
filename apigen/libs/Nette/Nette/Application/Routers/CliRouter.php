<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\Routers;

use Nette,
	Nette\Application;



/**
 * The unidirectional router for CLI. (experimental)
 *
 * @author     David Grudl
 *
 * @property-read array $defaults
 */
class CliRouter extends Nette\Object implements Application\IRouter
{
	const PRESENTER_KEY = 'action';

	/** @var array */
	private $defaults;



	/**
	 * @param  array   default values
	 */
	public function __construct($defaults = array())
	{
		$this->defaults = $defaults;
	}



	/**
	 * Maps command line arguments to a Request object.
	 * @param  Nette\Http\IRequest
	 * @return Nette\Application\Request|NULL
	 */
	public function match(Nette\Http\IRequest $httpRequest)
	{
		if (empty($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
			return NULL;
		}

		$names = array(self::PRESENTER_KEY);
		$params = $this->defaults;
		$args = $_SERVER['argv'];
		array_shift($args);
		$args[] = '--';

		foreach ($args as $arg) {
			$opt = preg_replace('#/|-+#A', '', $arg);
			if ($opt === $arg) {
				if (isset($flag) || $flag = array_shift($names)) {
					$params[$flag] = $arg;
				} else {
					$params[] = $arg;
				}
				$flag = NULL;
				continue;
			}

			if (isset($flag)) {
				$params[$flag] = TRUE;
				$flag = NULL;
			}

			if ($opt !== '') {
				$pair = explode('=', $opt, 2);
				if (isset($pair[1])) {
					$params[$pair[0]] = $pair[1];
				} else {
					$flag = $pair[0];
				}
			}
		}

		if (!isset($params[self::PRESENTER_KEY])) {
			throw new Nette\InvalidStateException('Missing presenter & action in route definition.');
		}
		$presenter = $params[self::PRESENTER_KEY];
		if ($a = strrpos($presenter, ':')) {
			$params[self::PRESENTER_KEY] = substr($presenter, $a + 1);
			$presenter = substr($presenter, 0, $a);
		}

		return new Application\Request(
			$presenter,
			'CLI',
			$params
		);
	}



	/**
	 * This router is only unidirectional.
	 * @param  Nette\Application\Request
	 * @param  Nette\Http\Url
	 * @return NULL
	 */
	public function constructUrl(Application\Request $appRequest, Nette\Http\Url $refUrl)
	{
		return NULL;
	}



	/**
	 * Returns default values.
	 * @return array
	 */
	public function getDefaults()
	{
		return $this->defaults;
	}

}
