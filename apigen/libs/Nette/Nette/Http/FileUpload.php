<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Http;

use Nette;



/**
 * Provides access to individual files that have been uploaded by a client.
 *
 * @author     David Grudl
 *
 * @property-read string $name
 * @property-read string $sanitizedName
 * @property-read string $contentType
 * @property-read int $size
 * @property-read string $temporaryFile
 * @property-read int $error
 * @property-read bool $ok
 * @property-read bool $image
 * @property-read array $imageSize
 * @property-read string $contents
 */
class FileUpload extends Nette\Object
{
	/** @var string */
	private $name;

	/** @var string */
	private $type;

	/** @var string */
	private $size;

	/** @var string */
	private $tmpName;

	/** @var int */
	private $error;



	public function __construct($value)
	{
		foreach (array('name', 'type', 'size', 'tmp_name', 'error') as $key) {
			if (!isset($value[$key]) || !is_scalar($value[$key])) {
				$this->error = UPLOAD_ERR_NO_FILE;
				return; // or throw exception?
			}
		}
		$this->name = $value['name'];
		$this->size = $value['size'];
		$this->tmpName = $value['tmp_name'];
		$this->error = $value['error'];
	}



	/**
	 * Returns the file name.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * Returns the sanitized file name.
	 * @return string
	 */
	public function getSanitizedName()
	{
		return trim(Nette\Utils\Strings::webalize($this->name, '.', FALSE), '.-');
	}



	/**
	 * Returns the MIME content type of an uploaded file.
	 * @return string
	 */
	public function getContentType()
	{
		if ($this->isOk() && $this->type === NULL) {
			$this->type = Nette\Utils\MimeTypeDetector::fromFile($this->tmpName);
		}
		return $this->type;
	}



	/**
	 * Returns the size of an uploaded file.
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}



	/**
	 * Returns the path to an uploaded file.
	 * @return string
	 */
	public function getTemporaryFile()
	{
		return $this->tmpName;
	}



	/**
	 * Returns the path to an uploaded file.
	 * @return string
	 */
	public function __toString()
	{
		return $this->tmpName;
	}



	/**
	 * Returns the error code. {@link http://php.net/manual/en/features.file-upload.errors.php}
	 * @return int
	 */
	public function getError()
	{
		return $this->error;
	}



	/**
	 * Is there any error?
	 * @return bool
	 */
	public function isOk()
	{
		return $this->error === UPLOAD_ERR_OK;
	}



	/**
	 * Move uploaded file to new location.
	 * @param  string
	 * @return FileUpload  provides a fluent interface
	 */
	public function move($dest)
	{
		@mkdir(dirname($dest), 0777, TRUE); // @ - dir may already exist
		/*5.2*if (substr(PHP_OS, 0, 3) === 'WIN') { @unlink($dest); }*/
		if (!call_user_func(is_uploaded_file($this->tmpName) ? 'move_uploaded_file' : 'rename', $this->tmpName, $dest)) {
			throw new Nette\InvalidStateException("Unable to move uploaded file '$this->tmpName' to '$dest'.");
		}
		chmod($dest, 0666);
		$this->tmpName = $dest;
		return $this;
	}



	/**
	 * Is uploaded file GIF, PNG or JPEG?
	 * @return bool
	 */
	public function isImage()
	{
		return in_array($this->getContentType(), array('image/gif', 'image/png', 'image/jpeg'), TRUE);
	}



	/**
	 * Returns the image.
	 * @return Nette\Image
	 */
	public function toImage()
	{
		return Nette\Image::fromFile($this->tmpName);
	}



	/**
	 * Returns the dimensions of an uploaded image as array.
	 * @return array
	 */
	public function getImageSize()
	{
		return $this->isOk() ? @getimagesize($this->tmpName) : NULL; // @ - files smaller than 12 bytes causes read error
	}



	/**
	 * Get file contents.
	 * @return string
	 */
	public function getContents()
	{
		// future implementation can try to work around safe_mode and open_basedir limitations
		return $this->isOk() ? file_get_contents($this->tmpName) : NULL;
	}

}
