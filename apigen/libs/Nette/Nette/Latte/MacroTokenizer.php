<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Latte;

use Nette;



/**
 * Macro tag tokenizer.
 *
 * @author     David Grudl
 */
class MacroTokenizer extends Nette\Utils\Tokenizer
{
	const T_WHITESPACE = 1,
		T_COMMENT = 2,
		T_SYMBOL = 3,
		T_NUMBER = 4,
		T_VARIABLE = 5,
		T_STRING = 6,
		T_CAST = 7,
		T_KEYWORD = 8,
		T_CHAR = 9;



	public function __construct($input)
	{
		parent::__construct(array(
			self::T_WHITESPACE => '\s+',
			self::T_COMMENT => '(?s)/\*.*?\*/',
			self::T_STRING => Parser::RE_STRING,
			self::T_KEYWORD => '(?:true|false|null|and|or|xor|clone|new|instanceof|return|continue|break|[A-Z_][A-Z0-9_]{2,})(?![\w\pL_])', // keyword or const
			self::T_CAST => '\((?:expand|string|array|int|integer|float|bool|boolean|object)\)', // type casting
			self::T_VARIABLE => '\$[\w\pL_]+',
			self::T_NUMBER => '[+-]?[0-9]+(?:\.[0-9]+)?(?:e[0-9]+)?',
			self::T_SYMBOL => '[\w\pL_]+(?:-[\w\pL_]+)*',
			self::T_CHAR => '::|=>|[^"\']', // =>, any char except quotes
		), 'u');
		$this->ignored = array(self::T_COMMENT, self::T_WHITESPACE);
		$this->tokenize($input);
	}



	/**
	 * Reads single token (optionally delimited by comma) from string.
	 * @param  string
	 * @return string
	 */
	public function fetchWord()
	{
		$word = $this->fetchUntil(self::T_WHITESPACE, ',');
		$this->fetch(',');
		$this->fetchAll(self::T_WHITESPACE, self::T_COMMENT);
		return $word;
	}

}
