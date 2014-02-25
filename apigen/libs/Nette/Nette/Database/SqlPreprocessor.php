<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Database;

use Nette;



/**
 * SQL preprocessor.
 *
 * @author     David Grudl
 */
class SqlPreprocessor extends Nette\Object
{
	/** @var Connection */
	private $connection;

	/** @var ISupplementalDriver */
	private $driver;

	/** @var array of input parameters */
	private $params;

	/** @var array of parameters to be processed by PDO */
	private $remaining;

	/** @var int */
	private $counter;

	/** @var string values|assoc|multi */
	private $arrayMode;



	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->driver = $connection->getSupplementalDriver();
	}



	/**
	 * @param  string
	 * @param  array
	 * @return array of [sql, params]
	 */
	public function process($sql, $params)
	{
		$this->params = $params;
		$this->counter = 0;
		$this->remaining = array();
		$this->arrayMode = 'assoc';

		$sql = Nette\Utils\Strings::replace($sql, '~\'.*?\'|".*?"|\?|\b(?:INSERT|REPLACE|UPDATE)\b~si', array($this, 'callback'));

		while ($this->counter < count($params)) {
			$sql .= ' ' . $this->formatValue($params[$this->counter++]);
		}

		return array($sql, $this->remaining);
	}



	/** @internal */
	public function callback($m)
	{
		$m = $m[0];
		if ($m[0] === "'" || $m[0] === '"') { // string
			return $m;

		} elseif ($m === '?') { // placeholder
			return $this->formatValue($this->params[$this->counter++]);

		} else { // INSERT, REPLACE, UPDATE
			$this->arrayMode = strtoupper($m) === 'UPDATE' ? 'assoc' : 'values';
			return $m;
		}
	}



	private function formatValue($value)
	{
		if (is_string($value)) {
			if (strlen($value) > 20) {
				$this->remaining[] = $value;
				return '?';

			} else {
				return $this->connection->quote($value);
			}

		} elseif (is_int($value)) {
			return (string) $value;

		} elseif (is_float($value)) {
			return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

		} elseif (is_bool($value)) {
			$this->remaining[] = $value;
			return '?';

		} elseif ($value === NULL) {
			return 'NULL';

		} elseif ($value instanceof Table\ActiveRow) {
			return $value->getPrimary();

		} elseif (is_array($value) || $value instanceof \Traversable) {
			$vx = $kx = array();

			if (isset($value[0])) { // non-associative; value, value, value
				foreach ($value as $v) {
					$vx[] = $this->formatValue($v);
				}
				return implode(', ', $vx);

			} elseif ($this->arrayMode === 'values') { // (key, key, ...) VALUES (value, value, ...)
				$this->arrayMode = 'multi';
				foreach ($value as $k => $v) {
					$kx[] = $this->driver->delimite($k);
					$vx[] = $this->formatValue($v);
				}
				return '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';

			} elseif ($this->arrayMode === 'assoc') { // key=value, key=value, ...
				foreach ($value as $k => $v) {
					$vx[] = $this->driver->delimite($k) . '=' . $this->formatValue($v);
				}
				return implode(', ', $vx);

			} elseif ($this->arrayMode === 'multi') { // multiple insert (value, value, ...), ...
				foreach ($value as $k => $v) {
					$vx[] = $this->formatValue($v);
				}
				return '(' . implode(', ', $vx) . ')';
			}

		} elseif ($value instanceof \DateTime) {
			return $this->driver->formatDateTime($value);

		} elseif ($value instanceof SqlLiteral) {
			return $value->__toString();

		} else {
			$this->remaining[] = $value;
			return '?';
		}
	}

}
