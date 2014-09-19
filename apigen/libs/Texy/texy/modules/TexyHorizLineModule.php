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
 * Horizontal line module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyHorizLineModule extends TexyModule
{
	/** @var array  default CSS class */
	public $classes = array(
		'-' => NULL,
		'*' => NULL,
	);



	public function __construct($texy)
	{
		$this->texy = $texy;

		$texy->addHandler('horizline', array($this, 'solve'));

		$texy->registerBlockPattern(
			array($this, 'pattern'),
			'#^(\*{3,}|-{3,})\ *'.TEXY_MODIFIER.'?()$#mU',
			'horizline'
		);
	}



	/**
	 * Callback for: -------.
	 *
	 * @param  TexyBlockParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml
	 */
	public function pattern($parser, $matches)
	{
		list(, $mType, $mMod) = $matches;
		//    [1] => ---
		//    [2] => .(title)[class]{style}<>

		$mod = new TexyModifier($mMod);
		return $this->texy->invokeAroundHandlers('horizline', $parser, array($mType, $mod));
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  string
	 * @param  TexyModifier
	 * @return TexyHtml
	 */
	public function solve($invocation, $type, $modifier)
	{
		$el = TexyHtml::el('hr');
		$modifier->decorate($invocation->texy, $el);

		$class = $this->classes[ $type[0] ];
		if ($class && !isset($modifier->classes[$class])) {
			$el->attrs['class'][] = $class;
		}

		return $el;
	}

}
