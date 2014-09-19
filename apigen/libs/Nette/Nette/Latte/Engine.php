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
 * Templating engine Latte.
 *
 * @author     David Grudl
 */
class Engine extends Nette\Object
{
	/** @var Parser */
	private $parser;

	/** @var Compiler */
	private $compiler;



	public function __construct()
	{
		$this->parser = new Parser;
		$this->compiler = new Compiler;
		$this->compiler->defaultContentType = Compiler::CONTENT_XHTML;

		Macros\CoreMacros::install($this->compiler);
		$this->compiler->addMacro('cache', new Macros\CacheMacro($this->compiler));
		Macros\UIMacros::install($this->compiler);
		Macros\FormMacros::install($this->compiler);
	}



	/**
	 * Invokes filter.
	 * @param  string
	 * @return string
	 */
	public function __invoke($s)
	{
		return $this->compiler->compile($this->parser->parse($s));
	}



	/**
	 * @return Parser
	 */
	public function getParser()
	{
		return $this->parser;
	}



	/**
	 * @return Compiler
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}

}
