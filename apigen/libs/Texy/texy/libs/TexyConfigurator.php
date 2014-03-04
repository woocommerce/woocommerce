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
 * Texy basic configurators.
 *
 * <code>
 *     $texy = new Texy();
 *     TexyConfigurator::safeMode($texy);
 * </code>
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
class TexyConfigurator
{
	public static $safeTags = array(
		'a'         => array('href', 'title'),
		'acronym'   => array('title'),
		'b'         => array(),
		'br'        => array(),
		'cite'      => array(),
		'code'      => array(),
		'em'        => array(),
		'i'         => array(),
		'strong'    => array(),
		'sub'       => array(),
		'sup'       => array(),
		'q'         => array(),
		'small'     => array(),
	);



	/**
	 * static class.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}



	/**
	 * Configure Texy! for web comments and other usages, where input text may insert attacker.
	 *
	 * @param  Texy  object to configure
	 * @return void
	 */
	public static function safeMode(Texy $texy)
	{
		$texy->allowedClasses = Texy::NONE;                 // no class or ID are allowed
		$texy->allowedStyles  = Texy::NONE;                 // style modifiers are disabled
		$texy->allowedTags = self::$safeTags;               // only some "safe" HTML tags and attributes are allowed
		$texy->urlSchemeFilters[Texy::FILTER_ANCHOR] = '#https?:|ftp:|mailto:#A';
		$texy->urlSchemeFilters[Texy::FILTER_IMAGE] = '#https?:#A';
		$texy->allowed['image'] = FALSE;                    // disable images
		$texy->allowed['link/definition'] = FALSE;          // disable [ref]: URL  reference definitions
		$texy->allowed['html/comment'] = FALSE;             // disable HTML comments
		$texy->linkModule->forceNoFollow = TRUE;            // force rel="nofollow"
	}



	/**
	 * Disable all links.
	 *
	 * @param  Texy  object to configure
	 * @return void
	 */
	public static function disableLinks(Texy $texy)
	{
		$texy->allowed['link/reference'] = FALSE;
		$texy->allowed['link/email'] = FALSE;
		$texy->allowed['link/url'] = FALSE;
		$texy->allowed['link/definition'] = FALSE;
		$texy->phraseModule->linksAllowed = FALSE;

		if (is_array($texy->allowedTags)) {
			unset($texy->allowedTags['a']);
		} // TODO: else...
	}



	/**
	 * Disable all images.
	 *
	 * @param  Texy  object to configure
	 * @return void
	 */
	public static function disableImages(Texy $texy)
	{
		$texy->allowed['image'] = FALSE;
		$texy->allowed['figure'] = FALSE;
		$texy->allowed['image/definition'] = FALSE;

		if (is_array($texy->allowedTags)) {
			unset($texy->allowedTags['img'], $texy->allowedTags['object'], $texy->allowedTags['embed'], $texy->allowedTags['applet']);
		} // TODO: else...
	}

}
