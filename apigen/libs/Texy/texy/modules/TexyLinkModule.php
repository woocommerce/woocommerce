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
 * Links module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyLinkModule extends TexyModule
{
	/** @var string  root of relative links */
	public $root = '';

	/** @var string image popup event */
	public $imageOnClick = 'return !popupImage(this.href)';  //

	/** @var string class 'popup' event */
	public $popupOnClick = 'return !popup(this.href)';

	/** @var bool  always use rel="nofollow" for absolute links? */
	public $forceNoFollow = FALSE;

	/** @var bool  shorten URLs to more readable form? */
	public $shorten = TRUE;

	/** @var array link references */
	private $references = array();

	/** @var array */
	private static $livelock;



	public function __construct($texy)
	{
		$this->texy = $texy;

		$texy->allowed['link/definition'] = TRUE;
		$texy->addHandler('newReference', array($this, 'solveNewReference'));
		$texy->addHandler('linkReference', array($this, 'solve'));
		$texy->addHandler('linkEmail', array($this, 'solveUrlEmail'));
		$texy->addHandler('linkURL', array($this, 'solveUrlEmail'));
		$texy->addHandler('beforeParse', array($this, 'beforeParse'));

		// [reference]
		$texy->registerLinePattern(
			array($this, 'patternReference'),
			'#(\[[^\[\]\*\n'.TEXY_MARK.']+\])#U',
			'link/reference'
		);

		// direct url and email
		$texy->registerLinePattern(
			array($this, 'patternUrlEmail'),
			'#(?<=^|[\s([<:\x17])(?:https?://|www\.|ftp://)[0-9.'.TEXY_CHAR.'-][/\d'.TEXY_CHAR.'+\.~%&?@=_:;\#,\x{ad}-]+[/\d'.TEXY_CHAR.'+~%?@=_\#]#u',
			'link/url',
			'#(?:https?://|www\.|ftp://)#u'
		);

		$texy->registerLinePattern(
			array($this, 'patternUrlEmail'),
			'#(?<=^|[\s([<:\x17])'.TEXY_EMAIL.'#u',
			'link/email',
			'#'.TEXY_EMAIL.'#u'
		);
	}



	/**
	 * Text pre-processing.
	 * @param  Texy
	 * @param  string
	 * @return void
	 */
	public function beforeParse($texy, & $text)
	{
		self::$livelock = array();

		// [la trine]: http://www.latrine.cz/ text odkazu .(title)[class]{style}
		if (!empty($texy->allowed['link/definition'])) {
			$text = preg_replace_callback(
				'#^\[([^\[\]\#\?\*\n]+)\]: +(\S+)(\ .+)?'.TEXY_MODIFIER.'?\s*()$#mUu',
				array($this, 'patternReferenceDef'),
				$text
			);
		}
	}



	/**
	 * Callback for: [la trine]: http://www.latrine.cz/ text odkazu .(title)[class]{style}.
	 *
	 * @param  array      regexp matches
	 * @return string
	 */
	private function patternReferenceDef($matches)
	{
		list(, $mRef, $mLink, $mLabel, $mMod) = $matches;
		//    [1] => [ (reference) ]
		//    [2] => link
		//    [3] => ...
		//    [4] => .(title)[class]{style}

		$link = new TexyLink($mLink);
		$link->label = trim($mLabel);
		$link->modifier->setProperties($mMod);
		$this->checkLink($link);
		$this->addReference($mRef, $link);
		return '';
	}



	/**
	 * Callback for: [ref].
	 *
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function patternReference($parser, $matches)
	{
		list(, $mRef) = $matches;
		//    [1] => [ref]

		$tx = $this->texy;
		$name = substr($mRef, 1, -1);
		$link = $this->getReference($name);

		if (!$link) {
			return $tx->invokeAroundHandlers('newReference', $parser, array($name));
		}

		$link->type = TexyLink::BRACKET;

		if ($link->label != '') {  // NULL or ''
			// prevent circular references
			if (isset(self::$livelock[$link->name])) {
				$content = $link->label;
			} else {
				self::$livelock[$link->name] = TRUE;
				$el = TexyHtml::el();
				$lineParser = new TexyLineParser($tx, $el);
				$lineParser->parse($link->label);
				$content = $el->toString($tx);
				unset(self::$livelock[$link->name]);
			}
		} else {
			$content = $this->textualUrl($link);
			$content = $this->texy->protect($content, Texy::CONTENT_TEXTUAL);
		}

		return $tx->invokeAroundHandlers('linkReference', $parser, array($link, $content));
	}



	/**
	 * Callback for: http://davidgrudl.com   david@grudl.com.
	 *
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function patternUrlEmail($parser, $matches, $name)
	{
		list($mURL) = $matches;
		//    [0] => URL

		$link = new TexyLink($mURL);
		$this->checkLink($link);

		return $this->texy->invokeAroundHandlers(
			$name === 'link/email' ? 'linkEmail' : 'linkURL',
			$parser,
			array($link)
		);
	}



	/**
	 * Adds new named reference.
	 *
	 * @param  string  reference name
	 * @param  TexyLink
	 * @return void
	 */
	public function addReference($name, TexyLink $link)
	{
		$link->name = TexyUtf::strtolower($name);
		$this->references[$link->name] = $link;
	}



	/**
	 * Returns named reference.
	 *
	 * @param  string  reference name
	 * @return TexyLink reference descriptor (or FALSE)
	 */
	public function getReference($name)
	{
		$name = TexyUtf::strtolower($name);
		if (isset($this->references[$name])) {
			return clone $this->references[$name];

		} else {
			$pos = strpos($name, '?');
			if ($pos === FALSE) $pos = strpos($name, '#');
			if ($pos !== FALSE) { // try to extract ?... #... part
				$name2 = substr($name, 0, $pos);
				if (isset($this->references[$name2])) {
					$link = clone $this->references[$name2];
					$link->URL .= substr($name, $pos);
					return $link;
				}
			}
		}

		return FALSE;
	}



	/**
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return TexyLink
	 */
	public function factoryLink($dest, $mMod, $label)
	{
		$tx = $this->texy;
		$type = TexyLink::COMMON;

		// [ref]
		if (strlen($dest)>1 && $dest{0} === '[' && $dest{1} !== '*') {
			$type = TexyLink::BRACKET;
			$dest = substr($dest, 1, -1);
			$link = $this->getReference($dest);

		// [* image *]
		} elseif (strlen($dest)>1 && $dest{0} === '[' && $dest{1} === '*') {
			$type = TexyLink::IMAGE;
			$dest = trim(substr($dest, 2, -2));
			$image = $tx->imageModule->getReference($dest);
			if ($image) {
				$link = new TexyLink($image->linkedURL === NULL ? $image->URL : $image->linkedURL);
				$link->modifier = $image->modifier;
			}
		}

		if (empty($link)) {
			$link = new TexyLink(trim($dest));
			$this->checkLink($link);
		}

		if (strpos($link->URL, '%s') !== FALSE) {
			$link->URL = str_replace('%s', urlencode($tx->stringToText($label)), $link->URL);
		}
		$link->modifier->setProperties($mMod);
		$link->type = $type;
		return $link;
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  TexyLink
	 * @param  TexyHtml|string
	 * @return TexyHtml|string
	 */
	public function solve($invocation, $link, $content = NULL)
	{
		if ($link->URL == NULL) return $content;

		$tx = $this->texy;

		$el = TexyHtml::el('a');

		if (empty($link->modifier)) {
			$nofollow = $popup = FALSE;
		} else {
			$nofollow = isset($link->modifier->classes['nofollow']);
			$popup = isset($link->modifier->classes['popup']);
			unset($link->modifier->classes['nofollow'], $link->modifier->classes['popup']);
			$el->attrs['href'] = NULL; // trick - move to front
			$link->modifier->decorate($tx, $el);
		}

		if ($link->type === TexyLink::IMAGE) {
			// image
			$el->attrs['href'] = Texy::prependRoot($link->URL, $tx->imageModule->linkedRoot);
			$el->attrs['onclick'] = $this->imageOnClick;

		} else {
			$el->attrs['href'] = Texy::prependRoot($link->URL, $this->root);

			// rel="nofollow"
			if ($nofollow || ($this->forceNoFollow && strpos($el->attrs['href'], '//') !== FALSE))
				$el->attrs['rel'] = 'nofollow';
		}

		// popup on click
		if ($popup) $el->attrs['onclick'] = $this->popupOnClick;

		if ($content !== NULL) $el->add($content);

		$tx->summary['links'][] = $el->attrs['href'];

		return $el;
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  TexyLink
	 * @return TexyHtml|string
	 */
	public function solveUrlEmail($invocation, $link)
	{
		$content = $this->textualUrl($link);
		$content = $this->texy->protect($content, Texy::CONTENT_TEXTUAL);
		return $this->solve(NULL, $link, $content);
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  string
	 * @return FALSE
	 */
	public function solveNewReference($invocation, $name)
	{
		// no change
		return FALSE;
	}



	/**
	 * Checks and corrects $URL.
	 * @param  TexyLink
	 * @return void
	 */
	private function checkLink($link)
	{
		// remove soft hyphens; if not removed by Texy::process()
		$link->URL = str_replace("\xC2\xAD", '', $link->URL);

		if (strncasecmp($link->URL, 'www.', 4) === 0) {
			// special supported case
			$link->URL = 'http://' . $link->URL;

		} elseif (preg_match('#'.TEXY_EMAIL.'$#Au', $link->URL)) {
			// email
			$link->URL = 'mailto:' . $link->URL;

		} elseif (!$this->texy->checkURL($link->URL, Texy::FILTER_ANCHOR)) {
			$link->URL = NULL;

		} else {
			$link->URL = str_replace('&amp;', '&', $link->URL); // replace unwanted &amp;
		}
	}



	/**
	 * Returns textual representation of URL.
	 * @param  TexyLink
	 * @return string
	 */
	private function textualUrl($link)
	{
		if ($this->texy->obfuscateEmail && preg_match('#^'.TEXY_EMAIL.'$#u', $link->raw)) { // email
			return str_replace('@', "&#64;<!---->", $link->raw);
		}

		if ($this->shorten && preg_match('#^(https?://|ftp://|www\.|/)#i', $link->raw)) {

			$raw = strncasecmp($link->raw, 'www.', 4) === 0 ? 'none://' . $link->raw : $link->raw;

			// parse_url() in PHP damages UTF-8 - use regular expression
			if (!preg_match('~^(?:(?P<scheme>[a-z]+):)?(?://(?P<host>[^/?#]+))?(?P<path>(?:/|^)(?!/)[^?#]*)?(?:\?(?P<query>[^#]*))?(?:#(?P<fragment>.*))?()$~', $raw, $parts)) {
				return $link->raw;
			}

			$res = '';
			if ($parts['scheme'] !== '' && $parts['scheme'] !== 'none')
				$res .= $parts['scheme'] . '://';

			if ($parts['host']  !== '')
				$res .= $parts['host'];

			if ($parts['path']  !== '')
				$res .=  (iconv_strlen($parts['path'], 'UTF-8') > 16 ? ("/\xe2\x80\xa6" . iconv_substr($parts['path'], -12, 12, 'UTF-8')) : $parts['path']);

			if ($parts['query']  !== '') {
				$res .= iconv_strlen($parts['query'], 'UTF-8') > 4 ? "?\xe2\x80\xa6" : ('?' . $parts['query']);
			} elseif ($parts['fragment']  !== '') {
				$res .= iconv_strlen($parts['fragment'], 'UTF-8') > 4 ? "#\xe2\x80\xa6" : ('#' . $parts['fragment']);
			}
			return $res;
		}

		return $link->raw;
	}

}









/**
 * @package Texy
 */
final class TexyLink extends TexyObject
{
	/** @see $type */
	const
		COMMON = 1,
		BRACKET = 2,
		IMAGE = 3;

	/** @var string  URL in resolved form */
	public $URL;

	/** @var string  URL as written in text */
	public $raw;

	/** @var TexyModifier */
	public $modifier;

	/** @var int  how was link created? */
	public $type = TexyLink::COMMON;

	/** @var string  optional label, used by references */
	public $label;

	/** @var string  reference name (if is stored as reference) */
	public $name;



	public function __construct($URL)
	{
		$this->URL = $URL;
		$this->raw = $URL;
		$this->modifier = new TexyModifier;
	}



	public function __clone()
	{
		if ($this->modifier) {
			$this->modifier = clone $this->modifier;
		}
	}

}
