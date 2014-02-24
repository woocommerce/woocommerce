<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Config\Extensions;

use Nette,
	Nette\DI\ContainerBuilder;



/**
 * Constant definitions.
 *
 * @author     David Grudl
 */
class ConstantsExtension extends Nette\Config\CompilerExtension
{

	public function afterCompile(Nette\Utils\PhpGenerator\ClassType $class)
	{
		foreach ($this->getConfig() as $name => $value) {
			$class->methods['initialize']->addBody('define(?, ?);', array($name, $value));
		}
	}

}
