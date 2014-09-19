<?php

/**
 * FSHL 2.1.0                                  | Fast Syntax HighLighter |
 * -----------------------------------------------------------------------
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

namespace FSHL\Output;

use FSHL;

/**
 * HTML output.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Html implements FSHL\Output
{
	/**
	 * Last used class.
	 *
	 * @var string
	 */
	private $lastClass = null;

	/**
	 * Outputs a template part.
	 *
	 * @param string $part
	 * @param string $class
	 * @return string
	 */
	public function template($part, $class)
	{
		$output = '';

		if ($this->lastClass !== $class) {
			if (null !== $this->lastClass) {
				$output .= '</span>';
			}
			if (null !== $class) {
				$output .= '<span class="' . $class . '">';
			}

			$this->lastClass = $class;
		}

		return $output . htmlspecialchars($part, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Outputs a keyword.
	 *
	 * @param string $part
	 * @param string $class
	 * @return string
	 */
	public function keyword($part, $class)
	{
		$output = '';

		if ($this->lastClass !== $class) {
			if (null !== $this->lastClass) {
				$output .= '</span>';
			}
			if (null !== $class) {
				$output .= '<span class="' . $class . '">';
			}

			$this->lastClass = $class;
		}

		return $output . htmlspecialchars($part, ENT_COMPAT, 'UTF-8');
	}
}