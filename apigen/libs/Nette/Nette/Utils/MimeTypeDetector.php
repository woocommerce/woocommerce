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
 * Mime type detector.
 *
 * @author     David Grudl
 */
final class MimeTypeDetector
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}



	/**
	 * Returns the MIME content type of file.
	 * @param  string
	 * @return string
	 */
	public static function fromFile($file)
	{
		if (!is_file($file)) {
			throw new Nette\FileNotFoundException("File '$file' not found.");
		}

		$info = @getimagesize($file); // @ - files smaller than 12 bytes causes read error
		if (isset($info['mime'])) {
			return $info['mime'];

		} elseif (extension_loaded('fileinfo')) {
			$type = preg_replace('#[\s;].*$#', '', finfo_file(finfo_open(FILEINFO_MIME), $file));

		} elseif (function_exists('mime_content_type')) {
			$type = mime_content_type($file);
		}

		return isset($type) && preg_match('#^\S+/\S+$#', $type) ? $type : 'application/octet-stream';
	}



	/**
	 * Returns the MIME content type of file.
	 * @param  string
	 * @return string
	 */
	public static function fromString($data)
	{
		if (extension_loaded('fileinfo') && preg_match('#^(\S+/[^\s;]+)#', finfo_buffer(finfo_open(FILEINFO_MIME), $data), $m)) {
			return $m[1];

		} elseif (strncmp($data, "\xff\xd8", 2) === 0) {
			return 'image/jpeg';

		} elseif (strncmp($data, "\x89PNG", 4) === 0) {
			return 'image/png';

		} elseif (strncmp($data, "GIF", 3) === 0) {
			return 'image/gif';

		} else {
			return 'application/octet-stream';
		}
	}

}
