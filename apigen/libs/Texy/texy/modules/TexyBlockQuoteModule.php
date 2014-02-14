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
 * Blockquote module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyBlockQuoteModule extends TexyModule
{

	public function __construct($texy)
	{
		$this->texy = $texy;

		$texy->registerBlockPattern(
			array($this, 'pattern'),
			'#^(?:'.TEXY_MODIFIER_H.'\n)?\>(\ +|:)(\S.*)$#mU', // original
//            '#^(?:'.TEXY_MODIFIER_H.'\n)?\>(?:(\>|\ +?|:)(.*))?()$#mU',  // >>>>
//            '#^(?:'.TEXY_MODIFIER_H.'\n)?\>(?:(\ +?|:)(.*))()$#mU',       // only >
			'blockquote'
		);
	}



	/**
	 * Callback for:.
	 *
	 *   > They went in single file, running like hounds on a strong scent,
	 *   and an eager light was in their eyes. Nearly due west the broad
	 *   swath of the marching Orcs tramped its ugly slot; the sweet grass
	 *   of Rohan had been bruised and blackened as they passed.
	 *   >:http://www.mycom.com/tolkien/twotowers.html
	 *
	 * @param  TexyBlockParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function pattern($parser, $matches)
	{
		list(, $mMod, $mPrefix, $mContent) = $matches;
		//    [1] => .(title)[class]{style}<>
		//    [2] => spaces |
		//    [3] => ... / LINK

		$tx = $this->texy;

		$el = TexyHtml::el('blockquote');
		$mod = new TexyModifier($mMod);
		$mod->decorate($tx, $el);

		$content = '';
		$spaces = '';
		do {
			if ($mPrefix === ':') {
				$mod->cite = $tx->blockQuoteModule->citeLink($mContent);
				$content .= "\n";
			} else {
				if ($spaces === '') $spaces = max(1, strlen($mPrefix));
				$content .= $mContent . "\n";
			}

			if (!$parser->next("#^>(?:|(\\ {1,$spaces}|:)(.*))()$#mA", $matches)) break;

/*
			if ($mPrefix === '>') {
				$content .= $mPrefix . $mContent . "\n";
			} elseif ($mPrefix === ':') {
				$mod->cite = $tx->blockQuoteModule->citeLink($mContent);
				$content .= "\n";
			} else {
				if ($spaces === '') $spaces = max(1, strlen($mPrefix));
				$content .= $mContent . "\n";
			}
			if (!$parser->next("#^\\>(?:(\\>|\\ {1,$spaces}|:)(.*))?()$#mA", $matches)) break;
*/

			list(, $mPrefix, $mContent) = $matches;
		} while (TRUE);

		$el->attrs['cite'] = $mod->cite;
		$el->parseBlock($tx, $content, $parser->isIndented());

		// no content?
		if (!$el->count()) return FALSE;

		// event listener
		$tx->invokeHandlers('afterBlockquote', array($parser, $el, $mod));

		return $el;
	}



	/**
	 * Converts cite source to URL.
	 * @param  string
	 * @return string|NULL
	 */
	public function citeLink($link)
	{
		$tx = $this->texy;

		if ($link == NULL) return NULL;

		if ($link{0} === '[') { // [ref]
			$link = substr($link, 1, -1);
			$ref = $tx->linkModule->getReference($link);
			if ($ref) return Texy::prependRoot($ref->URL, $tx->linkModule->root);
		}

		// special supported case
		if (strncasecmp($link, 'www.', 4) === 0) return 'http://' . $link;

		return Texy::prependRoot($link, $tx->linkModule->root);
	}

}
