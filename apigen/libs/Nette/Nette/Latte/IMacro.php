<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Latte;

use Nette;



/**
 * Latte macro.
 *
 * @author     David Grudl
 */
interface IMacro
{

	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	function initialize();

	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	function finalize();

	/**
	 * New node is found. Returns FALSE to reject.
	 * @return bool
	 */
	function nodeOpened(MacroNode $node);

	/**
	 * Node is closed.
	 * @return void
	 */
	function nodeClosed(MacroNode $node);

}
