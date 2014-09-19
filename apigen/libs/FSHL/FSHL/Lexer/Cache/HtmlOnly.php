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

namespace FSHL\Lexer\Cache;

/**
 * Optimized and cached HtmlOnly lexer.
 *
 * This file is generated. All changes made in this file will be lost.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 * @see \FSHL\Generator
 * @see \FSHL\Lexer\HtmlOnly
 */
class HtmlOnly
{
	/**
	 * Language name.
	 *
	 * @var array
	 */
	public $language;

	/**
	 * Transitions table.
	 *
	 * @var array
	 */
	public $trans;

	/**
	 * Id of the initial state.
	 *
	 * @var integer
	 */
	public $initialState;

	/**
	 * Id of the return state.
	 *
	 * @var integer
	 */
	public $returnState;

	/**
	 * Id of the quit state.
	 *
	 * @var integer
	 */
	public $quitState;

	/**
	 * List of flags for all states.
	 *
	 * @var array
	 */
	public $flags;

	/**
	 * Data for all states.
	 *
	 * @var array
	 */
	public $data;

	/**
	 * List of CSS classes.
	 *
	 * @var array
	 */
	public $classes;

	/**
	 * List of keywords.
	 *
	 * @var array
	 */
	public $keywords;

	/**
	 * Initializes the lexer.
	 */
	public function __construct()
	{
		$this->language = 'HtmlOnly';
		$this->trans = array(
			0 => array(
				0 => array(
					0 => 12, 1 => 1
				), 1 => array(
					0 => 0, 1 => 0
				), 2 => array(
					0 => 2, 1 => 1
				), 3 => array(
					0 => 1, 1 => 1
				), 4 => array(
					0 => 0, 1 => 1
				), 5 => array(
					0 => 0, 1 => 1
				)
			), 1 => array(
				0 => array(
					0 => 0, 1 => 0
				), 1 => array(
					0 => 0, 1 => 0
				), 2 => array(
					0 => 0, 1 => 0
				)
			), 2 => array(
				0 => array(
					0 => 0, 1 => 0
				), 1 => array(
					0 => 3, 1 => 1
				), 2 => array(
					0 => 4, 1 => 0
				), 3 => array(
					0 => 4, 1 => 0
				), 4 => array(
					0 => 7, 1 => 0
				), 5 => array(
					0 => 7, 1 => 0
				)
			), 3 => array(
				0 => array(
					0 => 10, 1 => 1
				), 1 => array(
					0 => 11, 1 => 1
				), 2 => array(
					0 => 2, 1 => -1
				), 3 => array(
					0 => 2, 1 => -1
				), 4 => array(
					0 => 3, 1 => 1
				), 5 => array(
					0 => 3, 1 => 1
				)
			), 4 => array(
				0 => array(
					0 => 10, 1 => 1
				), 1 => array(
					0 => 11, 1 => 1
				), 2 => array(
					0 => 5, 1 => -1
				), 3 => array(
					0 => 4, 1 => 1
				), 4 => array(
					0 => 4, 1 => 1
				)
			), 5 => array(
				0 => array(
					0 => 6, 1 => 0
				)
			), 6 => array(
				0 => array(
					0 => 6, 1 => 1
				), 1 => array(
					0 => 6, 1 => 1
				), 2 => array(
					0 => 2, 1 => 1
				), 3 => array(
					0 => 2, 1 => 1
				)
			), 7 => array(
				0 => array(
					0 => 10, 1 => 1
				), 1 => array(
					0 => 11, 1 => 1
				), 2 => array(
					0 => 8, 1 => -1
				), 3 => array(
					0 => 7, 1 => 1
				), 4 => array(
					0 => 7, 1 => 1
				)
			), 8 => array(
				0 => array(
					0 => 9, 1 => 0
				)
			), 9 => array(
				0 => array(
					0 => 9, 1 => 1
				), 1 => array(
					0 => 9, 1 => 1
				), 2 => array(
					0 => 2, 1 => 1
				), 3 => array(
					0 => 2, 1 => 1
				)
			), 10 => array(
				0 => array(
					0 => 13, 1 => 0
				)
			), 11 => array(
				0 => array(
					0 => 13, 1 => 0
				)
			), 12 => array(
				0 => array(
					0 => 12, 1 => 1
				), 1 => array(
					0 => 12, 1 => 1
				), 2 => array(
					0 => 0, 1 => 0
				)
			)
		);
		$this->initialState = 0;
		$this->returnState = 13;
		$this->quitState = 14;
		$this->flags = array(
			0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 4, 11 => 4, 12 => 0
		);
		$this->data = array(
			0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => NULL, 6 => NULL, 7 => NULL, 8 => NULL, 9 => NULL, 10 => NULL, 11 => NULL, 12 => NULL
		);
		$this->classes = array(
			0 => NULL, 1 => 'html-entity', 2 => 'html-tag', 3 => 'html-tagin', 4 => 'html-tagin', 5 => 'html-tag', 6 => NULL, 7 => 'html-tagin', 8 => 'html-tag', 9 => NULL, 10 => 'html-quote', 11 => 'html-quote', 12 => 'html-comment'
		);
		$this->keywords = array(
			
		);

	}

	/**
	 * Finds a delimiter for state OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter0($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '<!--', 1 => '<?', 2 => '<', 3 => '&', 4 => "\n", 5 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (0 === strpos($part, $delimiters[0])) {
				return array(0, $delimiters[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[1])) {
				return array(1, $delimiters[1], $buffer);
			}
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			if ($delimiters[4] === $letter) {
				return array(4, $delimiters[4], $buffer);
			}
			if ($delimiters[5] === $letter) {
				return array(5, $delimiters[5], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state ENTITY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter1($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => ';', 1 => '&'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if (preg_match('~^\s+~', $part, $matches)) {
				return array(2, $matches[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state TAG.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter2($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '>', 2 => 'style', 3 => 'STYLE', 4 => 'script', 5 => 'SCRIPT'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~^\s+~', $part, $matches)) {
				return array(1, $matches[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			if (0 === strpos($part, $delimiters[4])) {
				return array(4, $delimiters[4], $buffer);
			}
			if (0 === strpos($part, $delimiters[5])) {
				return array(5, $delimiters[5], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state TAGIN.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter3($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"', 1 => '\'', 2 => '/>', 3 => '>', 4 => "\n", 5 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			if ($delimiters[4] === $letter) {
				return array(4, $delimiters[4], $buffer);
			}
			if ($delimiters[5] === $letter) {
				return array(5, $delimiters[5], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state STYLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter4($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"', 1 => '\'', 2 => '>', 3 => "\n", 4 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			if ($delimiters[4] === $letter) {
				return array(4, $delimiters[4], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state STYLE_END.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter5($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '>'
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state CSS.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter6($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t", 2 => '</style', 3 => '</STYLE'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state SCRIPT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter7($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"', 1 => '\'', 2 => '>', 3 => "\n", 4 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			if ($delimiters[4] === $letter) {
				return array(4, $delimiters[4], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state SCRIPT_END.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter8($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '>'
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state JAVASCRIPT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter9($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t", 2 => '</script', 3 => '</SCRIPT'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state QUOTE_DOUBLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter10($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"'
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state QUOTE_SINGLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter11($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\''
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state COMMENT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter12($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t", 2 => '-->'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

}