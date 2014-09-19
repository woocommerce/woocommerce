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
 * HTML lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Html implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Html';
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
					'<!--' => array('COMMENT', Generator::NEXT),
					'PHP' => array('PHP', Generator::NEXT),
					'<?' => array(Generator::STATE_SELF, Generator::CURRENT),
					'<' => array('TAG', Generator::NEXT),
					'&' => array('ENTITY', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'ENTITY' => array(
				array(
					';' => array('OUT', Generator::CURRENT),
					'&' => array('OUT', Generator::CURRENT),
					'SPACE' => array('OUT', Generator::CURRENT)
				),
				Generator::STATE_FLAG_NONE,
				'html-entity',
				null
			),
			'TAG' => array(
				array(
					'>' => array('OUT', Generator::CURRENT),
					'SPACE' => array('TAGIN', Generator::NEXT),
					'style' => array('STYLE', Generator::CURRENT),
					'STYLE' => array('STYLE', Generator::CURRENT),
					'script' => array('SCRIPT', Generator::CURRENT),
					'SCRIPT' => array('SCRIPT', Generator::CURRENT),
					'PHP' => array('PHP', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'html-tag',
				null
			),
			'TAGIN' => array(
				array(
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'/>' => array('TAG', Generator::BACK),
					'>' => array('TAG', Generator::BACK),
					'PHP' => array('PHP', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'html-tagin',
				null
			),
			'STYLE' => array(
				array(
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'>' => array('CSS', Generator::NEXT),
					'PHP' => array('PHP', Generator::NEXT),
					'LINE' => array('TAGIN', Generator::NEXT),
					'TAB' => array('TAGIN', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'html-tagin',
				null
			),
			'CSS' => array(
				array(
					'>' => array(Generator::STATE_RETURN, Generator::CURRENT)
				),
				Generator::STATE_FLAG_NEWLEXER,
				'html-tag',
				'Css'
			),
			'SCRIPT' => array(
				array(
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'>' => array('JAVASCRIPT', Generator::NEXT),
					'PHP' => array('PHP', Generator::NEXT),
					'LINE' => array('TAGIN', Generator::NEXT),
					'TAB' => array('TAGIN', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'html-tagin',
				null
			),
			'JAVASCRIPT' => array(
				array(
					'>' => array(Generator::STATE_RETURN, Generator::CURRENT)
				),
				Generator::STATE_FLAG_NEWLEXER,
				'html-tag',
				'Javascript'
			),
			'QUOTE_DOUBLE' => array(
				array(
					'"' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'PHP' => array('PHP', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'html-quote',
				null
			),
			'QUOTE_SINGLE' => array(
				array(
					'\'' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'PHP' => array('PHP', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'html-quote',
				null
			),
			'COMMENT' => array(
				array(
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'-->' => array('OUT', Generator::CURRENT),
					'PHP' => array('PHP', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'html-comment',
				null
			),
			'PHP' => array(
				null,
				Generator::STATE_FLAG_NEWLEXER,
				'xlang',
				'Php'
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
