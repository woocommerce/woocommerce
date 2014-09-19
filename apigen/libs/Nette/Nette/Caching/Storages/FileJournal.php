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
 * Btree+ based file journal.
 *
 * @author     Jakub Onderka
 */
class FileJournal extends Nette\Object implements IJournal
{
	/** Filename with journal */
	const FILE = 'btfj.dat';

	/** 4 bytes file header magic (btfj) */
	const FILE_MAGIC  = 0x6274666A;

	/** 4 bytes index node magic (inde) */
	const INDEX_MAGIC = 0x696E6465;

	/** 4 bytes data node magic (data) */
	const DATA_MAGIC  = 0x64617461;

	/** Node size in bytes */
	const NODE_SIZE = 4096;

	/** Bit rotation for saving data into nodes. BITROT = log2(NODE_SIZE) */
	const BITROT = 12;

	/** Header size in bytes */
	const HEADER_SIZE = 4096;

	/** Size of 32 bit integer in bytes. INT32_SIZE = 32 / 8 :-) */
	const INT32_SIZE  = 4;

	const INFO = 'i',
		TYPE = 't', // TAGS, PRIORITY or DATA
		IS_LEAF = 'il', // TRUE or FALSE
		PREV_NODE = 'p', // Prev node id
		END = 'e',
		MAX = 'm', // Maximal key in node or -1 when is last node
		INDEX_DATA = 'id',
		LAST_INDEX = 'l';

	// Indexes
	const TAGS = 't',
		PRIORITY = 'p',
		ENTRIES = 'e';

	const DATA = 'd',
		KEY = 'k', // string
		DELETED = 'd'; // TRUE or FALSE

	/** Debug mode, only for testing purposes */
	public static $debug = FALSE;

	/** @var string */
	private $file;

	/** @var resource */
	private $handle;

	/** @var int Last complete free node */
	private $lastNode = 2;

	/** @var int Last modification time of journal file */
	private $lastModTime = NULL;

	/** @var array Cache and uncommited but changed nodes */
	private $nodeCache = array();

	/** @var array */
	private $nodeChanged = array();

	/** @var array */
	private $toCommit = array();

	/** @var array */
	private $deletedLinks = array();

	/** @var array Free space in data nodes */
	private $dataNodeFreeSpace = array();

	/** @var array */
	private static $startNode = array(
		self::TAGS     => 0,
		self::PRIORITY => 1,
		self::ENTRIES  => 2,
		self::DATA     => 3,
	);



	/**
	 * @param  string  Directory location with journal file
	 */
	public function __construct($dir)
	{
		$this->file = $dir . '/' . self::FILE;

		// Create jorunal file when not exists
		if (!file_exists($this->file)) {
			$init = @fopen($this->file, 'xb'); // intentionally @
			if (!$init) {
				clearstatcache();
				if (!file_exists($this->file)) {
					throw new Nette\InvalidStateException("Cannot create journal file $this->file.");
				}
			} else {
				$writen = fwrite($init, pack('N2', self::FILE_MAGIC, $this->lastNode));
				fclose($init);
				if ($writen !== self::INT32_SIZE * 2) {
					throw new Nette\InvalidStateException("Cannot write journal header.");
				}
			}
		}

		$this->handle = fopen($this->file, 'r+b');

		if (!$this->handle) {
			throw new Nette\InvalidStateException("Cannot open journal file '$this->file'.");
		}

		if (!flock($this->handle, LOCK_SH)) {
			throw new Nette\InvalidStateException('Cannot acquire shared lock on journal.');
		}

		$header = stream_get_contents($this->handle, 2 * self::INT32_SIZE, 0);

		flock($this->handle, LOCK_UN);

		list(, $fileMagic, $this->lastNode) = unpack('N2', $header);

		if ($fileMagic !== self::FILE_MAGIC) {
			fclose($this->handle);
			$this->handle = false;
			throw new Nette\InvalidStateException("Malformed journal file '$this->file'.");
		}
	}



