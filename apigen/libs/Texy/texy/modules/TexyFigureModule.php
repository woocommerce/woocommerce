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
 * The captioned figures.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyFigureModule extends TexyModule
{
	/** @var string  non-floated box CSS class */
	public $class = 'figure';

	/** @var string  left-floated box CSS class */
	public $leftClass;

	/** @var string  right-floated box CSS class */
	public $rightClass;

	/** @var int  how calculate div's width */
	public $widthDelta = 10;


	public function __construct($texy)
	{
		$this->texy = $texy;

		$texy->addHandler('figure', array($this, 'solve'));

		$texy->registerBlockPattern(
			array($this, 'pattern'),
			'#^'.TEXY_IMAGE.TEXY_LINK_N.'?? +\*\*\* +(.*)'.TEXY_MODIFIER_H.'?()$#mUu',
			'figure'
		);
	}



	/**
	 * Callback for [*image*]:link *** .... .(title)[class]{style}>.
	 *
	 * @param  TexyBlockParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function pattern($parser, $matches)
	{
		list(, $mURLs, $mImgMod, $mAlign, $mLink, $mContent, $mMod) = $matches;
		//    [1] => URLs
		//    [2] => .(title)[class]{style}<>
		//    [3] => * < >
		//    [4] => url | [ref] | [*image*]
		//    [5] => ...
		//    [6] => .(title)[class]{style}<>

		$tx = $this->texy;
		$image = $tx->imageModule->factoryImage($mURLs, $mImgMod.$mAlign);
		$mod = new TexyModifier($mMod);
		$mContent = ltrim($mContent);

		if ($mLink) {
			if ($mLink === ':') {
				$link = new TexyLink($image->linkedURL === NULL ? $image->URL : $image->linkedURL);
				$link->raw = ':';
				$link->type = TexyLink::IMAGE;
			} else {
				$link = $tx->linkModule->factoryLink($mLink, NULL, NULL);
			}
		} else $link = NULL;

		return $tx->invokeAroundHandlers('figure', $parser, array($image, $link, $mContent, $mod));
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  TexyImage
	 * @param  TexyLink
	 * @param  string
	 * @param  TexyModifier
	 * @return TexyHtml|FALSE
	 */
	public function solve($invocation, TexyImage $image, $link, $content, $mod)
	{
		$tx = $this->texy;

		$hAlign = $image->modifier->hAlign;
		$image->modifier->hAlign = NULL;

		$elImg = $tx->imageModule->solve(NULL, $image, $link); // returns TexyHtml or false!
		if (!$elImg) return FALSE;

		$el = TexyHtml::el('div');
		if (!empty($image->width) && $this->widthDelta !== FALSE) {
			$el->attrs['style']['width'] = ($image->width + $this->widthDelta) . 'px';
		}
		$mod->decorate($tx, $el);

		$el[0] = $elImg;
		$el[1] = TexyHtml::el('p');
		$el[1]->parseLine($tx, ltrim($content));

		$class = $this->class;
		if ($hAlign) {
			$var = $hAlign . 'Class'; // leftClass, rightClass
			if (!empty($this->$var)) {
				$class = $this->$var;

			} elseif (empty($tx->alignClasses[$hAlign])) {
				$el->attrs['style']['float'] = $hAlign;

			} else {
				$class .= '-' . $tx->alignClasses[$hAlign];
			}
		}
		$el->attrs['class'][] = $class;

		return $el;
	}

}
