<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Forms;

use Nette;



/**
 * List of validation & condition rules.
 *
 * @author     David Grudl
 */
final class Rules extends Nette\Object implements \IteratorAggregate
{
	/** @internal */
	const VALIDATE_PREFIX = 'validate';

	/** @var array */
	public static $defaultMessages = array(
		Form::PROTECTION => 'Security token did not match. Possible CSRF attack.',
		Form::EQUAL => 'Please enter %s.',
		Form::FILLED => 'Please complete mandatory field.',
		Form::MIN_LENGTH => 'Please enter a value of at least %d characters.',
		Form::MAX_LENGTH => 'Please enter a value no longer than %d characters.',
		Form::LENGTH => 'Please enter a value between %d and %d characters long.',
		Form::EMAIL => 'Please enter a valid email address.',
		Form::URL => 'Please enter a valid URL.',
		Form::INTEGER => 'Please enter a numeric value.',
		Form::FLOAT => 'Please enter a numeric value.',
		Form::RANGE => 'Please enter a value between %d and %d.',
		Form::MAX_FILE_SIZE => 'The size of the uploaded file can be up to %d bytes.',
		Form::IMAGE => 'The uploaded file must be image in format JPEG, GIF or PNG.',
	);

	/** @var Rule[] */
	private $rules = array();

	/** @var Rules */
	private $parent;

	/** @var array */
	private $toggles = array();

	/** @var IControl */
	private $control;



	public function __construct(IControl $control)
	{
		$this->control = $control;
	}



	/**
	 * Adds a validation rule for the current control.
	 * @param  mixed      rule type
	 * @param  string     message to display for invalid data
	 * @param  mixed      optional rule arguments
	 * @return Rules      provides a fluent interface
	 */
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		$rule = new Rule;
		$rule->control = $this->control;
		$rule->operation = $operation;
		$this->adjustOperation($rule);
		$rule->arg = $arg;
		$rule->type = Rule::VALIDATOR;
		if ($message === NULL && is_string($rule->operation) && isset(static::$defaultMessages[$rule->operation])) {
			$rule->message = static::$defaultMessages[$rule->operation];
		} else {
			$rule->message = $message;
		}
		$this->rules[] = $rule;
		return $this;
	}



	/**
	 * Adds a validation condition a returns new branch.
	 * @param  mixed      condition type
	 * @param  mixed      optional condition arguments
	 * @return Rules      new branch
	 */
	public function addCondition($operation, $arg = NULL)
	{
		return $this->addConditionOn($this->control, $operation, $arg);
	}



	/**
	 * Adds a validation condition on specified control a returns new branch.
	 * @param  IControl form control
	 * @param  mixed      condition type
	 * @param  mixed      optional condition arguments
	 * @return Rules      new branch
	 */
	public function addConditionOn(IControl $control, $operation, $arg = NULL)
	{
		$rule = new Rule;
		$rule->control = $control;
		$rule->operation = $operation;
		$this->adjustOperation($rule);
		$rule->arg = $arg;
		$rule->type = Rule::CONDITION;
		$rule->subRules = new static($this->control);
		$rule->subRules->parent = $this;

		$this->rules[] = $rule;
		return $rule->subRules;
	}



	/**
	 * Adds a else statement.
	 * @return Rules      else branch
	 */
	public function elseCondition()
	{
		$rule = clone end($this->parent->rules);
		$rule->isNegative = !$rule->isNegative;
		$rule->subRules = new static($this->parent->control);
		$rule->subRules->parent = $this->parent;
		$this->parent->rules[] = $rule;
		return $rule->subRules;
	}



	/**
	 * Ends current validation condition.
	 * @return Rules      parent branch
	 */
	public function endCondition()
	{
		return $this->parent;
	}



	/**
	 * Toggles HTML elememnt visibility.
	 * @param  string     element id
	 * @param  bool       hide element?
	 * @return Rules      provides a fluent interface
	 */
	public function toggle($id, $hide = TRUE)
	{
		$this->toggles[$id] = $hide;
		return $this;
	}



	/**
	 * Validates against ruleset.
	 * @param  bool    stop before first error?
	 * @return bool    is valid?
	 */
	public function validate($onlyCheck = FALSE)
	{
		foreach ($this->rules as $rule) {
			if ($rule->control->isDisabled()) {
				continue;
			}

			$success = ($rule->isNegative xor $this->getCallback($rule)->invoke($rule->control, $rule->arg));

			if ($rule->type === Rule::CONDITION && $success) {
				if (!$rule->subRules->validate($onlyCheck)) {
					return FALSE;
				}

			} elseif ($rule->type === Rule::VALIDATOR && !$success) {
				if (!$onlyCheck) {
					$rule->control->addError(static::formatMessage($rule, TRUE));
				}
				return FALSE;
			}
		}
		return TRUE;
	}



	/**
	 * Iterates over ruleset.
	 * @return \ArrayIterator
	 */
	final public function getIterator()
	{
		return new \ArrayIterator($this->rules);
	}



	/**
	 * @return array
	 */
	final public function getToggles()
	{
		return $this->toggles;
	}



	/**
	 * Process 'operation' string.
	 * @param  Rule
	 * @return void
	 */
	private function adjustOperation($rule)
	{
		if (is_string($rule->operation) && ord($rule->operation[0]) > 127) {
			$rule->isNegative = TRUE;
			$rule->operation = ~$rule->operation;
		}

		if (!$this->getCallback($rule)->isCallable()) {
			$operation = is_scalar($rule->operation) ? " '$rule->operation'" : '';
			throw new Nette\InvalidArgumentException("Unknown operation$operation for control '{$rule->control->name}'.");
		}
	}



	private function getCallback($rule)
	{
		$op = $rule->operation;
		if (is_string($op) && strncmp($op, ':', 1) === 0) {
			return new Nette\Callback(get_class($rule->control), self::VALIDATE_PREFIX . ltrim($op, ':'));
		} else {
			return new Nette\Callback($op);
		}
	}



	public static function formatMessage($rule, $withValue)
	{
		$message = $rule->message;
		if ($message instanceof Nette\Utils\Html) {
			return $message;
		}
		if (!isset($message)) { // report missing message by notice
			$message = static::$defaultMessages[$rule->operation];
		}
		if ($translator = $rule->control->getForm()->getTranslator()) {
			$message = $translator->translate($message, is_int($rule->arg) ? $rule->arg : NULL);
		}
		$message = vsprintf(preg_replace('#%(name|label|value)#', '%$0', $message), (array) $rule->arg);
		$message = str_replace('%name', $rule->control->getName(), $message);
		$message = str_replace('%label', $rule->control->translate($rule->control->caption), $message);
		if ($withValue && strpos($message, '%value') !== FALSE) {
			$message = str_replace('%value', $rule->control->getValue(), $message);
		}
		return $message;
	}

}
