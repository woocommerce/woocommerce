<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\Responses;

use Nette;



/**
 * File download response.
 *
 * @author     David Grudl
 *
 * @property-read string $file
 * @property-read string $name
 * @property-read string $contentType
 */
class FileResponse extends Nette\Object implements Nette\Application\IResponse
{
	/** @var string */
	private $file;

	/** @var string */
	private $contentType;

	/** @var string */
	private $name;

	/** @var bool */
	public $resuming = TRUE;


	/**
	 * @param  string  file path
	 * @param  string  imposed file name
	 * @param  string  MIME content type
	 */
	public function __construct($file, $name = NULL, $contentType = NULL)
	{
		if (!is_file($file)) {
			throw new Nette\Application\BadRequestException("File '$file' doesn't exist.");
		}

		$this->file = $file;
		$this->name = $name ? $name : basename($file);
		$this->contentType = $contentType ? $contentType : 'application/octet-stream';
	}



	/**
	 * Returns the path to a downloaded file.
	 * @return string
	 */
	final public function getFile()
	{
		return $this->file;
	}



	/**
	 * Returns the file name.
	 * @return string
	 */
	final public function getName()
	{
		return $this->name;
	}



	/**
	 * Returns the MIME content type of a downloaded file.
	 * @return string
	 */
	final public function getContentType()
	{
		return $this->contentType;
	}



	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$httpResponse->setContentType($this->contentType);
		$httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->name . '"');

		$filesize = $length = filesize($this->file);
		$handle = fopen($this->file, 'r');

		if ($this->resuming) {
			$httpResponse->setHeader('Accept-Ranges', 'bytes');
			if (preg_match('#^bytes=(\d*)-(\d*)$#', $httpRequest->getHeader('Range'), $matches)) {
				list(, $start, $end) = $matches;
				if ($start === '') {
					$start = max(0, $filesize - $end);
					$end = $filesize - 1;

				} elseif ($end === '' || $end > $filesize - 1) {
					$end = $filesize - 1;
				}
				if ($end <= $start) {
					$httpResponse->setCode(416); // requested range not satisfiable
					return;
				}

				$httpResponse->setCode(206);
				$httpResponse->setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $filesize);
				$length = $end - $start + 1;
				fseek($handle, $start);

			} else {
				$httpResponse->setHeader('Content-Range', 'bytes 0-' . ($filesize - 1) . '/' . $filesize);
			}
		}

		$httpResponse->setHeader('Content-Length', $length);
		while (!feof($handle) && $length > 0) {
			echo $s = fread($handle, min(4e6, $length));
			$length -= strlen($s);
		}
		fclose($handle);
	}

}
