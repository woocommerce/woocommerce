<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Config;

use Nette,
	Nette\DI\ContainerBuilder;



/**
 * Configurator compiling extension.
 *
 * @author     David Grudl
 * @property-read array $config
 * @property-read Nette\DI\ContainerBuilder $containerBuilder
 */
abstract class CompilerExtension extends Nette\Object
{
	/** @var Compiler */
	protected $compiler;

	/** @var string */
	protected $name;



	public function setCompiler(Compiler $compiler, $name)
	{
		$this->compiler = $compiler;
		$this->name = $name;
		return $this;
	}



	/**
	 * Returns extension configuration.
	 * @param  array default values.
	 * @param  bool  perform %parameters% expansion?
	 * @return array
	 */
	public function getConfig(array $defaults = NULL, $expand = TRUE)
	{
		$config = $this->compiler->getConfig();
		$config = isset($config[$this->name]) ? $config[$this->name] : array();
		unset($config['services'], $config['factories']);
		$config = Helpers::merge($config, $defaults);
		return $expand ? $this->compiler->getContainerBuilder()->expand($config) : $config;
	}



	/**
	 * @return Nette\DI\ContainerBuilder
	 */
	public function getContainerBuilder()
	{
		return $this->compiler->getContainerBuilder();
	}



	/**
	 * Reads configuration from file.
	 * @param  string  file name
	 * @return array
	 */
	public function loadFromFile($file)
	{
		$loader = new Loader;
		$res = $loader->load($file);
		$container = $this->compiler->getContainerBuilder();
		foreach ($loader->getDependencies() as $file) {
			$container->addDependency($file);
		}
		return $res;
	}



	/**
	 * Prepend extension name to identifier or service name.
	 * @param  string
	 * @return string
	 */
	public function prefix($id)
	{
		return substr_replace($id, $this->name . '.', substr($id, 0, 1) === '@' ? 1 : 0, 0);
	}



	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 * @return void
	 */
	public function loadConfiguration()
	{
	}



	/**
	 * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
	 * @return void
	 */
	public function beforeCompile()
	{
	}



	/**
	 * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
	 * @return void
	 */
	public function afterCompile(Nette\Utils\PhpGenerator\ClassType $class)
	{
	}

}
