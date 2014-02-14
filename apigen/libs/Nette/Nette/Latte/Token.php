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
 * Latte parser token.
 *
 * @author     David Grudl
 */
class Token extends Nette\Object
{
	const TEXT = 'text',
		MACRO_TAG = 'macroTag', // latte macro tag
		HTML_TAG_BEGIN = 'htmlTagBegin', // begin of HTML tag or comment
		HTML_TAG_END = 'htmlTagEnd', // end of HTML tag or comment
		HTML_ATTRIBUTE = 'htmlAttribute',
		COMMENT = 'comment'; // latte comment

	/** @var string  token type [TEXT | MACRO_TAG | HTML_TAG_BEGIN | HTML_TAG_END | HTML_ATTRIBUTE | COMMENT] */
	public $type;

	/** @var string  content of the token */
	public $text;

	/** @var int  line number */
	public $line;

	/** @var string  name of macro tag, HTML tag or attribute; used for types MACRO_TAG, HTML_TAG_BEGIN, HTML_ATTRIBUTE */
	public $name;

	/** @var string  value of macro tag or HTML attribute; used for types MACRO_TAG, HTML_ATTRIBUTE */
	public $value;

	/** @var string  macro modifiers; used for type MACRO_TAG */
	public $modifiers;

	/** @var bool  is closing HTML tag? used for type HTML_TAG_BEGIN */
	public $closing;

}
