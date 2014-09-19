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
 * Emoticon module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyEmoticonModule extends TexyModule
{
	/** @var array  supported emoticons and image files */
	public $icons = array(
		':-)' => 'smile.gif',
		':-(' => 'sad.gif',
		';-)' => 'wink.gif',
		':-D' => 'biggrin.gif',
		'8-O' => 'eek.gif',
		'8-)' => 'cool.gif',
		':-?' => 'confused.gif',
		':-x' => 'mad.gif',
		':-P' => 'razz.gif',
		':-|' => 'neutral.gif',
	);

	/** @var string  CSS class for emoticons */
	public $class;

	/** @var string  root of relative images (default value is $texy->imageModule->root) */
	public $root;

	/** @var string  physical location of images on server (default value is $texy->imageModule->fileRoot) */
	public $fileRoot;



	public function __construct($texy)
	{
		$this->texy = $texy;
		$texy->allowed['emoticon'] = FALSE;
		$texy->addHandler('emoticon', array($this, 'solve'));
		$texy->addHandler('beforeParse', array($this, 'beforeParse'));
	}



	public function beforeParse()
	{
		if (empty($this->texy->allowed['emoticon'])) return;

		krsort($this->icons);

		$pattern = array();
		foreach ($this->icons as $key => $foo)
			$pattern[] = preg_quote($key, '#') . '+'; // last char can be repeated

		$this->texy->registerLinePattern(
			array($this, 'pattern'),
			'#(?<=^|[\\x00-\\x20])(' . implode('|', $pattern) . ')#',
			'emoticon',
			'#' . implode('|', $pattern) . '#'
		);
	}



	/**
	 * Callback for: :-))).
	 *
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function pattern($parser, $matches)
	{
		$match = $matches[0];

		$tx = $this->texy;

		// find the closest match
		foreach ($this->icons as $emoticon => $foo)
		{
			if (strncmp($match, $emoticon, strlen($emoticon)) === 0)
			{
				return $tx->invokeAroundHandlers('emoticon', $parser, array($emoticon, $match));
			}
		}

		return FALSE; // tohle se nestane
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  string
	 * @param  string
	 * @return TexyHtml|FALSE
	 */
	public function solve($invocation, $emoticon, $raw)
	{
		$tx = $this->texy;
		$file = $this->icons[$emoticon];
		$el = TexyHtml::el('img');
		$el->attrs['src'] = Texy::prependRoot($file, $this->root === NULL ?  $tx->imageModule->root : $this->root);
		$el->attrs['alt'] = $raw;
		$el->attrs['class'][] = $this->class;

		// file path
		$file = rtrim($this->fileRoot === NULL ?  $tx->imageModule->fileRoot : $this->fileRoot, '/\\') . '/' . $file;
		if (@is_file($file)) { // intentionally @
			$size = @getImageSize($file); // intentionally @
			if (is_array($size)) {
				$el->attrs['width'] = $size[0];
				$el->attrs['height'] = $size[1];
			}
		}
		$tx->summary['images'][] = $el->attrs['src'];
		return $el;
	}

}
