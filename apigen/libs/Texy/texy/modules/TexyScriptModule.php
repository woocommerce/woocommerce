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
 * Scripts module.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
final class TexyScriptModule extends TexyModule
{
	/**
	 * @var callback|object  script elements handler
	 * function myFunc($parser, $cmd, $args, $raw)
	 */
	public $handler;


	/** @var string  arguments separator */
	public $separator = ',';



	public function __construct($texy)
	{
		$this->texy = $texy;

		$texy->addHandler('script', array($this, 'solve'));

		$texy->registerLinePattern(
			array($this, 'pattern'),
			'#\{\{([^'.TEXY_MARK.']+)\}\}()#U',
			'script'
		);
	}



	/**
	 * Callback for: {{...}}.
	 *
	 * @param  TexyLineParser
	 * @param  array      regexp matches
	 * @param  string     pattern name
	 * @return TexyHtml|string|FALSE
	 */
	public function pattern($parser, $matches)
	{
		list(, $mContent) = $matches;
		//    [1] => ...

		$cmd = trim($mContent);
		if ($cmd === '') return FALSE;

		$args = $raw = NULL;
		// function(arg, arg, ...)  or  function: arg, arg
		if (preg_match('#^([a-z_][a-z0-9_-]*)\s*(?:\(([^()]*)\)|:(.*))$#iu', $cmd, $matches)) {
			$cmd = $matches[1];
			$raw = isset($matches[3]) ? trim($matches[3]) : trim($matches[2]);
			if ($raw === '')
				$args = array();
			else
				$args = preg_split('#\s*' . preg_quote($this->separator, '#') . '\s*#u', $raw);
		}

		// Texy 1.x way
		if ($this->handler) {
			if (is_callable(array($this->handler, $cmd))) {
				array_unshift($args, $parser);
				return call_user_func_array(array($this->handler, $cmd), $args);
			}

			if (is_callable($this->handler))
				return call_user_func_array($this->handler, array($parser, $cmd, $args, $raw));
		}

		// Texy 2 way
		return $this->texy->invokeAroundHandlers('script', $parser, array($cmd, $args, $raw));
	}



	/**
	 * Finish invocation.
	 *
	 * @param  TexyHandlerInvocation  handler invocation
	 * @param  string  command
	 * @param  array   arguments
	 * @param  string  arguments in raw format
	 * @return TexyHtml|string|FALSE
	 */
	public function solve($invocation, $cmd, $args, $raw)
	{
		if ($cmd === 'texy') {
			if (!$args) return FALSE;

			switch ($args[0]) {
			case 'nofollow':
				$this->texy->linkModule->forceNoFollow = TRUE;
				break;
			}
			return '';

		} else {
			return FALSE;
		}
	}

}
