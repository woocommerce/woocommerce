<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\UI;

use Nette;



/**
 * Lazy encapsulation of PresenterComponent::link().
 * Do not instantiate directly, use PresenterComponent::lazyLink()
 *
 * @author     David Grudl
 * @internal
 *
 * @property-read string $destination
 * @property-read array $parameters
 */
class Link extends Nette\Object
{
	/** @var PresenterComponent */
	private $component;

	/** @var string */
	private $destination;

	/** @var array */
	private $params;


	/**
	 * Link specification.
	 * @param  PresenterComponent
	 * @param  string
	 * @param  array
	 */
	public function __construct(PresenterComponent $component, $destination, array $params)
	{
		$this->component = $component;
		$this->destination = $destination;
		$this->params = $params;
	}



	/**
	 * Returns link destination.
	 * @return string
	 */
	public function getDestination()
	{
		return $this->destination;
	}



	/**
	 * Changes link parameter.
	 * @param  string
	 * @param  mixed
	 * @return Link  provides a fluent interface
	 */
	public function setParameter($key, $value)
	{
		$this->params[$key] = $value;
		return $this;
	}



	/**
	 * Returns link parameter.
	 * @param  string
	 * @return mixed
	 */
	public function getParameter($key)
	{
		return isset($this->params[$key]) ? $this->params[$key] : NULL;
	}



	/**
	 * Returns link parameters.
	 * @return array
	 */
	public function getParameters()
	{
		return $this->params;
	}



	/**
	 * Converts link to URL.
	 * @return string
	 */
	public function __toString()
	{
		try {
			return $this->component->link($this->destination, $this->params);

		} catch (\Exception $e) {
			Nette\Diagnostics\Debugger::toStringException($e);
		}
	}

}
