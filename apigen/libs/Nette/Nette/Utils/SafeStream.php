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
 * Provides atomicity and isolation for thread safe file manipulation using stream safe://
 *
 * <code>
 * file_put_contents('safe://myfile.txt', $content);
 *
 * $content = file_get_contents('safe://myfile.txt');
 *
 * unlink('safe://myfile.txt');
 * </code>
 *
 * @author     David Grudl
 * @internal
 */
final class SafeStream
{
	/** Name of stream protocol - safe:// */
	const PROTOCOL = 'safe';

	/** @var resource  orignal file handle */
	private $handle;

	/** @var resource  temporary file handle */
	private $tempHandle;

	/** @var string  orignal file path */
	private $file;

	/** @var string  temporary file path */
	private $tempFile;

	/** @var bool */
	private $deleteFile;

	/** @var bool  error detected? */
	private $writeError = FALSE;



	/**
	 * Registers protocol 'safe://'.
	 * @return bool
	 */
	public static function register()
	{
		return stream_wrapper_register(self::PROTOCOL, __CLASS__);
	}



	/**
	 * Opens file.
	 * @param  string    file name with stream protocol
	 * @param  string    mode - see fopen()
	 * @param  int       STREAM_USE_PATH, STREAM_REPORT_ERRORS
	 * @param  string    full path
	 * @return bool      TRUE on success or FALSE on failure
	 */
	public function stream_open($path, $mode, $options, &$opened_path)
	{
		$path = substr($path, strlen(self::PROTOCOL)+3);  // trim protocol safe://

		$flag = trim($mode, 'crwax+');  // text | binary mode
		$mode = trim($mode, 'tb');     // mode
		$use_path = (bool) (STREAM_USE_PATH & $options); // use include_path?

		// open file
		if ($mode === 'r') { // provides only isolation
			return $this->checkAndLock($this->tempHandle = fopen($path, 'r'.$flag, $use_path), LOCK_SH);

		} elseif ($mode === 'r+') {
			if (!$this->checkAndLock($this->handle = fopen($path, 'r'.$flag, $use_path), LOCK_EX)) {
				return FALSE;
			}

		} elseif ($mode[0] === 'x') {
			if (!$this->checkAndLock($this->handle = fopen($path, 'x'.$flag, $use_path), LOCK_EX)) {
				return FALSE;
			}
			$this->deleteFile = TRUE;

		} elseif ($mode[0] === 'w' || $mode[0] === 'a' || $mode[0] === 'c') {
			if ($this->checkAndLock($this->handle = @fopen($path, 'x'.$flag, $use_path), LOCK_EX)) { // intentionally @
				$this->deleteFile = TRUE;

			} elseif (!$this->checkAndLock($this->handle = fopen($path, 'a+'.$flag, $use_path), LOCK_EX)) {
				return FALSE;
			}

		} else {
			trigger_error("Unknown mode $mode", E_USER_WARNING);
			return FALSE;
		}

		// create temporary file in the same directory to provide atomicity
		$tmp = '~~' . lcg_value() . '.tmp';
		if (!$this->tempHandle = fopen($path . $tmp, (strpos($mode, '+') ? 'x+' : 'x').$flag, $use_path)) {
			$this->clean();
			return FALSE;
		}
		$this->tempFile = realpath($path . $tmp);
		$this->file = substr($this->tempFile, 0, -strlen($tmp));

		// copy to temporary file
		if ($mode === 'r+' || $mode[0] === 'a' || $mode[0] === 'c') {
			$stat = fstat($this->handle);
			fseek($this->handle, 0);
			if ($stat['size'] !== 0 && stream_copy_to_stream($this->handle, $this->tempHandle) !== $stat['size']) {
				$this->clean();
				return FALSE;
			}

			if ($mode[0] === 'a') { // emulate append mode
				fseek($this->tempHandle, 0, SEEK_END);
			}
		}

		return TRUE;
	}



	/**
	 * Checks handle and locks file.
	 * @return bool
	 */
	private function checkAndLock($handle, $lock)
	{
		if (!$handle) {
			return FALSE;

		} elseif (!flock($handle, $lock)) {
			fclose($handle);
			return FALSE;
		}

		return TRUE;
	}



	/**
	 * Error destructor.
	 */
	private function clean()
	{
		flock($this->handle, LOCK_UN);
		fclose($this->handle);
		if ($this->deleteFile) {
			unlink($this->file);
		}
		if ($this->tempHandle) {
			fclose($this->tempHandle);
			unlink($this->tempFile);
		}
	}



	/**
	 * Closes file.
	 * @return void
	 */
	public function stream_close()
	{
		if (!$this->tempFile) { // 'r' mode
			flock($this->tempHandle, LOCK_UN);
			fclose($this->tempHandle);
			return;
		}

		flock($this->handle, LOCK_UN);
		fclose($this->handle);
		fclose($this->tempHandle);

		if ($this->writeError /*5.2*|| !(substr(PHP_OS, 0, 3) === 'WIN' ? unlink($this->file) : TRUE)*/
			|| !rename($this->tempFile, $this->file) // try to rename temp file
		) {
			unlink($this->tempFile); // otherwise delete temp file
			if ($this->deleteFile) {
				unlink($this->file);
			}
		}
	}



	/**
	 * Reads up to length bytes from the file.
	 * @param  int    length
	 * @return string
	 */
	public function stream_read($length)
	{
		return fread($this->tempHandle, $length);
	}



	/**
	 * Writes the string to the file.
	 * @param  string    data to write
	 * @return int      number of bytes that were successfully stored
	 */
	public function stream_write($data)
	{
		$len = strlen($data);
		$res = fwrite($this->tempHandle, $data, $len);

		if ($res !== $len) { // disk full?
			$this->writeError = TRUE;
		}

		return $res;
	}



	/**
	 * Returns the position of the file.
	 * @return int
	 */
	public function stream_tell()
	{
		return ftell($this->tempHandle);
	}



	/**
	 * Returns TRUE if the file pointer is at end-of-file.
	 * @return bool
	 */
	public function stream_eof()
	{
		return feof($this->tempHandle);
	}



	/**
	 * Sets the file position indicator for the file.
	 * @param  int    position
	 * @param  int    see fseek()
	 * @return int   Return TRUE on success
	 */
	public function stream_seek($offset, $whence)
	{
		return fseek($this->tempHandle, $offset, $whence) === 0; // ???
	}



	/**
	 * Gets information about a file referenced by $this->tempHandle.
	 * @return array
	 */
	public function stream_stat()
	{
		return fstat($this->tempHandle);
	}



	/**
	 * Gets information about a file referenced by filename.
	 * @param  string    file name
	 * @param  int       STREAM_URL_STAT_LINK, STREAM_URL_STAT_QUIET
	 * @return array
	 */
	public function url_stat($path, $flags)
	{
		// This is not thread safe
		$path = substr($path, strlen(self::PROTOCOL)+3);
		return ($flags & STREAM_URL_STAT_LINK) ? @lstat($path) : @stat($path); // intentionally @
	}



	/**
	 * Deletes a file.
	 * On Windows unlink is not allowed till file is opened
	 * @param  string    file name with stream protocol
	 * @return bool      TRUE on success or FALSE on failure
	 */
	public function unlink($path)
	{
		$path = substr($path, strlen(self::PROTOCOL)+3);
		return unlink($path);
	}

}
