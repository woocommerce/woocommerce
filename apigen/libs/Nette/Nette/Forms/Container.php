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
 * Container for form controls.
 *
 * @author     David Grudl
 *
 * @property-write $defaults
 * @property   Nette\ArrayHash $values
 * @property-read bool $valid
 * @property   ControlGroup $currentGroup
 * @property-read \ArrayIterator $controls
 * @property-read Form $form
 */
class Container extends Nette\ComponentModel\Container implements \ArrayAccess
{
	/** @var array of function(Form $sender); Occurs when the form is validated */
	public $onValidate;

	/** @var ControlGroup */
	protected $currentGroup;

	/** @var bool */
	protected $valid;



	/********************* data exchange ****************d*g**/



	/**
	 * Fill-in with default values.
	 * @param  array|Traversable  values used to fill the form
	 * @param  bool     erase other default values?
	 * @return Container  provides a fluent interface
	 */
	public function setDefaults($values, $erase = FALSE)
	{
		$form = $this->getForm(FALSE);
		if (!$form || !$form->isAnchored() || !$form->isSubmitted()) {
			$this->setValues($values, $erase);
		}
		return $this;
	}



	/**
	 * Fill-in with values.
	 * @param  array|Traversable  values used to fill the form
	 * @param  bool     erase other controls?
	 * @return Container  provides a fluent interface
	 */
	public function setValues($values, $erase = FALSE)
	{
		if ($values instanceof \Traversable) {
			$values = iterator_to_array($values);

		} elseif (!is_array($values)) {
			throw new Nette\InvalidArgumentException("First parameter must be an array, " . gettype($values) ." given.");
		}

		foreach ($this->getComponents() as $name => $control) {
			if ($control instanceof IControl) {
				if (array_key_exists($name, $values)) {
					$control->setValue($values[$name]);

				} elseif ($erase) {
					$control->setValue(NULL);
				}

			} elseif ($control instanceof Container) {
				if (array_key_exists($name, $values)) {
					$control->setValues($values[$name], $erase);

				} elseif ($erase) {
					$control->setValues(array(), $erase);
				}
			}
		}
		return $this;
	}



	/**
	 * Returns the values submitted by the form.
	 * @param  bool  return values as an array?
	 * @return Nette\ArrayHash|array
	 */
	public function getValues($asArray = FALSE)
	{
		$values = $asArray ? array() : new Nette\ArrayHash;
		foreach ($this->getComponents() as $name => $control) {
			if ($control instanceof IControl && !$control->isDisabled() && !$control instanceof ISubmitterControl) {
				$values[$name] = $control->getValue();

			} elseif ($control instanceof Container) {
				$values[$name] = $control->getValues($asArray);
			}
		}
		return $values;
	}



	/********************* validation ****************d*g**/



	/**
	 * Is form valid?
	 * @return bool
	 */
	public function isValid()
	{
		if ($this->valid === NULL) {
			$this->validate();
		}
		return $this->valid;
	}



	/**
	 * Performs the server side validation.
	 * @return void
	 */
	public function validate()
	{
		$this->valid = TRUE;
		$this->onValidate($this);
		foreach ($this->getControls() as $control) {
			if (!$control->getRules()->validate()) {
				$this->valid = FALSE;
			}
		}
	}



	/********************* form building ****************d*g**/



	/**
	 * @param  ControlGroup
	 * @return Container  provides a fluent interface
	 */
	public function setCurrentGroup(ControlGroup $group = NULL)
	{
		$this->currentGroup = $group;
		return $this;
	}



	/**
	 * Returns current group.
	 * @return ControlGroup
	 */
	public function getCurrentGroup()
	{
		return $this->currentGroup;
	}



	/**
	 * Adds the specified component to the IContainer.
	 * @param  IComponent
	 * @param  string
	 * @param  string
	 * @return Container  provides a fluent interface
	 * @throws Nette\InvalidStateException
	 */
	public function addComponent(Nette\ComponentModel\IComponent $component, $name, $insertBefore = NULL)
	{
		parent::addComponent($component, $name, $insertBefore);
		if ($this->currentGroup !== NULL && $component instanceof IControl) {
			$this->currentGroup->add($component);
		}
		return $this;
	}



	/**
	 * Iterates over all form controls.
	 * @return \ArrayIterator
	 */
	public function getControls()
	{
		return $this->getComponents(TRUE, 'Nette\Forms\IControl');
	}



	/**
	 * Returns form.
	 * @param  bool   throw exception if form doesn't exist?
	 * @return Form
	 */
	public function getForm($need = TRUE)
	{
		return $this->lookup('Nette\Forms\Form', $need);
	}



	/********************* control factories ****************d*g**/



	/**
	 * Adds single-line text input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  maximum number of characters the user may enter
	 * @return Nette\Forms\Controls\TextInput
	 */
	public function addText($name, $label = NULL, $cols = NULL, $maxLength = NULL)
	{
		return $this[$name] = new Controls\TextInput($label, $cols, $maxLength);
	}



