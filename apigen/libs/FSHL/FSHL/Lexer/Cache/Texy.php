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
 * Optimized and cached Texy lexer.
 *
 * This file is generated. All changes made in this file will be lost.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 * @see \FSHL\Generator
 * @see \FSHL\Lexer\Texy
 */
class Texy
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
		$this->language = 'Texy';
		$this->trans = array(
			0 => array(
				0 => array(
					0 => 8, 1 => 1
				), 1 => array(
					0 => 9, 1 => 1
				), 2 => array(
					0 => 1, 1 => 1
				)
			), 1 => array(
				0 => array(
					0 => 3, 1 => 1
				), 1 => array(
					0 => 2, 1 => -1
				)
			), 2 => array(
				0 => array(
					0 => 4, 1 => 1
				), 1 => array(
					0 => 4, 1 => 1
				), 2 => array(
					0 => 4, 1 => 1
				), 3 => array(
					0 => 4, 1 => 1
				), 4 => array(
					0 => 0, 1 => -1
				)
			), 3 => array(
				0 => array(
					0 => 3, 1 => 1
				), 1 => array(
					0 => 4, 1 => 1
				), 2 => array(
					0 => 4, 1 => 1
				), 3 => array(
					0 => 7, 1 => 1
				), 4 => array(
					0 => 7, 1 => 1
				), 5 => array(
					0 => 7, 1 => 1
				), 6 => array(
					0 => 7, 1 => 1
				), 7 => array(
					0 => 0, 1 => -1
				)
			), 4 => array(
				0 => array(
					0 => 4, 1 => 1
				), 1 => array(
					0 => 4, 1 => 1
				), 2 => array(
					0 => 4, 1 => 1
				), 3 => array(
					0 => 4, 1 => 1
				), 4 => array(
					0 => 3, 1 => 1
				), 5 => array(
					0 => 5, 1 => -1
				)
			), 5 => array(
				0 => array(
					0 => 6, 1 => 1
				), 1 => array(
					0 => 6, 1 => 1
				), 2 => array(
					0 => 6, 1 => 1
				), 3 => array(
					0 => 6, 1 => 1
				), 4 => array(
					0 => 3, 1 => 1
				)
			), 6 => array(
				0 => array(
					0 => 3, 1 => 1
				)
			), 7 => array(
				0 => array(
					0 => 0, 1 => -1
				)
			), 8 => array(
				0 => array(
					0 => 15, 1 => 1
				), 1 => array(
					0 => 19, 1 => 1
				), 2 => array(
					0 => 10, 1 => 1
				), 3 => array(
					0 => 11, 1 => 1
				), 4 => array(
					0 => 0, 1 => -1
				)
			), 9 => array(
				0 => array(
					0 => 0, 1 => -1
				)
			), 10 => array(
				0 => array(
					0 => 0, 1 => -1
				)
			), 11 => array(
				0 => array(
					0 => 12, 1 => -1
				)
			), 12 => array(
				0 => array(
					0 => 13, 1 => 1
				)
			), 13 => array(
				0 => array(
					0 => 14, 1 => 1
				), 1 => array(
					0 => 12, 1 => -1
				)
			), 14 => array(
				0 => array(
					0 => 0, 1 => -1
				)
			), 15 => array(
				0 => array(
					0 => 16, 1 => -1
				)
			), 16 => array(
				0 => array(
					0 => 17, 1 => 1
				)
			), 17 => array(
				0 => array(
					0 => 18, 1 => 1
				), 1 => array(
					0 => 16, 1 => -1
				)
			), 18 => array(
				0 => array(
					0 => 0, 1 => -1
				)
			), 19 => array(
				0 => array(
					0 => 20, 1 => -1
				)
			), 20 => array(
				0 => array(
					0 => 21, 1 => 1
				)
			), 21 => array(
				0 => array(
					0 => 22, 1 => 1
				), 1 => array(
					0 => 20, 1 => -1
				)
			), 22 => array(
				0 => array(
					0 => 0, 1 => -1
				)
			)
		);
		$this->initialState = 2;
		$this->returnState = 23;
		$this->quitState = 24;
		$this->flags = array(
			0 => 0, 1 => 0, 2 => 0, 3 => 'texy-err', 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0, 13 => 0, 14 => 0, 15 => 0, 16 => 0, 17 => 0, 18 => 0, 19 => 0, 20 => 0, 21 => 0, 22 => 0
		);
		$this->data = array(
			0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => NULL, 6 => NULL, 7 => NULL, 8 => NULL, 9 => NULL, 10 => NULL, 11 => NULL, 12 => NULL, 13 => NULL, 14 => NULL, 15 => NULL, 16 => NULL, 17 => NULL, 18 => NULL, 19 => NULL, 20 => NULL, 21 => NULL, 22 => NULL
		);
		$this->classes = array(
			0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => 'texy-hlead', 5 => 'texy-hbody', 6 => 'texy-hlead', 7 => 'texy-hr', 8 => 'texy-hr', 9 => 'texy-hr', 10 => 'texy-hr', 11 => 'texy-hr', 12 => 'texy-text', 13 => 'texy-text', 14 => 'texy-hr', 15 => 'texy-hr', 16 => 'texy-html', 17 => 'texy-html', 18 => 'texy-hr', 19 => 'texy-hr', 20 => 'texy-code', 21 => 'texy-code', 22 => 'texy-hr'
		);
		$this->keywords = array(
			
		);

	}

	/**
	 * Finds a delimiter for state LINE_BODY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter0($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '/---', 1 => '\\---', 2 => "\n"
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
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter1($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~^\\S+~', $part, $matches)) {
				return array(1, $matches[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state LINE_SINGLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter2($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '##', 1 => '**', 2 => '==', 3 => '--'
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
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			return array(4, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state LINE_DOUBLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter3($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => '##', 2 => '==', 3 => '--', 4 => '- -', 5 => '**', 6 => '* *'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[1])) {
				return array(1, $delimiters[1], $buffer);
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
			if (0 === strpos($part, $delimiters[6])) {
				return array(6, $delimiters[6], $buffer);
			}
			return array(7, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state HEADER_IN.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter4($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '=', 1 => '#', 2 => '-', 3 => '*', 4 => "\n"
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
			return array(5, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state HEADER_BODY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter5($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '=', 1 => '#', 2 => '-', 3 => '*', 4 => "\n"
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
	 * Finds a delimiter for state HEADER_OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter6($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state HORIZONTAL_LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter7($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_IN.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter8($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => 'html', 1 => 'code', 2 => 'div', 3 => 'text'
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
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			return array(4, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter9($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			return array(0, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_DUMMY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter10($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			return array(0, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_TEXT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter11($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_TEXT_BODY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter12($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_TEXT_BODY_LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter13($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\\---'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (0 === strpos($part, $delimiters[0])) {
				return array(0, $delimiters[0], $buffer);
			}
			return array(1, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_TEXT_BODY_OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter14($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			return array(0, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_HTML.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter15($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_HTML_BODY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter16($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_HTML_BODY_LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter17($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\\---'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (0 === strpos($part, $delimiters[0])) {
				return array(0, $delimiters[0], $buffer);
			}
			return array(1, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_HTML_BODY_OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter18($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			return array(0, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_CODE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter19($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_CODE_BODY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter20($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n"
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
	 * Finds a delimiter for state BLOCK_CODE_BODY_LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter21($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\\---'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (0 === strpos($part, $delimiters[0])) {
				return array(0, $delimiters[0], $buffer);
			}
			return array(1, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state BLOCK_CODE_BODY_OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter22($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			return array(0, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

}