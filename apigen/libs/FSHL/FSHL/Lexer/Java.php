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

namespace FSHL\Lexer;

use FSHL, FSHL\Generator;

/**
 * Java lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Java implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Java';
	}

	/**
	 * Returns initial state.
	 *
	 * @return string
	 */
	public function getInitialState()
	{
		return 'OUT';
	}

	/**
	 * Returns states.
	 *
	 * @return array
	 */
	public function getStates()
	{
		return array(
			'OUT' => array(
				array(
					'ALPHA' => array('KEYWORD', Generator::BACK),
					'NUM' => array('NUMBER', Generator::NEXT),
					'DOTNUM' => array('NUMBER', Generator::NEXT),
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'/*' => array('COMMENT_BLOCK', Generator::NEXT),
					'//' => array('COMMENT_LINE', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'KEYWORD' => array(
				array(
					'!ALNUM_' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_KEYWORD | Generator::STATE_FLAG_RECURSION,
				null,
				null
			),
			'NUMBER' => array(
				array(
					'x' => array('HEXA', Generator::NEXT),
					'DOTNUM' => array('NUMBER', Generator::NEXT),
					'ALL' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'java-num',
				null
			),
			'HEXA' => array(
				array(
					'!HEXNUM' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'java-num',
				null
			),
			'QUOTE_DOUBLE' => array(
				array(
					'"' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\\' => array(Generator::STATE_SELF, Generator::NEXT),
					'\\"' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'java-quote',
				null
			),
			'QUOTE_SINGLE' => array(
				array(
					'\'' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\\' => array(Generator::STATE_SELF, Generator::NEXT),
					'\\\'' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'java-quote',
				null
			),
			'COMMENT_BLOCK' => array(
				array(
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'*/' => array(Generator::STATE_RETURN, Generator::CURRENT)
				),
				Generator::STATE_FLAG_RECURSION,
				'java-comment',
				null
			),
			'COMMENT_LINE' => array(
				array(
					'LINE' => array(Generator::STATE_RETURN, Generator::BACK),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'java-comment',
				null
			)
		);
	}

	/**
	 * Returns special delimiters.
	 *
	 * @return array
	 */
	public function getDelimiters()
	{
		return array();
	}

	/**
	 * Returns keywords.
	 *
	 * @return array
	 */
	public function getKeywords()
	{
		return array(
			'java-keywords',
			array(
				'abstract' => 1,
				'double' => 1,
				'int' => 1,
				'strictfp' => 1,
				'boolean' => 1,
				'else' => 1,
				'interface' => 1,
				'super' => 1,
				'break' => 1,
				'extends' => 1,
				'long' => 1,
				'switch' => 1,
				'byte' => 1,
				'final' => 1,
				'native' => 1,
				'synchronized' => 1,
				'case' => 1,
				'finally' => 1,
				'new' => 1,
				'this' => 1,
				'catch' => 1,
				'float' => 1,
				'package' => 1,
				'throw' => 1,
				'char' => 1,
				'for' => 1,
				'private' => 1,
				'throws' => 1,
				'class' => 1,
				'goto' => 1,
				'protected' => 1,
				'transient' => 1,
				'const' => 1,
				'if' => 1,
				'public' => 1,
				'try' => 1,
				'continue' => 1,
				'implements' => 1,
				'return' => 1,
				'void' => 1,
				'default' => 1,
				'import' => 1,
				'short' => 1,
				'volatile' => 1,
				'do' => 1,
				'instanceof' => 1,
				'static' => 1,
				'while' => 1
			),
			Generator::CASE_SENSITIVE
		);
	}
}