	/**
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->handle) {
			$this->headerCommit();
			flock($this->handle, LOCK_UN); // Since PHP 5.3.3 is manual unlock necesary
			fclose($this->handle);
			$this->handle = false;
		}
	}



	/**
	 * Writes entry information into the journal.
	 * @param  string
	 * @param  array
	 * @return void
	 */
	public function write($key, array $dependencies)
	{
		$this->lock();

		$priority = !isset($dependencies[Cache::PRIORITY]) ? FALSE : (int) $dependencies[Cache::PRIORITY];
		$tags = empty($dependencies[Cache::TAGS]) ? FALSE : (array) $dependencies[Cache::TAGS];

		$exists = FALSE;
		$keyHash = crc32($key);
		list($entriesNodeId, $entriesNode) = $this->findIndexNode(self::ENTRIES, $keyHash);

		if (isset($entriesNode[$keyHash])) {
			$entries = $this->mergeIndexData($entriesNode[$keyHash]);
			foreach ($entries as $link => $foo) {
				$dataNode = $this->getNode($link >> self::BITROT);
				if ($dataNode[$link][self::KEY] === $key) {
					if ($dataNode[$link][self::TAGS] == $tags && $dataNode[$link][self::PRIORITY] === $priority) { // intentionally ==, the order of tags does not matter
						if ($dataNode[$link][self::DELETED]) {
							$dataNode[$link][self::DELETED] = FALSE;
							$this->saveNode($link >> self::BITROT, $dataNode);
						}
						$exists = TRUE;
					} else { // Alredy exists, but with other tags or priority
						$toDelete = array();
						foreach ($dataNode[$link][self::TAGS] as $tag) {
							$toDelete[self::TAGS][$tag][$link] = TRUE;
						}
						if ($dataNode[$link][self::PRIORITY] !== FALSE) {
							$toDelete[self::PRIORITY][$dataNode[$link][self::PRIORITY]][$link] = TRUE;
						}
						$toDelete[self::ENTRIES][$keyHash][$link] = TRUE;
						$this->cleanFromIndex($toDelete);
						$entriesNode = $this->getNode($entriesNodeId); // Node was changed, get again
						unset($dataNode[$link]);
						$this->saveNode($link >> self::BITROT, $dataNode);
					}
					break;
				}
			}
		}

		if ($exists === FALSE) {
			// Magical constants
			$requiredSize = strlen($key) + 75;
			if ($tags) {
				foreach ($tags as $tag) {
					$requiredSize += strlen($tag) + 13;
				}
			}
			$requiredSize += $priority ? 10 : 1;

			$freeDataNode = $this->findFreeDataNode($requiredSize);
			$data = $this->getNode($freeDataNode);

			if ($data === FALSE) {
				$data = array(
					self::INFO => array(
						self::LAST_INDEX => ($freeDataNode << self::BITROT),
						self::TYPE => self::DATA,
					)
				);
			}

			$dataNodeKey = ++$data[self::INFO][self::LAST_INDEX];
			$data[$dataNodeKey] = array(
				self::KEY => $key,
				self::TAGS => $tags ? $tags : array(),
				self::PRIORITY => $priority,
				self::DELETED => FALSE,
			);

			$this->saveNode($freeDataNode, $data);

			// Save to entries tree, ...
			$entriesNode[$keyHash][$dataNodeKey] = 1;
			$this->saveNode($entriesNodeId, $entriesNode);

			// ...tags tree...
			if ($tags) {
				foreach ($tags as $tag) {
					list($nodeId, $node) = $this->findIndexNode(self::TAGS, $tag);
					$node[$tag][$dataNodeKey] = 1;
					$this->saveNode($nodeId, $node);
				}
			}

			// ...and priority tree.
			if ($priority) {
				list($nodeId, $node) = $this->findIndexNode(self::PRIORITY, $priority);
				$node[$priority][$dataNodeKey] = 1;
				$this->saveNode($nodeId, $node);
			}
		}

		$this->commit();
		$this->unlock();
	}



