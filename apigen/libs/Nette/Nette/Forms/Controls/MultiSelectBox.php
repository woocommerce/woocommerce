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
 * Select box control that allows multiple item selection.
 *
 * @author     David Grudl
 */
class MultiSelectBox extends SelectBox
{


	/**
	 * Returns selected keys.
	 * @return array
	 */
	public function getValue()
	{
		return array_intersect($this->getRawValue(), array_keys($this->allowed));
	}



	/**
	 * Returns selected keys (not checked).
	 * @return array
	 */
	public function getRawValue()
	{
		if (is_scalar($this->value)) {
			return array($this->value);

		} else {
			$res = array();
			foreach ((array) $this->value as $val) {
				if (is_scalar($val)) {
					$res[] = $val;
				}
			}
			return $res;
		}
	}



	/**
	 * Returns selected values.
	 * @return array
	 */
	public function getSelectedItem()
	{
		return $this->areKeysUsed()
			? array_intersect_key($this->allowed, array_flip($this->getValue()))
			: $this->getValue();
	}



	/**
	 * Returns HTML name of control.
	 * @return string
	 */
	public function getHtmlName()
	{
		return parent::getHtmlName() . '[]';
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Utils\Html
	 */
	public function getControl()
	{
		return parent::getControl()->multiple(TRUE);
	}



	/**
	 * Count/length validator.
	 * @param  MultiSelectBox
	 * @param  array  min and max length pair
	 * @return bool
	 */
	public static function validateLength(MultiSelectBox $control, $range)
	{
		if (!is_array($range)) {
			$range = array($range, $range);
		}
		$count = count($control->getSelectedItem());
		return ($range[0] === NULL || $count >= $range[0]) && ($range[1] === NULL || $count <= $range[1]);
	}

}
