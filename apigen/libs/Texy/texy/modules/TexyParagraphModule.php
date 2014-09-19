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
 * Paragraph module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyParagraphModule extends TexyModule
{


	public function __construct($texy)
	{
		$this->texy = $texy;
		$texy->addHandler('paragraph', array($this, 'solve'));
	}



	/**
	 * @param  TexyBlockParser
	 * @param  string     text
	 * @param  array
	 * @param  TexyHtml
	 * @return vois
	 */
	public function process($parser, $content, $el)
	{
		$tx = $this->texy;

		if ($parser->isIndented()) {
			$parts = preg_split('#(\n(?! )|\n{2,})#', $content, -1, PREG_SPLIT_NO_EMPTY);
		} else {
			$parts = preg_split('#(\n{2,})#', $content, -1, PREG_SPLIT_NO_EMPTY);
		}

		foreach ($parts as $s)
		{
			$s = trim($s);
			if ($s === '') continue;

			// try to find modifier
			$mx = $mod = NULL;
			if (preg_match('#\A(.*)(?<=\A|\S)'.TEXY_MODIFIER_H.'(\n.*)?()\z#sUm', $s, $mx)) {
				list(, $mC1, $mMod, $mC2) = $mx;
				$s = trim($mC1 . $mC2);
				if ($s === '') continue;
				$mod = new TexyModifier;
				$mod->setProperties($mMod);
			}

			$res = $tx->invokeAroundHandlers('paragraph', $parser, array($s, $mod));
			if ($res) $el->insert(NULL, $res);
		}
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  string
	 * @param  TexyModifier|NULL
	 * @return TexyHtml|FALSE
	 */
	public function solve($invocation, $content, $mod)
	{
		$tx = $this->texy;

		// find hard linebreaks
		if ($tx->mergeLines) {
			// ....
			//  ...  => \r means break line
			$content = preg_replace('#\n +(?=\S)#', "\r", $content);
		} else {
			$content = preg_replace('#\n#', "\r", $content);
		}

		$el = TexyHtml::el('p');
		$el->parseLine($tx, $content);
		$content = $el->getText(); // string

		// check content type
		// block contains block tag
		if (strpos($content, Texy::CONTENT_BLOCK) !== FALSE) {
			$el->setName(NULL);  // ignores modifier!

		// block contains text (protected)
		} elseif (strpos($content, Texy::CONTENT_TEXTUAL) !== FALSE) {
			// leave element p

		// block contains text
		} elseif (preg_match('#[^\s'.TEXY_MARK.']#u', $content)) {
			// leave element p

		// block contains only replaced element
		} elseif (strpos($content, Texy::CONTENT_REPLACED) !== FALSE) {
			$el->setName($tx->nontextParagraph);

		// block contains only markup tags or spaces or nothing
		} else {
			// if {ignoreEmptyStuff} return FALSE;
			if (!$mod) $el->setName(NULL);
		}

		if ($el->getName()) {
			// apply modifier
			if ($mod) $mod->decorate($tx, $el);

			// add <br />
			if (strpos($content, "\r") !== FALSE) {
				$key = $tx->protect('<br />', Texy::CONTENT_REPLACED);
				$content = str_replace("\r", $key, $content);
			};
		}

		$content = strtr($content, "\r\n", '  ');
		$el->setText($content);

		return $el;
	}

}