	/**
	 * Cleans entries from journal.
	 * @param  array
	 * @return array of removed items or NULL when performing a full cleanup
	 */
	public function clean(array $conditions)
	{
		$this->lock();

		if (!empty($conditions[Cache::ALL])) {
			$this->nodeCache = $this->nodeChanged = $this->dataNodeFreeSpace = array();
			$this->deleteAll();
			$this->unlock();
			return;
		}

		$toDelete = array(
			self::TAGS => array(),
			self::PRIORITY => array(),
			self::ENTRIES => array()
		);

		$entries = array();

		if (!empty($conditions[Cache::TAGS])) {
			$entries = $this->cleanTags((array) $conditions[Cache::TAGS], $toDelete);
		}

		if (isset($conditions[Cache::PRIORITY])) {
			$this->arrayAppend($entries, $this->cleanPriority((int) $conditions[Cache::PRIORITY], $toDelete));
		}

		$this->deletedLinks = array();
		$this->cleanFromIndex($toDelete);

		$this->commit();
		$this->unlock();

		return $entries;
	}



	/**
	 * Cleans entries from journal by tags.
	 * @return array of removed items
	 */
	private function cleanTags(array $tags, array &$toDelete)
	{
		$entries = array();

		foreach ($tags as $tag) {
			list($nodeId, $node) = $this->findIndexNode(self::TAGS, $tag);

			if (isset($node[$tag])) {
				$ent = $this->cleanLinks($this->mergeIndexData($node[$tag]), $toDelete);
				$this->arrayAppend($entries, $ent);
			}
		}

		return $entries;
	}



	/**
	 * Cleans entries from journal by priority.
	 * @param  integer
	 * @param  array
	 * @return array of removed items
	 */
	private function cleanPriority($priority, array &$toDelete)
	{
		list($nodeId, $node) = $this->findIndexNode(self::PRIORITY, $priority);

		ksort($node);

		$allData = array();

		foreach ($node as $prior => $data) {
			if ($prior === self::INFO) {
				continue;
			} elseif ($prior > $priority) {
				break;
			}

			$this->arrayAppendKeys($allData, $this->mergeIndexData($data));
		}

		$nodeInfo = $node[self::INFO];
		while ($nodeInfo[self::PREV_NODE] !== -1) {
			$nodeId = $nodeInfo[self::PREV_NODE];
			$node = $this->getNode($nodeId);

			if ($node === FALSE) {
				if (self::$debug) {
					throw new Nette\InvalidStateException("Cannot load node number $nodeId.");
				}
				break;
			}

			$nodeInfo = $node[self::INFO];
			unset($node[self::INFO]);

			foreach ($node as $prior => $data) {
				$this->arrayAppendKeys($allData, $this->mergeIndexData($data));
			}
		}

		return $this->cleanLinks($allData, $toDelete);
	}



	/**
	 * Cleans links from $data.
	 * @param  array
	 * @param  array
	 * @return array of removed items
	 */
	private function cleanLinks(array $data, array &$toDelete)
	{
		$return = array();

		$data = array_keys($data);
		sort($data);
		$max = count($data);
		$data[] = 0;
		$i = 0;

		while ($i < $max) {
			$searchLink = $data[$i];

			if (isset($this->deletedLinks[$searchLink])) {
				++$i;
				continue;
			}

			$nodeId = $searchLink >> self::BITROT;
			$node = $this->getNode($nodeId);

			if ($node === FALSE) {
				if (self::$debug) {
					throw new Nette\InvalidStateException('Cannot load node number ' . ($nodeId) . '.');
				}
				++$i;
				continue;
			}

			do {
				$link = $data[$i];

				if (!isset($node[$link])) {
					if (self::$debug) {
						throw new Nette\InvalidStateException("Link with ID $searchLink is not in node ". ($nodeId) . '.');
					}
					continue;
				} elseif (isset($this->deletedLinks[$link])) {
					continue;
				}

				$nodeLink = &$node[$link];
				if (!$nodeLink[self::DELETED]) {
					$nodeLink[self::DELETED] = TRUE;
					$return[] = $nodeLink[self::KEY];
				} else {
					foreach ($nodeLink[self::TAGS] as $tag) {
						$toDelete[self::TAGS][$tag][$link] = TRUE;
					}
					if ($nodeLink[self::PRIORITY] !== FALSE) {
						$toDelete[self::PRIORITY][$nodeLink[self::PRIORITY]][$link] = TRUE;
					}
					$toDelete[self::ENTRIES][crc32($nodeLink[self::KEY])][$link] = TRUE;
					unset($node[$link]);
					$this->deletedLinks[$link] = TRUE;
				}
			} while (($data[++$i] >> self::BITROT) === $nodeId);

			$this->saveNode($nodeId, $node);
		}

		return $return;
	}



