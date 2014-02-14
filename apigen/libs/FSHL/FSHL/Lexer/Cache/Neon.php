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
 * Optimized and cached Neon lexer.
 *
 * This file is generated. All changes made in this file will be lost.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 * @see \FSHL\Generator
 * @see \FSHL\Lexer\Neon
 */
class Neon
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
		$this->language = 'Neon';
		$this->trans = array(
			0 => array(
				0 => array(
					0 => 1, 1 => 1
				), 1 => array(
					0 => 2, 1 => 1
				), 2 => array(
					0 => 7, 1 => 1
				), 3 => array(
					0 => 3, 1 => 1
				), 4 => array(
					0 => 0, 1 => 1
				), 5 => array(
					0 => 0, 1 => 1
				)
			), 1 => array(
				0 => array(
					0 => 6, 1 => 1
				), 1 => array(
					0 => 6, 1 => 1
				), 2 => array(
					0 => 1, 1 => 1
				), 3 => array(
					0 => 2, 1 => 1
				), 4 => array(
					0 => 3, 1 => 1
				), 5 => array(
					0 => 1, 1 => 1
				), 6 => array(
					0 => 1, 1 => 1
				)
			), 2 => array(
				0 => array(
					0 => 6, 1 => 1
				), 1 => array(
					0 => 6, 1 => 1
				), 2 => array(
					0 => 4, 1 => 1
				)
			), 3 => array(
				0 => array(
					0 => 4, 1 => 1
				)
			), 4 => array(
				0 => array(
					0 => 0, 1 => 1
				), 1 => array(
					0 => 2, 1 => 1
				), 2 => array(
					0 => 7, 1 => 1
				), 3 => array(
					0 => 8, 1 => 1
				), 4 => array(
					0 => 9, 1 => 1
				), 5 => array(
					0 => 6, 1 => 1
				), 6 => array(
					0 => 6, 1 => 1
				), 7 => array(
					0 => 6, 1 => 1
				), 8 => array(
					0 => 6, 1 => 1
				), 9 => array(
					0 => 6, 1 => 1
				), 10 => array(
					0 => 6, 1 => 1
				), 11 => array(
					0 => 6, 1 => 1
				), 12 => array(
					0 => 5, 1 => 1
				), 13 => array(
					0 => 11, 1 => 1
				), 14 => array(
					0 => 11, 1 => 1
				), 15 => array(
					0 => 10, 1 => 1
				), 16 => array(
					0 => 12, 1 => 1
				), 17 => array(
					0 => 4, 1 => 1
				)
			), 5 => array(
				0 => array(
					0 => 10, 1 => 1
				), 1 => array(
					0 => 7, 1 => 1
				), 2 => array(
					0 => 0, 1 => 1
				)
			), 6 => array(
				0 => array(
					0 => 13, 1 => -1
				)
			), 7 => array(
				0 => array(
					0 => 0, 1 => -1
				), 1 => array(
					0 => 7, 1 => 1
				)
			), 8 => array(
				0 => array(
					0 => 13, 1 => 0
				), 1 => array(
					0 => 10, 1 => 1
				), 2 => array(
					0 => 8, 1 => 1
				)
			), 9 => array(
				0 => array(
					0 => 13, 1 => 0
				), 1 => array(
					0 => 10, 1 => 1
				), 2 => array(
					0 => 9, 1 => 1
				)
			), 10 => array(
				0 => array(
					0 => 13, 1 => 0
				)
			), 11 => array(
				0 => array(
					0 => 11, 1 => 1
				), 1 => array(
					0 => 13, 1 => -1
				)
			), 12 => array(
				0 => array(
					0 => 13, 1 => -1
				)
			)
		);
		$this->initialState = 0;
		$this->returnState = 13;
		$this->quitState = 14;
		$this->flags = array(
			0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 4, 7 => 0, 8 => 4, 9 => 4, 10 => 4, 11 => 4, 12 => 4
		);
		$this->data = array(
			0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => NULL, 6 => NULL, 7 => NULL, 8 => NULL, 9 => NULL, 10 => NULL, 11 => NULL, 12 => NULL
		);
		$this->classes = array(
			0 => NULL, 1 => 'neon-section', 2 => 'neon-key', 3 => 'neon-sep', 4 => 'neon-value', 5 => 'neon-value', 6 => 'neon-sep', 7 => 'neon-comment', 8 => 'neon-quote', 9 => 'neon-quote', 10 => 'neon-var', 11 => 'neon-num', 12 => 'neon-ref'
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
			2 => '#', 3 => '-', 4 => "\n", 5 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if (preg_match('~[\\w.]+(?=(\\s*<\\s*[\\w.]+)?\\s*:\\s*\n)~Ai', $text, $matches, 0, $textPos)) {
				return array(0, $matches[0], $buffer);
			}
			if (preg_match('~[\\w.]+(?=\\s*(?::|=))~Ai', $text, $matches, 0, $textPos)) {
				return array(1, $matches[0], $buffer);
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
	 * Finds a delimiter for state SECTION.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter1($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '<', 1 => ':', 4 => '-', 5 => "\n", 6 => "\t"
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
			if (preg_match('~[\\w.]+(?=(\\s*<\\s*[\\w.]+)?\\s*:\\s*\n)~Ai', $text, $matches, 0, $textPos)) {
				return array(2, $matches[0], $buffer);
			}
			if (preg_match('~[\\w.]+(?=\\s*(?::|=))~Ai', $text, $matches, 0, $textPos)) {
				return array(3, $matches[0], $buffer);
			}
			if ($delimiters[4] === $letter) {
				return array(4, $delimiters[4], $buffer);
			}
			if ($delimiters[5] === $letter) {
				return array(5, $delimiters[5], $buffer);
			}
			if ($delimiters[6] === $letter) {
				return array(6, $delimiters[6], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state KEY.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter2($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => ':', 1 => '='
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
			return array(2, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state LIST.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter3($text, $textLength, $textPos)
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
	 * Finds a delimiter for state VALUE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter4($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 2 => '#', 3 => '"', 4 => '\'', 5 => '[', 6 => ']', 7 => '{', 8 => '}', 9 => '=', 10 => ',', 11 => ':', 16 => '@', 17 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~[\\w.]+(?=\\s*(?::|=))~Ai', $text, $matches, 0, $textPos)) {
				return array(1, $matches[0], $buffer);
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
			if ($delimiters[6] === $letter) {
				return array(6, $delimiters[6], $buffer);
			}
			if ($delimiters[7] === $letter) {
				return array(7, $delimiters[7], $buffer);
			}
			if ($delimiters[8] === $letter) {
				return array(8, $delimiters[8], $buffer);
			}
			if ($delimiters[9] === $letter) {
				return array(9, $delimiters[9], $buffer);
			}
			if ($delimiters[10] === $letter) {
				return array(10, $delimiters[10], $buffer);
			}
			if ($delimiters[11] === $letter) {
				return array(11, $delimiters[11], $buffer);
			}
			if (preg_match('~[a-z](?![,\\]}#\n])~Ai', $text, $matches, 0, $textPos)) {
				return array(12, $matches[0], $buffer);
			}
			if (preg_match('~^\\d+~', $part, $matches)) {
				return array(13, $matches[0], $buffer);
			}
			if (preg_match('~^\.\\d+~', $part, $matches)) {
				return array(14, $matches[0], $buffer);
			}
			if (preg_match('~%\\w+(?=%)~Ai', $text, $matches, 0, $textPos)) {
				return array(15, $matches[0], $buffer);
			}
			if ($delimiters[16] === $letter) {
				return array(16, $delimiters[16], $buffer);
			}
			if ($delimiters[17] === $letter) {
				return array(17, $delimiters[17], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state TEXT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter5($text, $textLength, $textPos)
	{
		static $delimiters = array(
			1 => '#', 2 => "\n"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if (preg_match('~%\\w+(?=%)~Ai', $text, $matches, 0, $textPos)) {
				return array(0, $matches[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
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
	 * Finds a delimiter for state SEPARATOR.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter6($text, $textLength, $textPos)
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
	 * Finds a delimiter for state COMMENT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter7($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t"
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
	public function findDelimiter8($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"', 2 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~%\\w+(?=%)~Ai', $text, $matches, 0, $textPos)) {
				return array(1, $matches[0], $buffer);
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
	 * Finds a delimiter for state QUOTE_SINGLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter9($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\'', 2 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~%\\w+(?=%)~Ai', $text, $matches, 0, $textPos)) {
				return array(1, $matches[0], $buffer);
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
	 * Finds a delimiter for state VARIABLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter10($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '%'
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
	 * Finds a delimiter for state NUMBER.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter11($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (preg_match('~^\.\\d+~', $part, $matches)) {
				return array(0, $matches[0], $buffer);
			}
			return array(1, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state REFERENCE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter12($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);

			if (preg_match('~^\\W+~', $part, $matches)) {
				return array(0, $matches[0], $buffer);
			}
			$buffer .= $text[$textPos];
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

}