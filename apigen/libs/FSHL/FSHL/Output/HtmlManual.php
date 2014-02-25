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
 * HTML output with links to manual.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class HtmlManual implements FSHL\Output
{
	/**
	 * Last used class.
	 *
	 * @var string
	 */
	private $lastClass = null;

	/**
	 * Closing tag for link.
	 *
	 * @var string
	 */
	private $closeTag = null;

	/**
	 * Urls list to manual.
	 *
	 * @var array
	 */
	private $manualUrl = array(
		'php-keyword1' => 'http://php.net/manual/en/langref.php',
		'php-keyword2' => 'http://php.net/%s',

		'sql-keyword1' => 'http://search.oracle.com/search/search?group=MySQL&q=%s',
		'sql-keyword2' => 'http://search.oracle.com/search/search?group=MySQL&q=%s',
		'sql-keyword3' => 'http://search.oracle.com/search/search?group=MySQL&q=%s',
	);

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

			$output .= $this->closeTag;
			$this->closeTag = '';

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

			$output .= $this->closeTag;
			$this->closeTag = '';

			if (null !== $class) {
				if (isset($this->manualUrl[$class])) {
					$output .= '<a href="' . sprintf($this->manualUrl[$class], $part) . '">';
					$this->closeTag = '</a>';
				}

				$output .= '<span class="' . $class . '">';
			}

			$this->lastClass = $class;
		}

		return $output . htmlspecialchars($part, ENT_COMPAT, 'UTF-8');
	}
}