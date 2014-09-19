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
 * Rendering helpers for Debugger.
 *
 * @author     David Grudl
 */
final class Helpers
{

	/**
	 * Returns link to editor.
	 * @return Nette\Utils\Html
	 */
	public static function editorLink($file, $line)
	{
		if (Debugger::$editor && is_file($file)) {
			$dir = dirname(strtr($file, '/', DIRECTORY_SEPARATOR));
			$base = isset($_SERVER['SCRIPT_FILENAME']) ? dirname(dirname(strtr($_SERVER['SCRIPT_FILENAME'], '/', DIRECTORY_SEPARATOR))) : dirname($dir);
			if (substr($dir, 0, strlen($base)) === $base) {
				$dir = '...' . substr($dir, strlen($base));
			}
			return Nette\Utils\Html::el('a')
				->href(strtr(Debugger::$editor, array('%file' => rawurlencode($file), '%line' => $line)))
				->title("$file:$line")
				->setHtml(htmlSpecialChars(rtrim($dir, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR . '<b>' . htmlSpecialChars(basename($file)) . '</b>');
		} else {
			return Nette\Utils\Html::el('span')->setText($file);
		}
	}



	/**
	 * Internal dump() implementation.
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	public static function htmlDump(&$var, $level = 0)
	{
		static $tableUtf, $tableBin, $reBinary = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';
		if ($tableUtf === NULL) {
			foreach (range("\x00", "\xFF") as $ch) {
				if (ord($ch) < 32 && strpos("\r\n\t", $ch) === FALSE) {
					$tableUtf[$ch] = $tableBin[$ch] = '\\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				} elseif (ord($ch) < 127) {
					$tableUtf[$ch] = $tableBin[$ch] = $ch;
				} else {
					$tableUtf[$ch] = $ch; $tableBin[$ch] = '\\x' . dechex(ord($ch));
				}
			}
			$tableBin["\\"] = '\\\\';
			$tableBin["\r"] = '\\r';
			$tableBin["\n"] = '\\n';
			$tableBin["\t"] = '\\t';
			$tableUtf['\\x'] = $tableBin['\\x'] = '\\\\x';
		}

		if (is_bool($var)) {
			return '<span class="php-bool">' . ($var ? 'TRUE' : 'FALSE') . "</span>\n";

		} elseif ($var === NULL) {
			return "<span class=\"php-null\">NULL</span>\n";

		} elseif (is_int($var)) {
			return "<span class=\"php-int\">$var</span>\n";

		} elseif (is_float($var)) {
			$var = var_export($var, TRUE);
			if (strpos($var, '.') === FALSE) {
				$var .= '.0';
			}
			return "<span class=\"php-float\">$var</span>\n";

		} elseif (is_string($var)) {
			if (Debugger::$maxLen && strlen($var) > Debugger::$maxLen) {
				$s = htmlSpecialChars(substr($var, 0, Debugger::$maxLen), ENT_NOQUOTES, 'ISO-8859-1') . ' ... ';
			} else {
				$s = htmlSpecialChars($var, ENT_NOQUOTES, 'ISO-8859-1');
			}
			$s = strtr($s, preg_match($reBinary, $s) || preg_last_error() ? $tableBin : $tableUtf);
			$len = strlen($var);
			return "<span class=\"php-string\">\"$s\"</span>" . ($len > 1 ? " ($len)" : "") . "\n";

		} elseif (is_array($var)) {
			$s = '<span class="php-array">array</span>(' . count($var) . ") ";
			$space = str_repeat($space1 = '   ', $level);
			$brackets = range(0, count($var) - 1) === array_keys($var) ? "[]" : "{}";

			static $marker;
			if ($marker === NULL) {
				$marker = uniqid("\x00", TRUE);
			}
			if (empty($var)) {

			} elseif (isset($var[$marker])) {
				$brackets = $var[$marker];
				$s .= "$brackets[0] *RECURSION* $brackets[1]";

			} elseif ($level < Debugger::$maxDepth || !Debugger::$maxDepth) {
				$s .= "<code>$brackets[0]\n";
				$var[$marker] = $brackets;
				foreach ($var as $k => &$v) {
					if ($k === $marker) {
						continue;
					}
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$k = htmlSpecialChars(preg_match('#^\w+$#', $k) ? $k : "\"$k\"");
					$s .= "$space$space1<span class=\"php-key\">$k</span> => " . self::htmlDump($v, $level + 1);
				}
				unset($var[$marker]);
				$s .= "$space$brackets[1]</code>";

			} else {
				$s .= "$brackets[0] ... $brackets[1]";
			}
			return $s . "\n";

		} elseif (is_object($var)) {
			if ($var instanceof \Closure) {
				$rc = new \ReflectionFunction($var);
				$arr = array();
				foreach ($rc->getParameters() as $param) {
					$arr[] = '$' . $param->getName();
				}
				$arr = array('file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'parameters' => implode(', ', $arr));
			} else {
				$arr = (array) $var;
			}
			$s = '<span class="php-object">' . get_class($var) . "</span>(" . count($arr) . ") ";
			$space = str_repeat($space1 = '   ', $level);

			static $list = array();
			if (empty($arr)) {

			} elseif (in_array($var, $list, TRUE)) {
				$s .= "{ *RECURSION* }";

			} elseif ($level < Debugger::$maxDepth || !Debugger::$maxDepth || $var instanceof \Closure) {
				$s .= "<code>{\n";
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					$m = '';
					if ($k[0] === "\x00") {
						$m = ' <span class="php-visibility">' . ($k[1] === '*' ? 'protected' : 'private') . '</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$k = htmlSpecialChars(preg_match('#^\w+$#', $k) ? $k : "\"$k\"");
					$s .= "$space$space1<span class=\"php-key\">$k</span>$m => " . self::htmlDump($v, $level + 1);
				}
				array_pop($list);
				$s .= "$space}</code>";

			} else {
				$s .= "{ ... }";
			}
			return $s . "\n";

		} elseif (is_resource($var)) {
			$type = get_resource_type($var);
			$s = '<span class="php-resource">' . htmlSpecialChars($type) . " resource</span> ";

			static $info = array('stream' => 'stream_get_meta_data', 'curl' => 'curl_getinfo');
			if (isset($info[$type])) {
				$space = str_repeat($space1 = '   ', $level);
				$s .= "<code>{\n";
				foreach (call_user_func($info[$type], $var) as $k => $v) {
					$s .= $space . $space1 . '<span class="php-key">' . htmlSpecialChars($k) . "</span> => " . self::htmlDump($v, $level + 1);
				}
				$s .= "$space}</code>";
			}
			return $s . "\n";

		} else {
			return "<span>unknown type</span>\n";
		}
	}



	/**
	 * Dumps variable.
	 * @param  string
	 * @return string
	 */
	public static function clickableDump($dump, $collapsed = FALSE)
	{
		return '<pre class="nette-dump">' . preg_replace_callback(
			'#^( *)((?>[^(\r\n]{1,200}))\((\d+)\) <code>#m',
			function ($m) use ($collapsed) {
				return "$m[1]<a href='#' rel='next'>$m[2]($m[3]) "
					. (($m[1] || !$collapsed) && ($m[3] < 7)
					? '<abbr>&#x25bc;</abbr> </a><code>'
					: '<abbr>&#x25ba;</abbr> </a><code class="nette-collapsed">');
			},
			self::htmlDump($dump)
		) . '</pre>';
	}



	public static function findTrace(array $trace, $method, & $index = NULL)
	{
		$m = explode('::', $method);
		foreach ($trace as $i => $item) {
			if (isset($item['function']) && $item['function'] === end($m)
				&& isset($item['class']) === isset($m[1])
				&& (!isset($item['class']) || $item['class'] === $m[0] || is_subclass_of($item['class'], $m[0])))
			{
				$index = $i;
				return $item;
			}
		}
	}

}
