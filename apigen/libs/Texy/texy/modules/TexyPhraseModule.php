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
 * Phrases module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyPhraseModule extends TexyModule
{

	public $tags = array(
		'phrase/strong' => 'strong', // or 'b'
		'phrase/em' => 'em', // or 'i'
		'phrase/em-alt' => 'em',
		'phrase/em-alt2' => 'em',
		'phrase/ins' => 'ins',
		'phrase/del' => 'del',
		'phrase/sup' => 'sup',
		'phrase/sup-alt' => 'sup',
		'phrase/sub' => 'sub',
		'phrase/sub-alt' => 'sub',
		'phrase/span' => 'a',
		'phrase/span-alt' => 'a',
		'phrase/cite' => 'cite',
		'phrase/acronym' => 'acronym',
		'phrase/acronym-alt' => 'acronym',
		'phrase/code'  => 'code',
		'phrase/quote' => 'q',
		'phrase/quicklink' => 'a',
	);


	/** @var bool  are links allowed? */
	public $linksAllowed = TRUE;



	public function __construct($texy)
	{
		$this->texy = $texy;

		$texy->addHandler('phrase', array($this, 'solve'));

/*
		// UNIVERSAL
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#((?>([*+/^_"~`-])+?))(?!\s)(.*(?!\\2).)'.TEXY_MODIFIER.'?(?<!\s)\\1(?!\\2)'.TEXY_LINK.'??()#Uus',
			'phrase/strong'
		);
*/

		// ***strong+emphasis***
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\*)\*\*\*(?![\s*])(.+)'.TEXY_MODIFIER.'?(?<![\s*])\*\*\*(?!\*)'.TEXY_LINK.'??()#Uus',
			'phrase/strong+em'
		);

		// **strong**
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\*)\*\*(?![\s*])(.+)'.TEXY_MODIFIER.'?(?<![\s*])\*\*(?!\*)'.TEXY_LINK.'??()#Uus',
			'phrase/strong'
		);

		// //emphasis//
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<![/:])\/\/(?![\s/])(.+)'.TEXY_MODIFIER.'?(?<![\s/:])\/\/(?!\/)'.TEXY_LINK.'??()#Uus',
			'phrase/em'
		);

		// *emphasisAlt*
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\*)\*(?![\s*])(\S+)'.TEXY_MODIFIER.'?(?<![\s*])\*(?!\*)'.TEXY_LINK.'??()#Uus',
			'phrase/em-alt'
		);

		// *emphasisAlt2*
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<![^\s.,;:<>()"\''.TEXY_MARK.'-])\*(?![\s*])(.+)'.TEXY_MODIFIER.'?(?<![\s*])\*(?![^\s.,;:<>()"?!\'-])'.TEXY_LINK.'??()#Uus',
			'phrase/em-alt2'
		);

		// ++inserted++
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\+)\+\+(?![\s+])([^\r\n]+)'.TEXY_MODIFIER.'?(?<![\s+])\+\+(?!\+)()#Uu',
			'phrase/ins'
		);

		// --deleted--
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<![<-])\-\-(?![\s>-])([^\r\n]+)'.TEXY_MODIFIER.'?(?<![\s<-])\-\-(?![>-])()#Uu',
			'phrase/del'
		);

		// ^^superscript^^
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\^)\^\^(?![\s^])([^\r\n]+)'.TEXY_MODIFIER.'?(?<![\s^])\^\^(?!\^)()#Uu',
			'phrase/sup'
		);

		// m^2 alternative superscript
		$texy->registerLinePattern(
			array($this, 'patternSupSub'),
			'#(?<=[a-z0-9])\^([n0-9+-]{1,4}?)(?![a-z0-9])#Uui',
			'phrase/sup-alt'
		);

		// __subscript__
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\_)\_\_(?![\s_])([^\r\n]+)'.TEXY_MODIFIER.'?(?<![\s_])\_\_(?!\_)()#Uu',
			'phrase/sub'
		);

		// m_2 alternative subscript
		$texy->registerLinePattern(
			array($this, 'patternSupSub'),
			'#(?<=[a-z])\_([n0-9]{1,3})(?![a-z0-9])#Uui',
			'phrase/sub-alt'
		);

		// "span"
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\")\"(?!\s)([^\"\r]+)'.TEXY_MODIFIER.'?(?<!\s)\"(?!\")'.TEXY_LINK.'??()#Uu',
			'phrase/span'
		);

		// ~alternative span~
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\~)\~(?!\s)([^\~\r]+)'.TEXY_MODIFIER.'?(?<!\s)\~(?!\~)'.TEXY_LINK.'??()#Uu',
			'phrase/span-alt'
		);

		// ~~cite~~
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\~)\~\~(?![\s~])([^\r\n]+)'.TEXY_MODIFIER.'?(?<![\s~])\~\~(?!\~)'.TEXY_LINK.'??()#Uu',
			'phrase/cite'
		);

		// >>quote<<
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\>)\>\>(?![\s>])([^\r\n]+)'.TEXY_MODIFIER.'?(?<![\s<])\<\<(?!\<)'.TEXY_LINK.'??()#Uu',
			'phrase/quote'
		);

		// acronym/abbr "et al."((and others))
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!\")\"(?!\s)([^\"\r\n]+)'.TEXY_MODIFIER.'?(?<!\s)\"(?!\")\(\((.+)\)\)()#Uu',
			'phrase/acronym'
		);

		// acronym/abbr NATO((North Atlantic Treaty Organisation))
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(?<!['.TEXY_CHAR.'])(['.TEXY_CHAR.']{2,})()\(\((.+)\)\)#Uu',
			'phrase/acronym-alt'
		);

		// ''notexy''
		$texy->registerLinePattern(
			array($this, 'patternNoTexy'),
			'#(?<!\')\'\'(?![\s\'])([^'.TEXY_MARK.'\r\n]*)(?<![\s\'])\'\'(?!\')()#Uu',
			'phrase/notexy'
		);

		// `code`
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#\`(\S[^'.TEXY_MARK.'\r\n]*)'.TEXY_MODIFIER.'?(?<!\s)\`'.TEXY_LINK.'??()#Uu',
			'phrase/code'
		);


		// ....:LINK
		$texy->registerLinePattern(
			array($this, 'patternPhrase'),
			'#(['.TEXY_CHAR.'0-9@\#$%&.,_-]+)()(?=:\[)'.TEXY_LINK.'()#Uu',
			'phrase/quicklink'
		);


		$texy->allowed['phrase/ins'] = FALSE;
		$texy->allowed['phrase/del'] = FALSE;
		$texy->allowed['phrase/sup'] = FALSE;
		$texy->allowed['phrase/sub'] = FALSE;
		$texy->allowed['phrase/cite'] = FALSE;
	}



	/**
	 * Callback for: **.... .(title)[class]{style}**:LINK.
	 *
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function patternPhrase($parser, $matches, $phrase)
	{
		list(, $mContent, $mMod, $mLink) = $matches;
		//    [1] => **
		//    [2] => ...
		//    [3] => .(title)[class]{style}
		//    [4] => LINK

		$tx = $this->texy;
		$mod = new TexyModifier($mMod);
		$link = NULL;

		$parser->again = $phrase !== 'phrase/code' && $phrase !== 'phrase/quicklink';

		if ($phrase === 'phrase/span' || $phrase === 'phrase/span-alt') {
			if ($mLink == NULL) {
				if (!$mMod) return FALSE; // means "..."
			} else {
				$link = $tx->linkModule->factoryLink($mLink, $mMod, $mContent);
			}

		} elseif ($phrase === 'phrase/acronym' || $phrase === 'phrase/acronym-alt') {
			$mod->title = trim(Texy::unescapeHtml($mLink));

		} elseif ($phrase === 'phrase/quote') {
			$mod->cite = $tx->blockQuoteModule->citeLink($mLink);

		} elseif ($mLink != NULL) {
			$link = $tx->linkModule->factoryLink($mLink, NULL, $mContent);
		}

		return $tx->invokeAroundHandlers('phrase', $parser, array($phrase, $mContent, $mod, $link));
	}



	/**
	 * Callback for: any^2  any_2.
	 *
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function patternSupSub($parser, $matches, $phrase)
	{
		list(, $mContent) = $matches;
		$mod = new TexyModifier();
		$link = NULL;
		$mContent = str_replace('-', "\xE2\x88\x92", $mContent); // &minus;
		return $this->texy->invokeAroundHandlers('phrase', $parser, array($phrase, $mContent, $mod, $link));
	}



	/**
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return string
	 */
	public function patternNoTexy($parser, $matches)
	{
		list(, $mContent) = $matches;
		return $this->texy->protect(Texy::escapeHtml($mContent), Texy::CONTENT_TEXTUAL);
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  string
	 * @param  string
	 * @param  TexyModifier
	 * @param  TexyLink
	 * @return TexyHtml
	 */
	public function solve($invocation, $phrase, $content, $mod, $link)
	{
		$tx = $this->texy;

		$tag = isset($this->tags[$phrase]) ? $this->tags[$phrase] : NULL;

		if ($tag === 'a')
			$tag = $link && $this->linksAllowed ? NULL : 'span';

		if ($phrase === 'phrase/code')
			$content = $tx->protect(Texy::escapeHtml($content), Texy::CONTENT_TEXTUAL);

		if ($phrase === 'phrase/strong+em') {
			$el = TexyHtml::el($this->tags['phrase/strong']);
			$el->create($this->tags['phrase/em'], $content);
			$mod->decorate($tx, $el);

		} elseif ($tag) {
			$el = TexyHtml::el($tag)->setText($content);
			$mod->decorate($tx, $el);

			if ($tag === 'q') $el->attrs['cite'] = $mod->cite;
		} else {
			$el = $content; // trick
		}

		if ($link && $this->linksAllowed) return $tx->linkModule->solve(NULL, $link, $el);

		return $el;
	}

}
