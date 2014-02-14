<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Forms\Controls;

use Nette;



/**
 * Multiline text input control.
 *
 * @author     David Grudl
 */
class TextArea extends TextBase
{

	/**
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  height of the control in text lines
	 */
	public function __construct($label = NULL, $cols = NULL, $rows = NULL)
	{
		parent::__construct($label);
		$this->control->setName('textarea');
		$this->control->cols = $cols;
		$this->control->rows = $rows;
		$this->value = '';
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Utils\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$control->setText($this->getValue() === '' ? $this->translate($this->emptyValue) : $this->value);
		return $control;
	}

}
