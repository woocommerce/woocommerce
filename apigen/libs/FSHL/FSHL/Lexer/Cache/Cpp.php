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
 * Optimized and cached Cpp lexer.
 *
 * This file is generated. All changes made in this file will be lost.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 * @see \FSHL\Generator
 * @see \FSHL\Lexer\Cpp
 */
class Cpp
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
		$this->language = 'Cpp';
		$this->trans = array(
			0 => array(
				0 => array(
					0 => 0, 1 => 1
				), 1 => array(
					0 => 0, 1 => 1
				), 2 => array(
					0 => 1, 1 => -1
				), 3 => array(
					0 => 8, 1 => 1
				), 4 => array(
					0 => 4, 1 => 1
				), 5 => array(
					0 => 2, 1 => 1
				), 6 => array(
					0 => 2, 1 => 1
				), 7 => array(
					0 => 5, 1 => 1
				), 8 => array(
					0 => 6, 1 => 1
				), 9 => array(
					0 => 7, 1 => 1
				)
			), 1 => array(
				0 => array(
					0 => 9, 1 => -1
				)
			), 2 => array(
				0 => array(
					0 => 3, 1 => 1
				), 1 => array(
					0 => 2, 1 => 1
				), 2 => array(
					0 => 2, 1 => 1
				), 3 => array(
					0 => 9, 1 => -1
				)
			), 3 => array(
				0 => array(
					0 => 3, 1 => 1
				), 1 => array(
					0 => 9, 1 => -1
				)
			), 4 => array(
				0 => array(
					0 => 4, 1 => 1
				), 1 => array(
					0 => 4, 1 => 1
				), 2 => array(
					0 => 4, 1 => 1
				), 3 => array(
					0 => 9, 1 => -1
				)
			), 5 => array(
				0 => array(
					0 => 9, 1 => 0
				), 1 => array(
					0 => 5, 1 => 1
				), 2 => array(
					0 => 5, 1 => 1
				), 3 => array(
					0 => 5, 1 => 1
				), 4 => array(
					0 => 5, 1 => 1
				)
			), 6 => array(
				0 => array(
					0 => 9, 1 => 0
				), 1 => array(
					0 => 6, 1 => 1
				), 2 => array(
					0 => 6, 1 => 1
				), 3 => array(
					0 => 6, 1 => 1
				)
			), 7 => array(
				0 => array(
					0 => 7, 1 => 1
				), 1 => array(
					0 => 7, 1 => 1
				), 2 => array(
					0 => 9, 1 => 0
				)
			), 8 => array(
				0 => array(
					0 => 9, 1 => -1
				), 1 => array(
					0 => 8, 1 => 1
				)
			)
		);
		$this->initialState = 0;
		$this->returnState = 9;
		$this->quitState = 10;
		$this->flags = array(
			0 => 0, 1 => 5, 2 => 4, 3 => 0, 4 => 4, 5 => 4, 6 => 4, 7 => 4, 8 => 4
		);
		$this->data = array(
			0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => NULL, 6 => NULL, 7 => NULL, 8 => NULL
		);
		$this->classes = array(
			0 => NULL, 1 => NULL, 2 => 'cpp-num', 3 => 'cpp-num', 4 => 'cpp-preproc', 5 => 'cpp-quote', 6 => 'cpp-quote', 7 => 'cpp-comment', 8 => 'cpp-comment'
		);
		$this->keywords = array(
			0 => 'cpp-keywords', 1 => array(
				'bool' => 1, 'break' => 1, 'case' => 1, 'catch' => 1, 'char' => 1, 'class' => 1, 'const' => 1, 'const_cast' => 1, 'continue' => 1, 'default' => 1, 'delete' => 1, 'deprecated' => 1, 'dllexport' => 1, 'dllimport' => 1, 'do' => 1, 'double' => 1, 'dynamic_cast' => 1, 'else' => 1, 'enum' => 1, 'explicit' => 1, 'extern' => 1, 'false' => 1, 'float' => 1, 'for' => 1, 'friend' => 1, 'goto' => 1,
			'if' => 1, 'inline' => 1, 'int' => 1, 'long' => 1, 'mutable' => 1, 'naked' => 1, 'namespace' => 1, 'new' => 1, 'noinline' => 1, 'noreturn' => 1, 'nothrow' => 1, 'novtable' => 1, 'operator' => 1, 'private' => 1, 'property' => 1, 'protected' => 1, 'public' => 1, 'register' => 1, 'reinterpret_cast' => 1, 'return' => 1, 'selectany' => 1, 'short' => 1, 'signed' => 1, 'sizeof' => 1, 'static' => 1, 'static_cast' => 1,
			'struct' => 1, 'switch' => 1, 'template' => 1, 'this' => 1, 'thread' => 1, 'throw' => 1, 'true' => 1, 'try' => 1, 'typedef' => 1, 'typeid' => 1, 'typename' => 1, 'union' => 1, 'unsigned' => 1, 'using' => 1, 'uuid' => 1, 'virtual' => 1, 'void' => 1, 'volatile' => 1, '__wchar_t' => 1, 'wchar_t' => 1, 'while' => 1, '__abstract' => 1, '__alignof' => 1, '__asm' => 1, '__assume' => 1, '__based' => 1,
			'__box' => 1, '__cdecl' => 1, '__declspec' => 1, '__delegate' => 1, '__event' => 1, '__except' => 1, '__fastcall' => 1, '__finally' => 1, '__forceinline' => 1, '__gc' => 1, '__hook' => 1, '__identifier' => 1, '__if_exists' => 1, '__if_not_exists' => 1, '__inline' => 1, '__int8' => 1, '__int16' => 1, '__int32' => 1, '__int64' => 1, '__interface' => 1, '__leave' => 1, '__m64' => 1, '__m128' => 1, '__m128d' => 1, '__m128i' => 1, '__multiple_inheritance' => 1,
			'__nogc' => 1, '__noop' => 1, '__pin' => 1, '__property' => 1, '__raise' => 1, '__sealed' => 1, '__single_inheritance' => 1, '__stdcall' => 1, '__super' => 1, '__try_cast' => 1, '__try' => 1, '__unhook' => 1, '__uuidof' => 1, '__value' => 1, '__virtual_inheritance' => 1, '__w64' => 1
			), 2 => true
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
			0 => "\n", 1 => "\t", 3 => '//', 4 => '#', 7 => '"', 8 => '\'', 9 => '/*'
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
			if (preg_match('~^[a-z]+~i', $part, $matches)) {
				return array(2, $matches[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			if ($delimiters[4] === $letter) {
				return array(4, $delimiters[4], $buffer);
			}
			if (preg_match('~^\\d+~', $part, $matches)) {
				return array(5, $matches[0], $buffer);
			}
			if (preg_match('~^\.\\d+~', $part, $matches)) {
				return array(6, $matches[0], $buffer);
			}
			if ($delimiters[7] === $letter) {
				return array(7, $delimiters[7], $buffer);
			}
			if ($delimiters[8] === $letter) {
				return array(8, $delimiters[8], $buffer);
			}
			if (0 === strpos($part, $delimiters[9])) {
				return array(9, $delimiters[9], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state KEYWORD.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter1($text, $textLength, $textPos)
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

	/**
	 * Finds a delimiter for state NUMBER.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter2($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => 'x', 1 => 'f'
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
			if (preg_match('~^\.\\d+~', $part, $matches)) {
				return array(2, $matches[0], $buffer);
			}
			return array(3, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state HEXA.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter3($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => 'L'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~^[^a-f\\d]+~i', $part, $matches)) {
				return array(1, $matches[0], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state PREPROC.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter4($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\\\n", 1 => "\t", 2 => "\\\r\n", 3 => "\n"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (0 === strpos($part, $delimiters[0])) {
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
	public function findDelimiter5($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"', 1 => '\\\\', 2 => '\\"', 3 => "\n", 4 => "\t"
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
	 * Finds a delimiter for state QUOTE_SINGLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter6($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\'', 1 => '\\\'', 2 => "\n", 3 => "\t"
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
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state COMMENT_BLOCK.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter7($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t", 2 => '*/'
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

	/**
	 * Finds a delimiter for state COMMENT_LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter8($text, $textLength, $textPos)
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

}