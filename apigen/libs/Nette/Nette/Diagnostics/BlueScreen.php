<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * Red BlueScreen.
 *
 * @author     David Grudl
 */
class BlueScreen extends Nette\Object
{
	/** @var array */
	private $panels = array();



	/**
	 * Add custom panel.
	 * @param  callable
	 * @return BlueScreen  provides a fluent interface
	 */
	public function addPanel($panel)
	{
		if (!in_array($panel, $this->panels, TRUE)) {
			$this->panels[] = $panel;
		}
		return $this;
	}



	/**
	 * Renders blue screen.
	 * @param  \Exception
	 * @return void
	 */
	public function render(\Exception $exception)
	{
		$panels = $this->panels;
		require __DIR__ . '/templates/bluescreen.phtml';
	}



	/**
	 * Returns syntax highlighted source code.
	 * @param  string
	 * @param  int
	 * @param  int
	 * @return string
	 */
	public static function highlightFile($file, $line, $lines = 15, $vars = array())
	{
		$source = @file_get_contents($file); // intentionally @
		if ($source) {
			return static::highlightPhp($source, $line, $lines, $vars);
		}
	}



	/**
	 * Returns syntax highlighted source code.
	 * @param  string
	 * @param  int
	 * @param  int
	 * @return string
	 */
	public static function highlightPhp($source, $line, $lines = 15, $vars = array())
	{
		if (function_exists('ini_set')) {
			ini_set('highlight.comment', '#998; font-style: italic');
			ini_set('highlight.default', '#000');
			ini_set('highlight.html', '#06B');
			ini_set('highlight.keyword', '#D24; font-weight: bold');
			ini_set('highlight.string', '#080');
		}

		$source = str_replace(array("\r\n", "\r"), "\n", $source);
		$source = explode("\n", highlight_string($source, TRUE));
		$spans = 1;
		$out = $source[0]; // <code><span color=highlight.html>
		$source = explode('<br />', $source[1]);
		array_unshift($source, NULL);

		$start = $i = max(1, $line - floor($lines * 2/3));
		while (--$i >= 1) { // find last highlighted block
			if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
				if ($m[1] !== '</span>') {
					$spans++; $out .= $m[1];
				}
				break;
			}
		}

		$source = array_slice($source, $start, $lines, TRUE);
		end($source);
		$numWidth = strlen((string) key($source));

		foreach ($source as $n => $s) {
			$spans += substr_count($s, '<span') - substr_count($s, '</span');
			$s = str_replace(array("\r", "\n"), array('', ''), $s);
			preg_match_all('#<[^>]+>#', $s, $tags);
			if ($n == $line) {
				$out .= sprintf(
					"<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
					$n,
					strip_tags($s),
					implode('', $tags[0])
				);
			} else {
				$out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
			}
		}
		$out .= str_repeat('</span>', $spans) . '</code>';

		$out = preg_replace_callback('#">\$(\w+)(&nbsp;)?</span>#', function($m) use ($vars) {
			return isset($vars[$m[1]])
				? '" title="' . str_replace('"', '&quot;', strip_tags(Helpers::htmlDump($vars[$m[1]]))) . $m[0]
				: $m[0];
		}, $out);

		return "<pre><div>$out</div></pre>";
	}

}
