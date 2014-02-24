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
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyHtmlOutputModule extends TexyModule
{
	/** @var bool  indent HTML code? */
	public $indent = TRUE;

	/** @var int  base indent level */
	public $baseIndent = 0;

	/** @var int  wrap width, doesn't include indent space */
	public $lineWrap = 80;

	/** @var bool  remove optional HTML end tags? */
	public $removeOptional = FALSE;

	/** @var int  indent space counter */
	private $space;

	/** @var array */
	private $tagUsed;

	/** @var array */
	private $tagStack;

	/** @var array  content DTD used, when context is not defined */
	private $baseDTD;

	/** @var bool */
	private $xml;



	public function __construct($texy)
	{
		$this->texy = $texy;
		$texy->addHandler('postProcess', array($this, 'postProcess'));
	}



	/**
	 * Converts <strong><em> ... </strong> ... </em>.
	 * into <strong><em> ... </em></strong><em> ... </em>
	 */
	public function postProcess($texy, & $s)
	{
		$this->space = $this->baseIndent;
		$this->tagStack = array();
		$this->tagUsed  = array();
		$this->xml = $texy->getOutputMode() & Texy::XML;

		// special "base content"
		$this->baseDTD = $texy->dtd['div'][1] + $texy->dtd['html'][1] /*+ $texy->dtd['head'][1]*/ + $texy->dtd['body'][1] + array('html'=>1);

		// wellform and reformat
		$s = preg_replace_callback(
			'#(.*)<(?:(!--.*--)|(/?)([a-z][a-z0-9._:-]*)(|[ \n].*)\s*(/?))>()#Uis',
			array($this, 'cb'),
			$s . '</end/>'
		);

		// empty out stack
		foreach ($this->tagStack as $item) $s .= $item['close'];

		// right trim
		$s = preg_replace("#[\t ]+(\n|\r|$)#", '$1', $s); // right trim

		// join double \r to single \n
		$s = str_replace("\r\r", "\n", $s);
		$s = strtr($s, "\r", "\n");

		// greedy chars
		$s = preg_replace("#\\x07 *#", '', $s);
		// back-tabs
		$s = preg_replace("#\\t? *\\x08#", '', $s);

		// line wrap
		if ($this->lineWrap > 0) {
			$s = preg_replace_callback(
				'#^(\t*)(.*)$#m',
				array($this, 'wrap'),
				$s
			);
		}

		// remove HTML 4.01 optional end tags
		if (!$this->xml && $this->removeOptional) {
			$s = preg_replace('#\\s*</(colgroup|dd|dt|li|option|p|td|tfoot|th|thead|tr)>#u', '', $s);
		}
	}



	/**
	 * Callback function: <tag> | </tag> | ....
	 * @return string
	 */
	private function cb($matches)
	{
		// html tag
		list(, $mText, $mComment, $mEnd, $mTag, $mAttr, $mEmpty) = $matches;
		//    [1] => text
		//    [1] => !-- comment --
		//    [2] => /
		//    [3] => TAG
		//    [4] => ... (attributes)
		//    [5] => /   (empty)

		$s = '';

		// phase #1 - stuff between tags
		if ($mText !== '') {
			$item = reset($this->tagStack);
			// text not allowed?
			if ($item && !isset($item['dtdContent']['%DATA'])) { }

			// inside pre & textarea preserve spaces
			elseif (!empty($this->tagUsed['pre']) || !empty($this->tagUsed['textarea']) || !empty($this->tagUsed['script']))
				$s = Texy::freezeSpaces($mText);

			// otherwise shrink multiple spaces
			else $s = preg_replace('#[ \n]+#', ' ', $mText);
		}


		// phase #2 - HTML comment
		if ($mComment) return $s . '<' . Texy::freezeSpaces($mComment) . '>';


		// phase #3 - HTML tag
		$mEmpty = $mEmpty || isset(TexyHtml::$emptyElements[$mTag]);
		if ($mEmpty && $mEnd) return $s; // bad tag; /end/


		if ($mEnd) {  // end tag

			// has start tag?
			if (empty($this->tagUsed[$mTag])) return $s;

			// autoclose tags
			$tmp = array();
			$back = TRUE;
			foreach ($this->tagStack as $i => $item)
			{
				$tag = $item['tag'];
				$s .= $item['close'];
				$this->space -= $item['indent'];
				$this->tagUsed[$tag]--;
				$back = $back && isset(TexyHtml::$inlineElements[$tag]);
				unset($this->tagStack[$i]);
				if ($tag === $mTag) break;
				array_unshift($tmp, $item);
			}

			if (!$back || !$tmp) return $s;

			// allowed-check (nejspis neni ani potreba)
			$item = reset($this->tagStack);
			if ($item) $dtdContent = $item['dtdContent'];
			else $dtdContent = $this->baseDTD;
			if (!isset($dtdContent[$tmp[0]['tag']])) return $s;

			// autoopen tags
			foreach ($tmp as $item)
			{
				$s .= $item['open'];
				$this->space += $item['indent'];
				$this->tagUsed[$item['tag']]++;
				array_unshift($this->tagStack, $item);
			}


		} else { // start tag

			$dtdContent = $this->baseDTD;

			if (!isset($this->texy->dtd[$mTag])) {
				// unknown (non-html) tag
				$allowed = TRUE;
				$item = reset($this->tagStack);
				if ($item) $dtdContent = $item['dtdContent'];


			} else {
				// optional end tag closing
				foreach ($this->tagStack as $i => $item)
				{
					// is tag allowed here?
					$dtdContent = $item['dtdContent'];
					if (isset($dtdContent[$mTag])) break;

					$tag = $item['tag'];

					// auto-close hidden, optional and inline tags
					if ($item['close'] && (!isset(TexyHtml::$optionalEnds[$tag]) && !isset(TexyHtml::$inlineElements[$tag]))) break;

					// close it
					$s .= $item['close'];
					$this->space -= $item['indent'];
					$this->tagUsed[$tag]--;
					unset($this->tagStack[$i]);
					$dtdContent = $this->baseDTD;
				}

				// is tag allowed in this content?
				$allowed = isset($dtdContent[$mTag]);

				// check deep element prohibitions
				if ($allowed && isset(TexyHtml::$prohibits[$mTag])) {
					foreach (TexyHtml::$prohibits[$mTag] as $pTag)
						if (!empty($this->tagUsed[$pTag])) { $allowed = FALSE; break; }
				}
			}

			// empty elements se neukladaji do zasobniku
			if ($mEmpty) {
				if (!$allowed) return $s;

				if ($this->xml) $mAttr .= " /";

				$indent = $this->indent && empty($this->tagUsed['pre']) && empty($this->tagUsed['textarea']);

				if ($indent && $mTag === 'br')
					// formatting exception
					return rtrim($s) .  '<' . $mTag . $mAttr . ">\n" . str_repeat("\t", max(0, $this->space - 1)) . "\x07";

				if ($indent && !isset(TexyHtml::$inlineElements[$mTag])) {
					$space = "\r" . str_repeat("\t", $this->space);
					return $s . $space . '<' . $mTag . $mAttr . '>' . $space;
				}

				return $s . '<' . $mTag . $mAttr . '>';
			}

			$open = NULL;
			$close = NULL;
			$indent = 0;

			/*
			if (!isset(TexyHtml::$inlineElements[$mTag])) {
				// block tags always decorate with \n
				$s .= "\n";
				$close = "\n";
			}
			*/

			if ($allowed) {
				$open = '<' . $mTag . $mAttr . '>';

				// receive new content (ins & del are special cases)
				if (!empty($this->texy->dtd[$mTag][1])) $dtdContent = $this->texy->dtd[$mTag][1];

				// format output
				if ($this->indent && !isset(TexyHtml::$inlineElements[$mTag])) {
					$close = "\x08" . '</'.$mTag.'>' . "\n" . str_repeat("\t", $this->space);
					$s .= "\n" . str_repeat("\t", $this->space++) . $open . "\x07";
					$indent = 1;
				} else {
					$close = '</'.$mTag.'>';
					$s .= $open;
				}

				// TODO: problematic formatting of select / options, object / params
			}


			// open tag, put to stack, increase counter
			$item = array(
				'tag' => $mTag,
				'open' => $open,
				'close' => $close,
				'dtdContent' => $dtdContent,
				'indent' => $indent,
			);
			array_unshift($this->tagStack, $item);
			$tmp = &$this->tagUsed[$mTag]; $tmp++;
		}

		return $s;
	}



	/**
	 * Callback function: wrap lines.
	 * @return string
	 */
	private function wrap($m)
	{
		list(, $space, $s) = $m;
		return $space . wordwrap($s, $this->lineWrap, "\n" . $space);
	}

}