	/**
	 * Remove links to deleted keys from index.
	 * @param  array
	 */
	private function cleanFromIndex(array $toDeleteFromIndex)
	{
		foreach ($toDeleteFromIndex as $type => $toDelete) {
			ksort($toDelete);

			while (!empty($toDelete)) {
				reset($toDelete);
				$searchKey = key($toDelete);
				list($masterNodeId, $masterNode) = $this->findIndexNode($type, $searchKey);

				if (!isset($masterNode[$searchKey])) {
					if (self::$debug) {
						throw new Nette\InvalidStateException('Bad index.');
					}
					unset($toDelete[$searchKey]);
					continue;
				}

				foreach ($toDelete as $key => $links) {
					if (isset($masterNode[$key])) {
						foreach ($links as $link => $foo) {
							if (isset($masterNode[$key][$link])) {
								unset($masterNode[$key][$link], $links[$link]);
							}
						}

						if (!empty($links) && isset($masterNode[$key][self::INDEX_DATA])) {
							$this->cleanIndexData($masterNode[$key][self::INDEX_DATA], $links, $masterNode[$key]);
						}

						if (empty($masterNode[$key])) {
							unset($masterNode[$key]);
						}
						unset($toDelete[$key]);
					} else {
						break;
					}
				}
				$this->saveNode($masterNodeId, $masterNode);
			}
		}
	}



	/**
	 * Merge data with index data in other nodes.
	 * @param  array
	 * @return array of merged items
	 */
	private function mergeIndexData(array $data)
	{
		while (isset($data[self::INDEX_DATA])) {
			$id = $data[self::INDEX_DATA];
			unset($data[self::INDEX_DATA]);
			$childNode = $this->getNode($id);

			if ($childNode === FALSE) {
				if (self::$debug) {
					throw new Nette\InvalidStateException("Cannot load node number $id.");
				}
				break;
			}

			$this->arrayAppendKeys($data, $childNode[self::INDEX_DATA]);
		}

		return $data;
	}



	/**
	 * Cleans links from other nodes.
	 * @param  int
	 * @param  array
	 * @param  array
	 * @return void
	 */
	private function cleanIndexData($nextNodeId, array $links, &$masterNodeLink)
	{
		$prev = -1;

		while ($nextNodeId && !empty($links)) {
			$nodeId = $nextNodeId;
			$node = $this->getNode($nodeId);

			if ($node === FALSE) {
				if (self::$debug) {
					throw new Nette\InvalidStateException("Cannot load node number $nodeId.");
				}
				break;
			}

			foreach ($links as $link => $foo) {
				if (isset($node[self::INDEX_DATA][$link])) {
					unset($node[self::INDEX_DATA][$link], $links[$link]);
				}
			}

			if (isset($node[self::INDEX_DATA][self::INDEX_DATA])) {
				$nextNodeId = $node[self::INDEX_DATA][self::INDEX_DATA];
			} else {
				$nextNodeId = FALSE;
			}

			if (empty($node[self::INDEX_DATA]) || (count($node[self::INDEX_DATA]) === 1 && $nextNodeId)) {
				if ($prev === -1) {
					if ($nextNodeId === FALSE) {
						unset($masterNodeLink[self::INDEX_DATA]);
					} else {
						$masterNodeLink[self::INDEX_DATA] = $nextNodeId;
					}
				} else {
					$prevNode = $this->getNode($prev);
					if ($prevNode === FALSE) {
						if (self::$debug) {
							throw new Nette\InvalidStateException("Cannot load node number $prev.");
						}
					} else {
						if ($nextNodeId === FALSE) {
							unset($prevNode[self::INDEX_DATA][self::INDEX_DATA]);
							if (empty($prevNode[self::INDEX_DATA])) {
								unset($prevNode[self::INDEX_DATA]);
							}
						} else {
							$prevNode[self::INDEX_DATA][self::INDEX_DATA] = $nextNodeId;
						}

						$this->saveNode($prev, $prevNode);
					}
				}
				unset($node[self::INDEX_DATA]);
			} else {
				$prev = $nodeId;
			}

			$this->saveNode($nodeId, $node);
		}
	}



