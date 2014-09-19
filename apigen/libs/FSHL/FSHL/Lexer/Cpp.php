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
 * CPP lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Cpp implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Cpp';
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
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'ALPHA' => array('KEYWORD', Generator::BACK),
					'//' => array('COMMENT_LINE', Generator::NEXT),
					'#' => array('PREPROC', Generator::NEXT),
					'NUM' => array('NUMBER', Generator::NEXT),
					'DOTNUM' => array('NUMBER', Generator::NEXT),
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'/*' => array('COMMENT_BLOCK', Generator::NEXT)
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
					'f' => array(Generator::STATE_SELF, Generator::NEXT),
					'DOTNUM' => array(Generator::STATE_SELF, Generator::NEXT),
					'ALL' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'cpp-num',
				null
			),
			'HEXA' => array(
				array(
					'L' => array(Generator::STATE_SELF, Generator::NEXT),
					'!HEXNUM' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'cpp-num',
				null
			),
			'PREPROC' => array(
				array(
					"\\\n" => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					"\\\xd\xa" => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'cpp-preproc',
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
				'cpp-quote',
				null
			),
			'QUOTE_SINGLE' => array(
				array(
					'\'' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\'' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'cpp-quote',
				null
			),
			'COMMENT_BLOCK' => array(
				array(
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT),
					'*/' => array(Generator::STATE_RETURN, Generator::CURRENT)
				),
				Generator::STATE_FLAG_RECURSION,
				'cpp-comment',
				null
			),
			'COMMENT_LINE' => array(
				array(
					'LINE' => array(Generator::STATE_RETURN, Generator::BACK),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'cpp-comment',
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
			'cpp-keywords',
			array(
				'bool' => 1,
				'break' => 1,
				'case' => 1,
				'catch' => 1,
				'char' => 1,
				'class' => 1,
				'const' => 1,
				'const_cast' => 1,
				'continue' => 1,
				'default' => 1,
				'delete' => 1,
				'deprecated' => 1,
				'dllexport' => 1,
				'dllimport' => 1,
				'do' => 1,
				'double' => 1,
				'dynamic_cast' => 1,
				'else' => 1,
				'enum' => 1,
				'explicit' => 1,
				'extern' => 1,
				'false' => 1,
				'float' => 1,
				'for' => 1,
				'friend' => 1,
				'goto' => 1,
				'if' => 1,
				'inline' => 1,
				'int' => 1,
				'long' => 1,
				'mutable' => 1,
				'naked' => 1,
				'namespace' => 1,
				'new' => 1,
				'noinline' => 1,
				'noreturn' => 1,
				'nothrow' => 1,
				'novtable' => 1,
				'operator' => 1,
				'private' => 1,
				'property' => 1,
				'protected' => 1,
				'public' => 1,
				'register' => 1,
				'reinterpret_cast' => 1,
				'return' => 1,
				'selectany' => 1,
				'short' => 1,
				'signed' => 1,
				'sizeof' => 1,
				'static' => 1,
				'static_cast' => 1,
				'struct' => 1,
				'switch' => 1,
				'template' => 1,
				'this' => 1,
				'thread' => 1,
				'throw' => 1,
				'true' => 1,
				'try' => 1,
				'typedef' => 1,
				'typeid' => 1,
				'typename' => 1,
				'union' => 1,
				'unsigned' => 1,
				'using' => 1,
				'uuid' => 1,
				'virtual' => 1,
				'void' => 1,
				'volatile' => 1,
				'__wchar_t' => 1,
				'wchar_t' => 1,
				'while' => 1,
				'__abstract' => 1,
				'__alignof' => 1,
				'__asm' => 1,
				'__assume' => 1,
				'__based' => 1,
				'__box' => 1,
				'__cdecl' => 1,
				'__declspec' => 1,
				'__delegate' => 1,
				'__event' => 1,
				'__except' => 1,
				'__fastcall' => 1,
				'__finally' => 1,
				'__forceinline' => 1,
				'__gc' => 1,
				'__hook' => 1,
				'__identifier' => 1,
				'__if_exists' => 1,
				'__if_not_exists' => 1,
				'__inline' => 1,
				'__int8' => 1,
				'__int16' => 1,
				'__int32' => 1,
				'__int64' => 1,
				'__interface' => 1,
				'__leave' => 1,
				'__m64' => 1,
				'__m128' => 1,
				'__m128d' => 1,
				'__m128i' => 1,
				'__multiple_inheritance' => 1,
				'__nogc' => 1,
				'__noop' => 1,
				'__pin' => 1,
				'__property' => 1,
				'__raise' => 1,
				'__sealed' => 1,
				'__single_inheritance' => 1,
				'__stdcall' => 1,
				'__super' => 1,
				'__try_cast' => 1,
				'__try' => 1,
				'__except' => 1,
				'__finally' => 1,
				'__unhook' => 1,
				'__uuidof' => 1,
				'__value' => 1,
				'__virtual_inheritance' => 1,
				'__w64' => 1
			),
			Generator::CASE_SENSITIVE
		);
	}
}
