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
 * Neon lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Neon implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Neon';
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
					'SECTION' => array('SECTION', Generator::NEXT),
					'KEY' => array('KEY', Generator::NEXT),
					'#' => array('COMMENT', Generator::NEXT),
					'-' => array('LIST', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'SECTION' => array(
				array(
					'<' => array('SEPARATOR', Generator::NEXT),
					':' => array('SEPARATOR', Generator::NEXT),
					'SECTION' => array(Generator::STATE_SELF, Generator::NEXT),
					'KEY' => array('KEY', Generator::NEXT),
					'-' => array('LIST', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'neon-section',
				null
			),
			'KEY' => array(
				array(
					':' => array('SEPARATOR', Generator::NEXT),
					'=' => array('SEPARATOR', Generator::NEXT),
					'ALL' => array('VALUE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'neon-key',
				null
			),
			'LIST' => array(
				array(
					'ALL' => array('VALUE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'neon-sep',
				null
			),
			'VALUE' => array(
				array(
					'LINE' => array('OUT', Generator::NEXT),
					'KEY' => array('KEY', Generator::NEXT),
					'#' => array('COMMENT', Generator::NEXT),
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'[' => array('SEPARATOR', Generator::NEXT),
					']' => array('SEPARATOR', Generator::NEXT),
					'{' => array('SEPARATOR', Generator::NEXT),
					'}' => array('SEPARATOR', Generator::NEXT),
					'=' => array('SEPARATOR', Generator::NEXT),
					',' => array('SEPARATOR', Generator::NEXT),
					':' => array('SEPARATOR', Generator::NEXT),
					'TEXT' => array('TEXT', Generator::NEXT),
					'NUM' => array('NUMBER', Generator::NEXT),
					'DOTNUM' => array('NUMBER', Generator::NEXT),
					'VARIABLE' => array('VARIABLE', Generator::NEXT),
					'@' => array('REFERENCE', Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'neon-value',
				null
			),
			'TEXT' => array(
				array(
					'VARIABLE' => array('VARIABLE', Generator::NEXT),
					'#' => array('COMMENT', Generator::NEXT),
					'LINE' => array('OUT', Generator::NEXT),
				),
				Generator::STATE_FLAG_NONE,
				'neon-value',
				null
			),
			'SEPARATOR' => array(
				array(
					'ALL' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'neon-sep',
				null
			),
			'COMMENT' => array(
				array(
					'LINE' => array('OUT', Generator::BACK),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'neon-comment',
				null
			),
			'QUOTE_DOUBLE' => array(
				array(
					'"' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'VARIABLE' => array('VARIABLE', Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'neon-quote',
				null
			),
			'QUOTE_SINGLE' => array(
				array(
					'\'' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'VARIABLE' => array('VARIABLE', Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'neon-quote',
				null
			),
			'VARIABLE' => array(
				array(
					'%' => array(Generator::STATE_RETURN, Generator::CURRENT)
				),
				Generator::STATE_FLAG_RECURSION,
				'neon-var',
				null
			),
			'NUMBER' => array(
				array(
					'DOTNUM' => array(Generator::STATE_SELF, Generator::NEXT),
					'ALL' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'neon-num',
				null
			),
			'REFERENCE' => array(
				array(
					'!ALNUM_' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'neon-ref',
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
			'SECTION' => 'preg_match(\'~[\\\\w.]+(?=(\\\\s*<\\\\s*[\\\\w.]+)?\\\\s*:\\\\s*\\n)~Ai\', $text, $matches, 0, $textPos)',
			'KEY' => 'preg_match(\'~[\\\\w.]+(?=\\\\s*(?::|=))~Ai\', $text, $matches, 0, $textPos)',
			'VARIABLE' => 'preg_match(\'~%\\\\w+(?=%)~Ai\', $text, $matches, 0, $textPos)',
			'TEXT' => 'preg_match(\'~[a-z](?![,\\\\]}#\\n])~Ai\', $text, $matches, 0, $textPos)'
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