	/**
	 * Get node from journal.
	 * @param  integer
	 * @return array
	 */
	private function getNode($id)
	{
		// Load from cache
		if (isset($this->nodeCache[$id])) {
			return $this->nodeCache[$id];
		}

		$binary = stream_get_contents($this->handle, self::NODE_SIZE, self::HEADER_SIZE + self::NODE_SIZE * $id);

		if (empty($binary)) {
			// empty node, no Exception
			return FALSE;
		}

		list(, $magic, $lenght) = unpack('N2', $binary);
		if ($magic !== self::INDEX_MAGIC && $magic !== self::DATA_MAGIC) {
			if (!empty($magic)) {
				if (self::$debug) {
					throw new Nette\InvalidStateException("Node $id has malformed header.");
				}
				$this->deleteNode($id);
			}
			return FALSE;
		}

		$data = substr($binary, 2 * self::INT32_SIZE, $lenght - 2 * self::INT32_SIZE);

		$node = @unserialize($data); // intentionally @
		if ($node === FALSE) {
			$this->deleteNode($id);
			if (self::$debug) {
				throw new Nette\InvalidStateException("Cannot deserialize node number $id.");
			}
			return FALSE;
		}

		// Save to cache and return
		return $this->nodeCache[$id] = $node;
	}



	/**
	 * Save node to cache.
	 * @param  integer
	 * @param  array
	 * @return void
	 */
	private function saveNode($id, array $node)
	{
		if (count($node) === 1) { // Nod contains only INFO
			$nodeInfo = $node[self::INFO];
			if ($nodeInfo[self::TYPE] !== self::DATA) {

				if ($nodeInfo[self::END] !== -1) {
					$this->nodeCache[$id] = $node;
					$this->nodeChanged[$id] = TRUE;
					return;
				}

				if ($nodeInfo[self::MAX] === -1) {
					$max = PHP_INT_MAX;
				} else {
					$max = $nodeInfo[self::MAX];
				}

				list(, , $parentId) = $this->findIndexNode($nodeInfo[self::TYPE], $max, $id);
				if ($parentId !== -1 && $parentId !== $id) {
					$parentNode = $this->getNode($parentId);
					if ($parentNode === FALSE) {
						if (self::$debug) {
							throw new Nette\InvalidStateException("Cannot load node number $parentId.");
						}
					} else {
						if ($parentNode[self::INFO][self::END] === $id) {
							if (count($parentNode) === 1) {
								$parentNode[self::INFO][self::END] = -1;
							} else {
								end($parentNode);
								$lastKey = key($parentNode);
								$parentNode[self::INFO][self::END] = $parentNode[$lastKey];
								unset($parentNode[$lastKey]);
							}
						} else {
							unset($parentNode[$nodeInfo[self::MAX]]);
						}

						$this->saveNode($parentId, $parentNode);
					}
				}

				if ($nodeInfo[self::TYPE] === self::PRIORITY) { // only priority tree has link to prevNode
					if ($nodeInfo[self::MAX] === -1) {
						if ($nodeInfo[self::PREV_NODE] !== -1) {
							$prevNode = $this->getNode($nodeInfo[self::PREV_NODE]);
							if ($prevNode === FALSE) {
								if (self::$debug) {
									throw new Nette\InvalidStateException('Cannot load node number ' . $nodeInfo[self::PREV_NODE] . '.');
								}
							} else {
								$prevNode[self::INFO][self::MAX] = -1;
								$this->saveNode($nodeInfo[self::PREV_NODE], $prevNode);
							}
						}
					} else {
						list($nextId, $nextNode) = $this->findIndexNode($nodeInfo[self::TYPE], $nodeInfo[self::MAX] + 1, NULL, $id);
						if ($nextId !== -1 && $nextId !== $id) {
							$nextNode[self::INFO][self::PREV_NODE] = $nodeInfo[self::PREV_NODE];
							$this->saveNode($nextId, $nextNode);
						}
					}
				}
			}
			$this->nodeCache[$id] = FALSE;
		} else {
			$this->nodeCache[$id] = $node;
		}
		$this->nodeChanged[$id] = TRUE;
	}



