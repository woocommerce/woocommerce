<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Utils;

use Nette;



/**
 * Simple lexical analyser.
 *
 * @author     David Grudl
 */
class Tokenizer extends Nette\Object
{
	/** @var array */
	public $tokens;

	/** @var int */
	public $position = 0;

	/** @var array */
	public $ignored = array();

	/** @var string */
	private $input;

	/** @var string */
	private $re;

	/** @var array */
	private $types;

	/** @var array|string */
	public $current;



	/**
	 * @param  array of [(int) symbol type => pattern]
	 * @param  string  regular expression flag
	 */
	public function __construct(array $patterns, $flags = '')
	{
		$this->re = '~(' . implode(')|(', $patterns) . ')~A' . $flags;
		$keys = array_keys($patterns);
		$this->types = $keys === range(0, count($patterns) - 1) ? FALSE : $keys;
	}



	/**
	 * Tokenize string.
	 * @param  string
	 * @return array
	 */
	public function tokenize($input)
	{
		$this->input = $input;
		if ($this->types) {
			$this->tokens = Strings::matchAll($input, $this->re);
			$len = 0;
			$count = count($this->types);
			$line = 1;
			foreach ($this->tokens as & $match) {
				$type = NULL;
				for ($i = 1; $i <= $count; $i++) {
					if (!isset($match[$i])) {
						break;
					} elseif ($match[$i] != NULL) {
						$type = $this->types[$i - 1]; break;
					}
				}
				$match = self::createToken($match[0], $type, $line);
				$len += strlen($match['value']);
				$line += substr_count($match['value'], "\n");
			}
			if ($len !== strlen($input)) {
				$errorOffset = $len;
			}

		} else {
			$this->tokens = Strings::split($input, $this->re, PREG_SPLIT_NO_EMPTY);
			if ($this->tokens && !Strings::match(end($this->tokens), $this->re)) {
				$tmp = Strings::split($this->input, $this->re, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
				list(, $errorOffset) = end($tmp);
			}
		}

		if (isset($errorOffset)) {
			$line = $errorOffset ? substr_count($this->input, "\n", 0, $errorOffset) + 1 : 1;
			$col = $errorOffset - strrpos(substr($this->input, 0, $errorOffset), "\n") + 1;
			$token = str_replace("\n", '\n', substr($input, $errorOffset, 10));
			throw new TokenizerException("Unexpected '$token' on line $line, column $col.");
		}
		return $this->tokens;
	}



	public static function createToken($value, $type = NULL, $line = NULL)
	{
		return array('value' => $value, 'type' => $type, 'line' => $line);
	}



	/**
	 * Returns position of token in input string.
	 * @param  int token number
	 * @return array [offset, line, column]
	 */
	public function getOffset($i)
	{
		$tokens = Strings::split($this->input, $this->re, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
		$offset = isset($tokens[$i]) ? $tokens[$i][1] : strlen($this->input);
		return array(
			$offset,
			($offset ? substr_count($this->input, "\n", 0, $offset) + 1 : 1),
			$offset - strrpos(substr($this->input, 0, $offset), "\n"),
		);
	}



	/**
	 * Returns next token as string.
	 * @param  desired token
	 * @return string
	 */
	public function fetch()
	{
		$args = func_get_args();
		return $this->scan($args, TRUE);
	}



	/**
	 * Returns next token.
	 * @param  desired token
	 * @return array|string
	 */
	public function fetchToken()
	{
		$args = func_get_args();
		return $this->scan($args, TRUE) === FALSE ? FALSE : $this->current;
	}



	/**
	 * Returns concatenation of all next tokens.
	 * @param  desired token
	 * @return string
	 */
	public function fetchAll()
	{
		$args = func_get_args();
		return $this->scan($args, FALSE);
	}



	/**
	 * Returns concatenation of all next tokens until it sees a token with the given value.
	 * @param  tokens
	 * @return string
	 */
	public function fetchUntil($arg)
	{
		$args = func_get_args();
		return $this->scan($args, FALSE, TRUE, TRUE);
	}



	/**
	 * Checks the next token.
	 * @param  token
	 * @return string
	 */
	public function isNext($arg)
	{
		$args = func_get_args();
		return (bool) $this->scan($args, TRUE, FALSE);
	}



	/**
	 * Checks the previous token.
	 * @param  token
	 * @return string
	 */
	public function isPrev($arg)
	{
		$args = func_get_args();
		return (bool) $this->scan($args, TRUE, FALSE, FALSE, TRUE);
	}



	/**
	 * Checks existence of next token.
	 * @return bool
	 */
	public function hasNext()
	{
		return isset($this->tokens[$this->position]);
	}



	/**
	 * Checks existence of previous token.
	 * @return bool
	 */
	public function hasPrev()
	{
		return $this->position > 1;
	}



	/**
	 * Checks the current token.
	 * @param  token
	 * @return string
	 */
	public function isCurrent($arg)
	{
		$args = func_get_args();
		if (is_array($this->current)) {
			return in_array($this->current['value'], $args, TRUE)
				|| in_array($this->current['type'], $args, TRUE);
		} else {
			return in_array($this->current, $args, TRUE);
		}
	}



	public function reset()
	{
		$this->position = 0;
		$this->current = NULL;
	}



	/**
	 * Looks for (first) (not) wanted tokens.
	 * @param  int token number
	 * @return array
	 */
	private function scan($wanted, $first, $advance = TRUE, $neg = FALSE, $prev = FALSE)
	{
		$res = FALSE;
		$pos = $this->position + ($prev ? -2 : 0);
		while (isset($this->tokens[$pos])) {
			$token = $this->tokens[$pos];
			$pos += $prev ? -1 : 1;
			$value = is_array($token) ? $token['value'] : $token;
			$type = is_array($token) ? $token['type'] : $token;
			if (!$wanted || (in_array($value, $wanted, TRUE) || in_array($type, $wanted, TRUE)) ^ $neg) {
				if ($advance) {
					$this->position = $pos;
					$this->current = $token;
				}
				$res .= $value;
				if ($first) {
					break;
				}

			} elseif ($neg || !in_array($type, $this->ignored, TRUE)) {
				break;
			}
		}
		return $res;
	}

}



/**
 * The exception that indicates tokenizer error.
 */
class TokenizerException extends \Exception
{
}
