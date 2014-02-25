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
 * Texy lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Texy implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Texy';
	}

	/**
	 * Returns initial state.
	 *
	 * @return string
	 */
	public function getInitialState()
	{
		return 'LINE_SINGLE';
	}

	/**
	 * Returns states.
	 *
	 * @return array
	 */
	public function getStates()
	{
		return array(
			'LINE_BODY' => array(
				array(
					'/---' => array('BLOCK_IN', Generator::NEXT),
					'\---' => array('BLOCK_OUT', Generator::NEXT),
					'LINE' => array('LINE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'LINE' => array(
				array(
					'LINE' => array('LINE_DOUBLE', Generator::NEXT),
					'!SPACE' => array('LINE_SINGLE', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'LINE_SINGLE' => array(
				array(
					'##' => array('HEADER_IN', Generator::NEXT),
					'**' => array('HEADER_IN', Generator::NEXT),
					'==' => array('HEADER_IN', Generator::NEXT),
					'--' => array('HEADER_IN', Generator::NEXT),
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'LINE_DOUBLE' => array(
				array(
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'##' => array('HEADER_IN', Generator::NEXT),
					'==' => array('HEADER_IN', Generator::NEXT),
					'--' => array('HORIZONTAL_LINE', Generator::NEXT),
					'- -' => array('HORIZONTAL_LINE', Generator::NEXT),
					'**' => array('HORIZONTAL_LINE', Generator::NEXT),
					'* *' => array('HORIZONTAL_LINE', Generator::NEXT),
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				'texy-err',
				null,
				null
			),
			'HEADER_IN' => array(
				array(
					'=' => array('HEADER_IN', Generator::NEXT),
					'#' => array('HEADER_IN', Generator::NEXT),
					'-' => array('HEADER_IN', Generator::NEXT),
					'*' => array('HEADER_IN', Generator::NEXT),
					'LINE' => array('LINE_DOUBLE', Generator::NEXT),
					'ALL' => array('HEADER_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hlead',
				null
			),
			'HEADER_BODY' => array(
				array(
					'=' => array('HEADER_OUT', Generator::NEXT),
					'#' => array('HEADER_OUT', Generator::NEXT),
					'-' => array('HEADER_OUT', Generator::NEXT),
					'*' => array('HEADER_OUT', Generator::NEXT),
					'LINE' => array('LINE_DOUBLE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hbody',
				null
			),
			'HEADER_OUT' => array(
				array(
					'LINE' => array('LINE_DOUBLE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hlead',
				null
			),
			'HORIZONTAL_LINE' => array(
				array(
					'LINE' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_IN' => array(
				array(
					'html' => array('BLOCK_HTML', Generator::NEXT),
					'code' => array('BLOCK_CODE', Generator::NEXT),
					'div' => array('BLOCK_DUMMY', Generator::NEXT),
					'text' => array('BLOCK_TEXT', Generator::NEXT),
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_OUT' => array(
				array(
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_DUMMY' => array(
				array(
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_TEXT' => array(
				array(
					'LINE' => array('BLOCK_TEXT_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_TEXT_BODY' => array(
				array(
					'LINE' => array('BLOCK_TEXT_BODY_LINE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'texy-text',
				null
			),
			'BLOCK_TEXT_BODY_LINE' => array(
				array(
					'\---' => array('BLOCK_TEXT_BODY_OUT', Generator::NEXT),
					'ALL' => array('BLOCK_TEXT_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-text',
				null
			),
			'BLOCK_TEXT_BODY_OUT' => array(
				array(
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_HTML' => array(
				array(
					'LINE' => array('BLOCK_HTML_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_HTML_BODY' => array(
				array(
					'LINE' => array('BLOCK_HTML_BODY_LINE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'texy-html',
				null
			),
			'BLOCK_HTML_BODY_LINE' => array(
				array(
					'\---' => array('BLOCK_HTML_BODY_OUT', Generator::NEXT),
					'ALL' => array('BLOCK_HTML_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-html',
				null
			),
			'BLOCK_HTML_BODY_OUT' => array(
				array(
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_CODE' => array(
				array(
					'LINE' => array('BLOCK_CODE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
				null
			),
			'BLOCK_CODE_BODY' => array(
				array(
					'LINE' => array('BLOCK_CODE_BODY_LINE', Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				'texy-code',
				null
			),
			'BLOCK_CODE_BODY_LINE' => array(
				array(
					'\---' => array('BLOCK_CODE_BODY_OUT', Generator::NEXT),
					'ALL' => array('BLOCK_CODE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-code',
				null
			),
			'BLOCK_CODE_BODY_OUT' => array(
				array(
					'ALL' => array('LINE_BODY', Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'texy-hr',
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
		return array();
	}
}