	/**
	 * Commit all changed nodes from cache to journal file.
	 * @return void
	 */
	private function commit()
	{
		do {
			foreach ($this->nodeChanged as $id => $foo) {
				if ($this->prepareNode($id, $this->nodeCache[$id])) {
					unset($this->nodeChanged[$id]);
				}
			}
		} while (!empty($this->nodeChanged));

		foreach ($this->toCommit as $node => $str) {
			$this->commitNode($node, $str);
		}
		$this->toCommit = array();
	}



	/**
	 * Prepare node to journal file structure.
	 * @param  integer
	 * @param  array|bool
	 * @return bool Sucessfully commited
	 */
	private function prepareNode($id, $node)
	{
		if ($node === FALSE) {
			if ($id < $this->lastNode) {
				$this->lastNode = $id;
			}
			unset($this->nodeCache[$id]);
			unset($this->dataNodeFreeSpace[$id]);
			$this->deleteNode($id);
			return TRUE;
		}

		$data = serialize($node);
		$dataSize = strlen($data) + 2 * self::INT32_SIZE;

		$isData = $node[self::INFO][self::TYPE] === self::DATA;
		if ($dataSize > self::NODE_SIZE) {
			if ($isData) {
				throw new Nette\InvalidStateException('Saving node is bigger than maximum node size.');
			} else {
				$this->bisectNode($id, $node);
				return FALSE;
			}
		}

		$this->toCommit[$id] = pack('N2', $isData ? self::DATA_MAGIC : self::INDEX_MAGIC, $dataSize) . $data;

		if ($this->lastNode < $id) {
			$this->lastNode = $id;
		}
		if ($isData) {
			$this->dataNodeFreeSpace[$id] = self::NODE_SIZE - $dataSize;
		}

		return TRUE;
	}



	/**
	 * Commit node string to journal file.
	 * @param  integer
	 * @param  string
	 * @return void
	 */
	private function commitNode($id, $str)
	{
		fseek($this->handle, self::HEADER_SIZE + self::NODE_SIZE * $id);
		$writen = fwrite($this->handle, $str);
		if ($writen === FALSE) {
			throw new Nette\InvalidStateException("Cannot write node number $id to journal.");
		}
	}



	/**
	 * Find right node in B+tree. .
	 * @param  string Tree type (TAGS, PRIORITY or ENTRIES)
	 * @param  int    Searched item
	 * @return array Node
	 */
	private function findIndexNode($type, $search, $childId = NULL, $prevId = NULL)
	{
		$nodeId = self::$startNode[$type];

		$parentId = -1;
		while (TRUE) {
			$node = $this->getNode($nodeId);

			if ($node === FALSE) {
				return array(
					$nodeId,
					array(
						self::INFO => array(
							self::TYPE => $type,
							self::IS_LEAF => TRUE,
							self::PREV_NODE => -1,
							self::END => -1,
							self::MAX => -1,
						)
					),
					$parentId,
				); // Init empty node
			}

			if ($node[self::INFO][self::IS_LEAF] || $nodeId === $childId || $node[self::INFO][self::PREV_NODE] === $prevId) {
				return array($nodeId, $node, $parentId);
			}

			$parentId = $nodeId;

			if (isset($node[$search])) {
				$nodeId = $node[$search];
			} else {
				foreach ($node as $key => $childNode) {
					if ($key > $search and $key !== self::INFO) {
						$nodeId = $childNode;
						continue 2;
					}
				}

				$nodeId = $node[self::INFO][self::END];
			}
		}
	}



	/**
	 * Find complete free node.
	 * @param  integer
	 * @return array|integer Node ID
	 */
	private function findFreeNode($count = 1)
	{
		$id = $this->lastNode;
		$nodesId = array();

		do {
			if (isset($this->nodeCache[$id])) {
				++$id;
				continue;
			}

			$offset = self::HEADER_SIZE + self::NODE_SIZE * $id;

			$binary = stream_get_contents($this->handle, self::INT32_SIZE, $offset);

			if (empty($binary)) {
				$nodesId[] = $id;
			} else {
				list(, $magic) = unpack('N', $binary);
				if ($magic !== self::INDEX_MAGIC && $magic !== self::DATA_MAGIC) {
					$nodesId[] = $id;
				}
			}

			++$id;
		} while (count($nodesId) !== $count);

		if ($count === 1) {
			return $nodesId[0];
		} else {
			return $nodesId;
		}
	}



