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
 * CSS lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Css implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Css';
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
					'FUNC' => array('FUNC', Generator::NEXT),
					'ALNUM' => array('TAG', Generator::NEXT),
					'*' => array('TAG', Generator::NEXT),
					'#' => array('ID', Generator::NEXT),
					'.' => array('CLASS', Generator::NEXT),
					'{' => array('DEF', Generator::NEXT),
					'/*' => array('COMMENT', Generator::NEXT),
					'@media' => array('MEDIA', Generator::NEXT),
					'@' => array('AT_RULE', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'</' => array(Generator::STATE_QUIT, Generator::NEXT),
					'PHP' => array('PHP', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'MEDIA' => array(
				array(
					'PROPERTY' => array('PROPERTY', Generator::NEXT),
					':' => array('VALUE', Generator::CURRENT),
					';' => array(Generator::STATE_SELF, Generator::CURRENT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					')' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-at-rule',
				null
			),
			'AT_RULE' => array(
				array(
					'SPACE' => array(Generator::STATE_RETURN, Generator::BACK),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-at-rule',
				null
			),
			'TAG' => array(
				array(
					'{' => array(Generator::STATE_RETURN, Generator::NEXT),
					',' => array(Generator::STATE_RETURN, Generator::BACK),
					'SPACE' => array(Generator::STATE_RETURN, Generator::BACK),
					':' => array('PSEUDO', Generator::NEXT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-tag',
				null
			),
			'ID' => array(
				array(
					'{' => array(Generator::STATE_RETURN, Generator::BACK),
					',' => array(Generator::STATE_RETURN, Generator::BACK),
					'SPACE' => array(Generator::STATE_RETURN, Generator::BACK),
					':' => array('PSEUDO', Generator::NEXT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-id',
				null
			),
			'CLASS' => array(
				array(
					'{' => array(Generator::STATE_RETURN, Generator::BACK),
					'SPACE' => array(Generator::STATE_RETURN, Generator::BACK),
					',' => array(Generator::STATE_RETURN, Generator::BACK),
					':' => array('PSEUDO', Generator::NEXT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-class',
				null
			),
			'PSEUDO' => array(
				array(
					'SPACE' => array(Generator::STATE_RETURN, Generator::BACK),
					',' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-pseudo',
				null
			),
			'DEF' => array(
				array(
					'PROPERTY' => array('PROPERTY', Generator::NEXT),
					':' => array('VALUE', Generator::CURRENT),
					';' => array(Generator::STATE_SELF, Generator::CURRENT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'}' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				null,
				null
			),
			'PROPERTY' => array(
				array(
					':' => array(Generator::STATE_RETURN, Generator::BACK),
					'}' => array(Generator::STATE_RETURN, Generator::BACK),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-property',
				null
			),
			'VALUE' => array(
				array(
					'#' => array('COLOR', Generator::NEXT),
					';' => array(Generator::STATE_RETURN, Generator::BACK),
					'FUNC' => array('FUNC', Generator::NEXT),
					')' => array(Generator::STATE_RETURN, Generator::BACK),
					'}' => array(Generator::STATE_RETURN, Generator::BACK),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'/*' => array('COMMENT', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-value',
				null
			),
			'FUNC' => array(
				array(
					')' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'ALL' => array('VALUE', Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-func',
				null
			),
			'COLOR' => array(
				array(
					'!HEXNUM' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-color',
				null
			),
			'COMMENT' => array(
				array(
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'*/' => array(Generator::STATE_RETURN, Generator::CURRENT)
				),
				Generator::STATE_FLAG_RECURSION,
				'css-comment',
				null
			),
			'PHP' => array(
				null,
				Generator::STATE_FLAG_NEWLEXER,
				'xlang',
				'Php'
			),
			Generator::STATE_QUIT => array(
				null,
				Generator::STATE_FLAG_NEWLEXER,
				'html-tag',
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
		return array(
			'FUNC' => 'preg_match(\'~[a-z]+\\s*\\(~iA\', $text, $matches, 0, $textPos)',
			'PROPERTY' => 'preg_match(\'~[-a-z]+~iA\', $text, $matches, 0, $textPos)',
			'PHP' => 'preg_match(\'~<\\\\?(php|=|(?!xml))~A\', $text, $matches, 0, $textPos)'
		);
	}

	/**
	 * Returns keywords.
	 *
	 * @return array
	 */
	public function getKeywords()
	{
		return array();
	}
}
