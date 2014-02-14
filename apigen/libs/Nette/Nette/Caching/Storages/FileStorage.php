<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Caching\Storages;

use Nette,
	Nette\Caching\Cache;



/**
 * Cache file storage.
 *
 * @author     David Grudl
 */
class FileStorage extends Nette\Object implements Nette\Caching\IStorage
{
	/**
	 * Atomic thread safe logic:
	 *
	 * 1) reading: open(r+b), lock(SH), read
	 *     - delete?: delete*, close
	 * 2) deleting: delete*
	 * 3) writing: open(r+b || wb), lock(EX), truncate*, write data, write meta, close
	 *
	 * delete* = try unlink, if fails (on NTFS) { lock(EX), truncate, close, unlink } else close (on ext3)
	 */

	/** @internal cache file structure */
	const META_HEADER_LEN = 28, // 22b signature + 6b meta-struct size + serialized meta-struct + data
	// meta structure: array of
		META_TIME = 'time', // timestamp
		META_SERIALIZED = 'serialized', // is content serialized?
		META_EXPIRE = 'expire', // expiration timestamp
		META_DELTA = 'delta', // relative (sliding) expiration
		META_ITEMS = 'di', // array of dependent items (file => timestamp)
		META_CALLBACKS = 'callbacks'; // array of callbacks (function, args)

	/** additional cache structure */
	const FILE = 'file',
		HANDLE = 'handle';


	/** @var float  probability that the clean() routine is started */
	public static $gcProbability = 0.001;

	/** @var bool */
	public static $useDirectories = TRUE;

	/** @var string */
	private $dir;

	/** @var bool */
	private $useDirs;

	/** @var IJournal */
	private $journal;

	/** @var array */
	private $locks;



	public function __construct($dir, IJournal $journal = NULL)
	{
		$this->dir = realpath($dir);
		if ($this->dir === FALSE) {
			throw new Nette\DirectoryNotFoundException("Directory '$dir' not found.");
		}

		$this->useDirs = (bool) static::$useDirectories;
		$this->journal = $journal;

		if (mt_rand() / mt_getrandmax() < static::$gcProbability) {
			$this->clean(array());
		}
	}



	/**
	 * Read from cache.
	 * @param  string key
	 * @return mixed|NULL
	 */
	public function read($key)
	{
		$meta = $this->readMetaAndLock($this->getCacheFile($key), LOCK_SH);
		if ($meta && $this->verify($meta)) {
			return $this->readData($meta); // calls fclose()

		} else {
			return NULL;
		}
	}



	/**
	 * Verifies dependencies.
	 * @param  array
	 * @return bool
	 */
	private function verify($meta)
	{
		do {
			if (!empty($meta[self::META_DELTA])) {
				// meta[file] was added by readMetaAndLock()
				if (filemtime($meta[self::FILE]) + $meta[self::META_DELTA] < time()) {
					break;
				}
				touch($meta[self::FILE]);

			} elseif (!empty($meta[self::META_EXPIRE]) && $meta[self::META_EXPIRE] < time()) {
				break;
			}

			if (!empty($meta[self::META_CALLBACKS]) && !Cache::checkCallbacks($meta[self::META_CALLBACKS])) {
				break;
			}

			if (!empty($meta[self::META_ITEMS])) {
				foreach ($meta[self::META_ITEMS] as $depFile => $time) {
					$m = $this->readMetaAndLock($depFile, LOCK_SH);
					if ($m[self::META_TIME] !== $time || ($m && !$this->verify($m))) {
						break 2;
					}
				}
			}

			return TRUE;
		} while (FALSE);

		$this->delete($meta[self::FILE], $meta[self::HANDLE]); // meta[handle] & meta[file] was added by readMetaAndLock()
		return FALSE;
	}



	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 * @param  string key
	 * @return void
	 */
	public function lock($key)
	{
		$cacheFile = $this->getCacheFile($key);
		if ($this->useDirs && !is_dir($dir = dirname($cacheFile))) {
			@mkdir($dir, 0777); // @ - directory may already exist
		}
		$handle = @fopen($cacheFile, 'r+b'); // @ - file may not exist
		if (!$handle) {
			$handle = fopen($cacheFile, 'wb');
			if (!$handle) {
				return;
			}
		}

		$this->locks[$key] = $handle;
		flock($handle, LOCK_EX);
	}