	/**
	 * Find free data node that has $size bytes of free space.
	 * @param  integer size in bytes
	 * @return integer Node ID
	 */
	private function findFreeDataNode($size)
	{
		foreach ($this->dataNodeFreeSpace as $id => $freeSpace) {
			if ($freeSpace > $size) {
				return $id;
			}
		}

		$id = self::$startNode[self::DATA];
		while (TRUE) {
			if (isset($this->dataNodeFreeSpace[$id]) || isset($this->nodeCache[$id])) {
				++$id;
				continue;
			}

			$offset = self::HEADER_SIZE + self::NODE_SIZE * $id;
			$binary = stream_get_contents($this->handle, 2 * self::INT32_SIZE, $offset);

			if (empty($binary)) {
				$this->dataNodeFreeSpace[$id] = self::NODE_SIZE;
				return $id;
			}

			list(, $magic, $nodeSize) = unpack('N2', $binary);
			if (empty($magic)) {
				$this->dataNodeFreeSpace[$id] = self::NODE_SIZE;
				return $id;
			} elseif ($magic === self::DATA_MAGIC) {
				$freeSpace = self::NODE_SIZE - $nodeSize;
				$this->dataNodeFreeSpace[$id] = $freeSpace;

				if ($freeSpace > $size) {
					return $id;
				}
			}

			++$id;
		}
	}



	/**
	 * Bisect node or when has only one key, move part to data node.
	 * @param  integer Node ID
	 * @param  array Node
	 * @return void
	 */
	private function bisectNode($id, array $node)
	{
		$nodeInfo = $node[self::INFO];
		unset($node[self::INFO]);

		if (count($node) === 1) {
			$key = key($node);

			$dataId = $this->findFreeDataNode(self::NODE_SIZE);
			$this->saveNode($dataId, array(
				self::INDEX_DATA => $node[$key],
				self::INFO => array(
					self::TYPE => self::DATA,
					self::LAST_INDEX => ($dataId << self::BITROT),
			)));

			unset($node[$key]);
			$node[$key][self::INDEX_DATA] = $dataId;
			$node[self::INFO] = $nodeInfo;

			$this->saveNode($id, $node);
			return;
		}

		ksort($node);
		$halfCount = ceil(count($node) / 2);

		list($first, $second) = array_chunk($node, $halfCount, TRUE);

		end($first);
		$halfKey = key($first);

		if ($id <= 2) { // Root
			list($firstId, $secondId) = $this->findFreeNode(2);

			$first[self::INFO] = array(
				self::TYPE => $nodeInfo[self::TYPE],
				self::IS_LEAF => $nodeInfo[self::IS_LEAF],
				self::PREV_NODE => -1,
				self::END => -1,
				self::MAX => $halfKey,
			);
			$this->saveNode($firstId, $first);

			$second[self::INFO] = array(
				self::TYPE => $nodeInfo[self::TYPE],
				self::IS_LEAF => $nodeInfo[self::IS_LEAF],
				self::PREV_NODE => $firstId,
				self::END => $nodeInfo[self::END],
				self::MAX => -1,
			);
			$this->saveNode($secondId, $second);

			$parentNode = array(
				self::INFO => array(
					self::TYPE => $nodeInfo[self::TYPE],
					self::IS_LEAF => FALSE,
					self::PREV_NODE => -1,
					self::END => $secondId,
					self::MAX => -1,
				),
				$halfKey => $firstId,
			);
			$this->saveNode($id, $parentNode);
		} else {
			$firstId = $this->findFreeNode();

			$first[self::INFO] = array(
				self::TYPE => $nodeInfo[self::TYPE],
				self::IS_LEAF => $nodeInfo[self::IS_LEAF],
				self::PREV_NODE => $nodeInfo[self::PREV_NODE],
				self::END => -1,
				self::MAX => $halfKey,
			);
			$this->saveNode($firstId, $first);

			$second[self::INFO] = array(
				self::TYPE => $nodeInfo[self::TYPE],
				self::IS_LEAF => $nodeInfo[self::IS_LEAF],
				self::PREV_NODE => $firstId,
				self::END => $nodeInfo[self::END],
				self::MAX => $nodeInfo[self::MAX],
			);
			$this->saveNode($id, $second);

			list(,, $parent) = $this->findIndexNode($nodeInfo[self::TYPE], $halfKey);
			$parentNode = $this->getNode($parent);
			if ($parentNode === FALSE) {
				if (self::$debug) {
					throw new Nette\InvalidStateException("Cannot load node number $parent.");
				}
			} else {
				$parentNode[$halfKey] = $firstId;
				ksort($parentNode); // Parent index must be always sorted.
				$this->saveNode($parent, $parentNode);
			}
		}
	}



