<?php

/**
 * Texy! - human-readable text to HTML converter.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE version 2 or 3
 * @link       http://texy.info
 * @package    Texy
 */



/**
 * Typography replacements module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyTypographyModule extends TexyModule
{
	// @see http://www.unicode.org/cldr/data/charts/by_type/misc.delimiters.html

	public static $locales = array(
		'cs' => array(
			'singleQuotes' => array("\xe2\x80\x9a", "\xe2\x80\x98"), // U+201A, U+2018
			'doubleQuotes' => array("\xe2\x80\x9e", "\xe2\x80\x9c"), // U+201E, U+201C
		),

		'en' => array(
			'singleQuotes' => array("\xe2\x80\x98", "\xe2\x80\x99"), // U+2018, U+2019
			'doubleQuotes' => array("\xe2\x80\x9c", "\xe2\x80\x9d"), // U+201C, U+201D
		),

		'fr' => array(
			'singleQuotes' => array("\xe2\x80\xb9", "\xe2\x80\xba"), // U+2039, U+203A
			'doubleQuotes' => array("\xc2\xab", "\xc2\xbb"),         // U+00AB, U+00BB
		),

		'de' => array(
			'singleQuotes' => array("\xe2\x80\x9a", "\xe2\x80\x98"), // U+201A, U+2018
			'doubleQuotes' => array("\xe2\x80\x9e", "\xe2\x80\x9c"), // U+201E, U+201C
		),

		'pl' => array(
			'singleQuotes' => array("\xe2\x80\x9a", "\xe2\x80\x99"), // U+201A, U+2019
			'doubleQuotes' => array("\xe2\x80\x9e", "\xe2\x80\x9d"), // U+201E, U+201D
		),
	);

	/** @var string */
	public $locale = 'cs';

	/** @var array */
	private $pattern, $replace;



	public function __construct($texy)
	{
		$this->texy = $texy;
		$texy->registerPostLine(array($this, 'postLine'), 'typography');
		$texy->addHandler('beforeParse', array($this, 'beforeParse'));
	}



	/**
	 * Text pre-processing.
	 * @param  Texy
	 * @param  string
	 * @return void
	 */
	public function beforeParse($texy, & $text)
	{
		// CONTENT_MARKUP mark:   \x17-\x1F
		// CONTENT_REPLACED mark: \x16
		// CONTENT_TEXTUAL mark:  \x17
		// CONTENT_BLOCK: not used in postLine

		if (isset(self::$locales[$this->locale]))
			$locale = self::$locales[$this->locale];
		else // fall back
			$locale = self::$locales['en'];

		$pairs = array(
			'#(?<![.\x{2026}])\.{3,4}(?![.\x{2026}])#mu' => "\xe2\x80\xa6",                // ellipsis  ...
			'#(?<=[\d ]|^)-(?=[\d ]|$)#'              => "\xe2\x80\x93",                   // en dash 123-123
			'#(?<=[^!*+,/:;<=>@\\\\_|-])--(?=[^!*+,/:;<=>@\\\\_|-])#' => "\xe2\x80\x93",   // en dash alphanum--alphanum
			'#,-#'                                    => ",\xe2\x80\x93",                  // en dash ,-
			'#(?<!\d)(\d{1,2}\.) (\d{1,2}\.) (\d\d)#' => "\$1\xc2\xa0\$2\xc2\xa0\$3",      // date 23. 1. 1978
			'#(?<!\d)(\d{1,2}\.) (\d{1,2}\.)#'        => "\$1\xc2\xa0\$2",                 // date 23. 1.
			'# --- #'                                 => "\xc2\xa0\xe2\x80\x94 ",          // em dash ---
			'# ([\x{2013}\x{2014}])#u'                => "\xc2\xa0\$1",                    // &nbsp; behind dash (dash stays at line end)
			'# <-{1,2}> #'                            => " \xe2\x86\x94 ",                 // left right arrow <-->
			'#-{1,}> #'                               => " \xe2\x86\x92 ",                 // right arrow -->
			'# <-{1,}#'                               => " \xe2\x86\x90 ",                 // left arrow <--
			'#={1,}> #'                               => " \xe2\x87\x92 ",                 // right arrow ==>
			'#\\+-#'                                  => "\xc2\xb1",                       // +-
			'#(\d+)( ?)x\\2(?=\d)#'                   => "\$1\xc3\x97",                    // dimension sign 123 x 123...
			'#(?<=\d)x(?= |,|.|$)#m'                  => "\xc3\x97",                       // dimension sign 123x
			'#(\S ?)\(TM\)#i'                         => "\$1\xe2\x84\xa2",                // trademark (TM)
			'#(\S ?)\(R\)#i'                          => "\$1\xc2\xae",                    // registered (R)
			'#\(C\)( ?\S)#i'                          => "\xc2\xa9\$1",                    // copyright (C)
			'#\(EUR\)#'                               => "\xe2\x82\xac",                   // Euro (EUR)
			'#(\d) (?=\d{3})#'                        => "\$1\xc2\xa0",                    // (phone) number 1 123 123 123...

			'#(?<=[^\s\x17])\s+([\x17-\x1F]+)(?=\s)#u'=> "\$1",                            // remove intermarkup space phase 1
			'#(?<=\s)([\x17-\x1F]+)\s+#u'             => "\$1",                            // remove intermarkup space phase 2

			'#(?<=.{50})\s+(?=[\x17-\x1F]*\S{1,6}[\x17-\x1F]*$)#us' => "\xc2\xa0",         // space before last short word

			// nbsp space between number (optionally followed by dot) and word, symbol, punctation, currency symbol
			'#(?<=^| |\.|,|-|\+|\x16|\(|\d\x{A0})([\x17-\x1F]*\d+\.?[\x17-\x1F]*)\s+(?=[\x17-\x1F]*[%'.TEXY_CHAR.'\x{b0}-\x{be}\x{2020}-\x{214f}])#mu'
													=> "\$1\xc2\xa0",

			// space between preposition and word
			'#(?<=^|[^0-9'.TEXY_CHAR.'])([\x17-\x1F]*[ksvzouiKSVZOUIA][\x17-\x1F]*)\s+(?=[\x17-\x1F]*[0-9'.TEXY_CHAR.'])#mus'
													=> "\$1\xc2\xa0",

			'#(?<!"|\w)"(?!\ |")(.+)(?<!\ |")"(?!")()#U' => $locale['doubleQuotes'][0].'$1'.$locale['doubleQuotes'][1], // double ""
			'#(?<!\'|\w)\'(?!\ |\')(.+)(?<!\ |\')\'(?!\')()#Uu' => $locale['singleQuotes'][0].'$1'.$locale['singleQuotes'][1], // single ''
		);

		$this->pattern = array_keys($pairs);
		$this->replace = array_values($pairs);
	}



	public function postLine($text, $preserveSpaces = FALSE)
	{
		if (!$preserveSpaces) {
			$text = preg_replace('# {2,}#', ' ', $text);
		}
		return preg_replace($this->pattern, $this->replace, $text);
	}

}