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
 * JSON encoder and decoder.
 *
 * @author     David Grudl
 */
final class Json
{
	const FORCE_ARRAY = 1;

	/** @var array */
	private static $messages = array(
		JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
		JSON_ERROR_STATE_MISMATCH => 'Syntax error, malformed JSON',
		JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
		JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
	);



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}



	/**
	 * Returns the JSON representation of a value.
	 * @param  mixed
	 * @return string
	 */
	public static function encode($value)
	{
		Nette\Diagnostics\Debugger::tryError();
		if (function_exists('ini_set')) {
			$old = ini_set('display_errors', 0); // needed to receive 'Invalid UTF-8 sequence' error
			$json = json_encode($value);
			ini_set('display_errors', $old);
		} else {
			$json = json_encode($value);
		}
		if (Nette\Diagnostics\Debugger::catchError($e)) { // needed to receive 'recursion detected' error
			throw new JsonException($e->getMessage());
		}
		return $json;
	}



	/**
	 * Decodes a JSON string.
	 * @param  string
	 * @param  int
	 * @return mixed
	 */
	public static function decode($json, $options = 0)
	{
		$json = (string) $json;
		$value = json_decode($json, (bool) ($options & self::FORCE_ARRAY));
		if ($value === NULL && $json !== '' && strcasecmp($json, 'null')) { // '' do not clean json_last_error
			$error = PHP_VERSION_ID >= 50300 ? json_last_error() : 0;
			throw new JsonException(isset(static::$messages[$error]) ? static::$messages[$error] : 'Unknown error', $error);
		}
		return $value;
	}

}



/**
 * The exception that indicates error of JSON encoding/decoding.
 */
class JsonException extends \Exception
{
}
