<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\ComponentModel;

use Nette;



/**
 * Provides functionality required by all components.
 *
 * @author     David Grudl
 */
interface IComponent
{
	/** Separator for component names in path concatenation. */
	const NAME_SEPARATOR = '-';

	/**
	 * @return string
	 */
	function getName();

	/**
	 * Returns the container if any.
	 * @return IContainer|NULL
	 */
	function getParent();

	/**
	 * Sets the parent of this component.
	 * @param  IContainer
	 * @param  string
	 * @return void
	 */
	function setParent(IContainer $parent = NULL, $name = NULL);

}
