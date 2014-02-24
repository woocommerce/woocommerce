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
 * Class method description.
 *
 * @author     David Grudl
 *
 * @method Method setName(string $name)
 * @method Method setBody(string $body)
 * @method Method setStatic(bool $on)
 * @method Method setVisibility(string $access)
 * @method Method setFinal(bool $on)
 * @method Method setAbstract(bool $on)
 * @method Method setReturnReference(bool $on)
 * @method Method addDocument(string $doc)
 */
class Method extends Nette\Object
{
	/** @var string */
	public $name;

	/** @var array of name => Parameter */
	public $parameters = array();

	/** @var array of name => bool */
	public $uses = array();

	/** @var string|FALSE */
	public $body;

	/** @var bool */
	public $static;

	/** @var string  public|protected|private or none */
	public $visibility;

	/** @var bool */
	public $final;

	/** @var bool */
	public $abstract;

	/** @var bool */
	public $returnReference;

	/** @var array of string */
	public $documents = array();


	/** @return Parameter */
	public function addParameter($name, $defaultValue = NULL)
	{
		$param = new Parameter;
		if (func_num_args() > 1) {
			$param->setOptional(TRUE)->setDefaultValue($defaultValue);
		}
		return $this->parameters[] = $param->setName($name);
	}



	/** @return Parameter */
	public function addUse($name)
	{
		$param = new Parameter;
		return $this->uses[] = $param->setName($name);
	}



	/** @return Method */
	public function setBody($statement, array $args = NULL)
	{
		$this->body = func_num_args() > 1 ? Helpers::formatArgs($statement, $args) : $statement;
		return $this;
	}



	/** @return Method */
	public function addBody($statement, array $args = NULL)
	{
		$this->body .= (func_num_args() > 1 ? Helpers::formatArgs($statement, $args) : $statement) . "\n";
		return $this;
	}



	public function __call($name, $args)
	{
		return Nette\ObjectMixin::callProperty($this, $name, $args);
	}



	/** @return string  PHP code */
	public function __toString()
	{
		$parameters = array();
		foreach ($this->parameters as $param) {
			$parameters[] = ($param->typeHint ? $param->typeHint . ' ' : '')
				. ($param->reference ? '&' : '')
				. '$' . $param->name
				. ($param->optional ? ' = ' . Helpers::dump($param->defaultValue) : '');
		}
		$uses = array();
		foreach ($this->uses as $param) {
			$uses[] = ($param->reference ? '&' : '') . '$' . $param->name;
		}
		return ($this->documents ? str_replace("\n", "\n * ", "/**\n" . implode("\n", (array) $this->documents)) . "\n */\n" : '')
			. ($this->abstract ? 'abstract ' : '')
			. ($this->final ? 'final ' : '')
			. ($this->visibility ? $this->visibility . ' ' : '')
			. ($this->static ? 'static ' : '')
			. 'function'
			. ($this->returnReference ? ' &' : '')
			. ($this->name ? ' ' . $this->name : '')
			. '(' . implode(', ', $parameters) . ')'
			. ($this->uses ? ' use (' . implode(', ', $uses) . ')' : '')
			. ($this->abstract || $this->body === FALSE ? ';'
				: ($this->name ? "\n" : ' ') . "{\n" . Nette\Utils\Strings::indent(trim($this->body), 1) . "\n}");
	}

}