	/**
	 * Writes item into the cache.
	 * @param  string key
	 * @param  mixed  data
	 * @param  array  dependencies
	 * @return void
	 */
	public function write($key, $data, array $dp)
	{
		$meta = array(
			self::META_TIME => microtime(),
		);

		if (isset($dp[Cache::EXPIRATION])) {
			if (empty($dp[Cache::SLIDING])) {
				$meta[self::META_EXPIRE] = $dp[Cache::EXPIRATION] + time(); // absolute time
			} else {
				$meta[self::META_DELTA] = (int) $dp[Cache::EXPIRATION]; // sliding time
			}
		}

		if (isset($dp[Cache::ITEMS])) {
			foreach ((array) $dp[Cache::ITEMS] as $item) {
				$depFile = $this->getCacheFile($item);
				$m = $this->readMetaAndLock($depFile, LOCK_SH);
				$meta[self::META_ITEMS][$depFile] = $m[self::META_TIME]; // may be NULL
				unset($m);
			}
		}

		if (isset($dp[Cache::CALLBACKS])) {
			$meta[self::META_CALLBACKS] = $dp[Cache::CALLBACKS];
		}

		if (!isset($this->locks[$key])) {
			$this->lock($key);
			if (!isset($this->locks[$key])) {
				return;
			}
		}
		$handle = $this->locks[$key];
		unset($this->locks[$key]);

		$cacheFile = $this->getCacheFile($key);

		if (isset($dp[Cache::TAGS]) || isset($dp[Cache::PRIORITY])) {
			if (!$this->journal) {
				throw new Nette\InvalidStateException('CacheJournal has not been provided.');
			}
			$this->journal->write($cacheFile, $dp);
		}

		ftruncate($handle, 0);

		if (!is_string($data)) {
			$data = serialize($data);
			$meta[self::META_SERIALIZED] = TRUE;
		}

		$head = serialize($meta) . '?>';
		$head = '<?php //netteCache[01]' . str_pad((string) strlen($head), 6, '0', STR_PAD_LEFT) . $head;
		$headLen = strlen($head);
		$dataLen = strlen($data);

		do {
			if (fwrite($handle, str_repeat("\x00", $headLen), $headLen) !== $headLen) {
				break;
			}

			if (fwrite($handle, $data, $dataLen) !== $dataLen) {
				break;
			}

			fseek($handle, 0);
			if (fwrite($handle, $head, $headLen) !== $headLen) {
				break;
			}

			flock($handle, LOCK_UN);
			fclose($handle);
			return;
		} while (FALSE);

		$this->delete($cacheFile, $handle);
	}



	/**
	 * Removes item from the cache.
	 * @param  string key
	 * @return void
	 */
	public function remove($key)
	{
		unset($this->locks[$key]);
		$this->delete($this->getCacheFile($key));
	}



	/**
	 * Removes items from the cache by conditions & garbage collector.
	 * @param  array  conditions
	 * @return void
	 */
	public function clean(array $conds)
	{
		$all = !empty($conds[Cache::ALL]);
		$collector = empty($conds);

		// cleaning using file iterator
		if ($all || $collector) {
			$now = time();
			foreach (Nette\Utils\Finder::find('_*')->from($this->dir)->childFirst() as $entry) {
				$path = (string) $entry;
				if ($entry->isDir()) { // collector: remove empty dirs
					@rmdir($path); // @ - removing dirs is not necessary
					continue;
				}
				if ($all) {
					$this->delete($path);

				} else { // collector
					$meta = $this->readMetaAndLock($path, LOCK_SH);
					if (!$meta) {
						continue;
					}

					if ((!empty($meta[self::META_DELTA]) && filemtime($meta[self::FILE]) + $meta[self::META_DELTA] < $now)
						|| (!empty($meta[self::META_EXPIRE]) && $meta[self::META_EXPIRE] < $now)
					) {
						$this->delete($path, $meta[self::HANDLE]);
						continue;
					}

					flock($meta[self::HANDLE], LOCK_UN);
					fclose($meta[self::HANDLE]);
				}
			}

			if ($this->journal) {
				$this->journal->clean($conds);
			}
			return;
		}

		// cleaning using journal
		if ($this->journal) {
			foreach ($this->journal->clean($conds) as $file) {
				$this->delete($file);
			}
		}
	}



	/**
	 * Reads cache data from disk.
	 * @param  string  file path
	 * @param  int     lock mode
	 * @return array|NULL
	 */
	protected function readMetaAndLock($file, $lock)
	{
		$handle = @fopen($file, 'r+b'); // @ - file may not exist
		if (!$handle) {
			return NULL;
		}

		flock($handle, $lock);

		$head = stream_get_contents($handle, self::META_HEADER_LEN);
		if ($head && strlen($head) === self::META_HEADER_LEN) {
			$size = (int) substr($head, -6);
			$meta = stream_get_contents($handle, $size, self::META_HEADER_LEN);
			$meta = @unserialize($meta); // intentionally @
			if (is_array($meta)) {
				fseek($handle, $size + self::META_HEADER_LEN); // needed by PHP < 5.2.6
				$meta[self::FILE] = $file;
				$meta[self::HANDLE] = $handle;
				return $meta;
			}
		}

		flock($handle, LOCK_UN);
		fclose($handle);
		return NULL;
	}



	/**
	 * Reads cache data from disk and closes cache file handle.
	 * @param  array
	 * @return mixed
	 */
	protected function readData($meta)
	{
		$data = stream_get_contents($meta[self::HANDLE]);
		flock($meta[self::HANDLE], LOCK_UN);
		fclose($meta[self::HANDLE]);

		if (empty($meta[self::META_SERIALIZED])) {
			return $data;
		} else {
			return @unserialize($data); // intentionally @
		}
	}



	/**
	 * Returns file name.
	 * @param  string
	 * @return string
	 */
	protected function getCacheFile($key)
	{
		$file = urlencode($key);
		if ($this->useDirs && $a = strrpos($file, '%00')) { // %00 = urlencode(Nette\Caching\Cache::NAMESPACE_SEPARATOR)
			$file = substr_replace($file, '/_', $a, 3);
		}
		return $this->dir . '/_' . $file;
	}



	/**
	 * Deletes and closes file.
	 * @param  string
	 * @param  resource
	 * @return void
	 */
	private static function delete($file, $handle = NULL)
	{
		if (@unlink($file)) { // @ - file may not already exist
			if ($handle) {
				flock($handle, LOCK_UN);
				fclose($handle);
			}
			return;
		}

		if (!$handle) {
			$handle = @fopen($file, 'r+'); // @ - file may not exist
		}
		if ($handle) {
			flock($handle, LOCK_EX);
			ftruncate($handle, 0);
			flock($handle, LOCK_UN);
			fclose($handle);
			@unlink($file); // @ - file may not already exist
		}
	}

}
