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
 * Component multiplier.
 *
 * @author     David Grudl
 */
class Multiplier extends PresenterComponent
{
	/** @var Nette\Callback */
	private $factory;


	public function __construct($factory)
	{
		parent::__construct();
		$this->factory = new Nette\Callback($factory);
	}



	protected function createComponent($name)
	{
		return $this->factory->invoke($name, $this);
	}

}