	/**
	 * Commit header to journal file.
	 * @return void
	 */
	private function headerCommit()
	{
		fseek($this->handle, self::INT32_SIZE);
		@fwrite($this->handle, pack('N', $this->lastNode));  // intentionally @, save is not necceseary
	}



	/**
	 * Remove node from journal file.
	 * @param  integer
	 * @return void
	 */
	private function deleteNode($id)
	{
		fseek($this->handle, 0, SEEK_END);
		$end = ftell($this->handle);

		if ($end <= (self::HEADER_SIZE + self::NODE_SIZE * ($id + 1))) {
			$packedNull = pack('N', 0);

			do {
				$binary = stream_get_contents($this->handle, self::INT32_SIZE, (self::HEADER_SIZE + self::NODE_SIZE * --$id));
			} while (empty($binary) || $binary === $packedNull);

			if (!ftruncate($this->handle, self::HEADER_SIZE + self::NODE_SIZE * ($id + 1))) {
				throw new Nette\InvalidStateException('Cannot truncate journal file.');
			}
		} else {
			fseek($this->handle, self::HEADER_SIZE + self::NODE_SIZE * $id);
			$writen = fwrite($this->handle, pack('N', 0));
			if ($writen !== self::INT32_SIZE) {
				throw new Nette\InvalidStateException("Cannot delete node number $id from journal.");
			}
		}
	}



	/**
	 * Complete delete all nodes from file.
	 * @return void
	 */
	private function deleteAll()
	{
		if (!ftruncate($this->handle, self::HEADER_SIZE)) {
			throw new Nette\InvalidStateException('Cannot truncate journal file.');
		}
	}



	/**
	 * Lock file for writing and reading and delete node cache when file has changed.
	 * @return void
	 */
	private function lock()
	{
		if (!$this->handle) {
			throw new Nette\InvalidStateException('File journal file is not opened');
		}

		if (!flock($this->handle, LOCK_EX)) {
			throw new Nette\InvalidStateException('Cannot acquire exclusive lock on journal.');
		}

		if ($this->lastModTime !== NULL) {
			clearstatcache();
			if ($this->lastModTime < @filemtime($this->file)) { // intentionally @
				$this->nodeCache = $this->dataNodeFreeSpace = array();
			}
		}
	}



	/**
	 * Unlock file and save last modified time.
	 * @return void
	 */
	private function unlock()
	{
		if ($this->handle) {
			fflush($this->handle);
			flock($this->handle, LOCK_UN);
			clearstatcache();
			$this->lastModTime = @filemtime($this->file); // intentionally @
		}
	}



	/**
	 * Append $append to $array.
	 * This function is much faster then $array = array_merge($array, $append)
	 * @param  array
	 * @param  array
	 * @return void
	 */
	private function arrayAppend(array &$array, array $append)
	{
		foreach ($append as $value) {
			$array[] = $value;
		}
	}



	/**
	 * Append $append to $array with preserve keys.
	 * This function is much faster then $array = $array + $append
	 * @param  array
	 * @param  array
	 * @return void
	 */
	private function arrayAppendKeys(array &$array, array $append)
	{
		foreach ($append as $key => $value) {
			$array[$key] = $value;
		}
	}

}
