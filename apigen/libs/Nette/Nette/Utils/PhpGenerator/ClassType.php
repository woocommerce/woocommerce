<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Utils\PhpGenerator;

use Nette;



/**
 * Class/Interface/Trait description.
 *
 * @author     David Grudl
 *
 * @method ClassType setName(string $name)
 * @method ClassType setType(string $type)
 * @method ClassType setFinal(bool $on)
 * @method ClassType setAbstract(bool $on)
 * @method ClassType addExtend(string $class)
 * @method ClassType addImplement(string $interface)
 * @method ClassType addTrait(string $trait)
 * @method ClassType addDocument(string $doc)
 */
class ClassType extends Nette\Object
{
	/** @var string */
	public $name;

	/** @var string  class|interface|trait */
	public $type = 'class';

	/** @var bool */
	public $final;

	/** @var bool */
	public $abstract;

	/** @var string[] */
	public $extends = array();

	/** @var string[] */
	public $implements = array();

	/** @var string[] */
	public $traits = array();

	/** @var string[] */
	public $documents = array();

	/** @var mixed[] name => value */
	public $consts = array();

	/** @var Property[] name => Property */
	public $properties = array();

	/** @var Method[] name => Method */
	public $methods = array();


	public function __construct($name)
	{
		$this->name = $name;
	}



	/** @return ClassType */
	public function addConst($name, $value)
	{
		$this->consts[$name] = $value;
		return $this;
	}



	/** @return Property */
	public function addProperty($name, $value = NULL)
	{
		$property = new Property;
		return $this->properties[$name] = $property->setName($name)->setValue($value);
	}



	/** @return Method */
	public function addMethod($name)
	{
		$method = new Method;
		if ($this->type === 'interface') {
			$method->setVisibility('')->setBody(FALSE);
		} else {
			$method->setVisibility('public');
		}
		return $this->methods[$name] = $method->setName($name);
	}



	public function __call($name, $args)
	{
		return Nette\ObjectMixin::callProperty($this, $name, $args);
	}



	/** @return string  PHP code */
	public function __toString()
	{
		$consts = array();
		foreach ($this->consts as $name => $value) {
			$consts[] = "const $name = " . Helpers::dump($value) . ";\n";
		}
		$properties = array();
		foreach ($this->properties as $property) {
			$properties[] = ($property->documents ? str_replace("\n", "\n * ", "/**\n" . implode("\n", (array) $property->documents)) . "\n */\n" : '')
				. $property->visibility . ($property->static ? ' static' : '') . ' $' . $property->name
				. ($property->value === NULL ? '' : ' = ' . Helpers::dump($property->value))
				. ";\n";
		}
		return Nette\Utils\Strings::normalize(
			($this->documents ? str_replace("\n", "\n * ", "/**\n" . implode("\n", (array) $this->documents)) . "\n */\n" : '')
			. ($this->abstract ? 'abstract ' : '')
			. ($this->final ? 'final ' : '')
			. $this->type . ' '
			. $this->name . ' '
			. ($this->extends ? 'extends ' . implode(', ', (array) $this->extends) . ' ' : '')
			. ($this->implements ? 'implements ' . implode(', ', (array) $this->implements) . ' ' : '')
			. "\n{\n\n"
			. Nette\Utils\Strings::indent(
				($this->traits ? "use " . implode(', ', (array) $this->traits) . ";\n\n" : '')
				. ($this->consts ? implode('', $consts) . "\n\n" : '')
				. ($this->properties ? implode("\n", $properties) . "\n\n" : '')
				. implode("\n\n\n", $this->methods), 1)
			. "\n\n}") . "\n";
	}

}