	/**
	 * Adds single-line text input control used for sensitive input such as passwords.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  maximum number of characters the user may enter
	 * @return Nette\Forms\Controls\TextInput
	 */
	public function addPassword($name, $label = NULL, $cols = NULL, $maxLength = NULL)
	{
		$control = new Controls\TextInput($label, $cols, $maxLength);
		$control->setType('password');
		return $this[$name] = $control;
	}



	/**
	 * Adds multi-line text input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  height of the control in text lines
	 * @return Nette\Forms\Controls\TextArea
	 */
	public function addTextArea($name, $label = NULL, $cols = 40, $rows = 10)
	{
		return $this[$name] = new Controls\TextArea($label, $cols, $rows);
	}



	/**
	 * Adds control that allows the user to upload files.
	 * @param  string  control name
	 * @param  string  label
	 * @return Nette\Forms\Controls\UploadControl
	 */
	public function addUpload($name, $label = NULL)
	{
		return $this[$name] = new Controls\UploadControl($label);
	}



	/**
	 * Adds hidden form control used to store a non-displayed value.
	 * @param  string  control name
	 * @param  mixed   default value
	 * @return Nette\Forms\Controls\HiddenField
	 */
	public function addHidden($name, $default = NULL)
	{
		$control = new Controls\HiddenField;
		$control->setDefaultValue($default);
		return $this[$name] = $control;
	}



	/**
	 * Adds check box control to the form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Nette\Forms\Controls\Checkbox
	 */
	public function addCheckbox($name, $caption = NULL)
	{
		return $this[$name] = new Controls\Checkbox($caption);
	}



	/**
	 * Adds set of radio button controls to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @return Nette\Forms\Controls\RadioList
	 */
	public function addRadioList($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new Controls\RadioList($label, $items);
	}



	/**
	 * Adds select box control that allows single item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int     number of rows that should be visible
	 * @return Nette\Forms\Controls\SelectBox
	 */
	public function addSelect($name, $label = NULL, array $items = NULL, $size = NULL)
	{
		return $this[$name] = new Controls\SelectBox($label, $items, $size);
	}



	/**
	 * Adds select box control that allows multiple item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @param  int     number of rows that should be visible
	 * @return Nette\Forms\Controls\MultiSelectBox
	 */
	public function addMultiSelect($name, $label = NULL, array $items = NULL, $size = NULL)
	{
		return $this[$name] = new Controls\MultiSelectBox($label, $items, $size);
	}



	/**
	 * Adds button used to submit form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Nette\Forms\Controls\SubmitButton
	 */
	public function addSubmit($name, $caption = NULL)
	{
		return $this[$name] = new Controls\SubmitButton($caption);
	}



	/**
	 * Adds push buttons with no default behavior.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Nette\Forms\Controls\Button
	 */
	public function addButton($name, $caption)
	{
		return $this[$name] = new Controls\Button($caption);
	}



	/**
	 * Adds graphical button used to submit form.
	 * @param  string  control name
	 * @param  string  URI of the image
	 * @param  string  alternate text for the image
	 * @return Nette\Forms\Controls\ImageButton
	 */
	public function addImage($name, $src = NULL, $alt = NULL)
	{
		return $this[$name] = new Controls\ImageButton($src, $alt);
	}



	/**
	 * Adds naming container to the form.
	 * @param  string  name
	 * @return Container
	 */
	public function addContainer($name)
	{
		$control = new Container;
		$control->currentGroup = $this->currentGroup;
		return $this[$name] = $control;
	}



	/********************* interface \ArrayAccess ****************d*g**/



	/**
	 * Adds the component to the container.
	 * @param  string  component name
	 * @param  Nette\ComponentModel\IComponent
	 * @return void
	 */
	final public function offsetSet($name, $component)
	{
		$this->addComponent($component, $name);
	}



	/**
	 * Returns component specified by name. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return Nette\ComponentModel\IComponent
	 * @throws Nette\InvalidArgumentException
	 */
	final public function offsetGet($name)
	{
		return $this->getComponent($name, TRUE);
	}



	/**
	 * Does component specified by name exists?
	 * @param  string  component name
	 * @return bool
	 */
	final public function offsetExists($name)
	{
		return $this->getComponent($name, FALSE) !== NULL;
	}



	/**
	 * Removes component from the container.
	 * @param  string  component name
	 * @return void
	 */
	final public function offsetUnset($name)
	{
		$component = $this->getComponent($name, FALSE);
		if ($component !== NULL) {
			$this->removeComponent($component);
		}
	}



	/**
	 * Prevents cloning.
	 */
	final public function __clone()
	{
		throw new Nette\NotImplementedException('Form cloning is not supported yet.');
	}



	/********************* deprecated ****************d*g**/

	/** @deprecated */
	function addFile($name, $label = NULL)
	{
		trigger_error(__METHOD__ . '() is deprecated; use addUpload() instead.', E_USER_WARNING);
		return $this->addUpload($name, $label);
	}

}
