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
 * Database helpers.
 *
 * @author     David Grudl
 */
class Helpers
{
	/** @var array */
	public static $typePatterns = array(
		'^_' => IReflection::FIELD_TEXT, // PostgreSQL arrays
		'BYTEA|BLOB|BIN' => IReflection::FIELD_BINARY,
		'TEXT|CHAR' => IReflection::FIELD_TEXT,
		'YEAR|BYTE|COUNTER|SERIAL|INT|LONG' => IReflection::FIELD_INTEGER,
		'CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER' => IReflection::FIELD_FLOAT,
		'^TIME$' => IReflection::FIELD_TIME,
		'TIME' => IReflection::FIELD_DATETIME, // DATETIME, TIMESTAMP
		'DATE' => IReflection::FIELD_DATE,
		'BOOL|BIT' => IReflection::FIELD_BOOL,
	);



	/**
	 * Displays complete result set as HTML table for debug purposes.
	 * @return void
	 */
	public static function dumpResult(Statement $statement)
	{
		echo "\n<table class=\"dump\">\n<caption>" . htmlSpecialChars($statement->queryString) . "</caption>\n";
		if (!$statement->columnCount()) {
			echo "\t<tr>\n\t\t<th>Affected rows:</th>\n\t\t<td>", $statement->rowCount(), "</td>\n\t</tr>\n</table>\n";
			return;
		}
		$i = 0;
		foreach ($statement as $row) {
			if ($i === 0) {
				echo "<thead>\n\t<tr>\n\t\t<th>#row</th>\n";
				foreach ($row as $col => $foo) {
					echo "\t\t<th>" . htmlSpecialChars($col) . "</th>\n";
				}
				echo "\t</tr>\n</thead>\n<tbody>\n";
			}
			echo "\t<tr>\n\t\t<th>", $i, "</th>\n";
			foreach ($row as $col) {
				//if (is_object($col)) $col = $col->__toString();
				echo "\t\t<td>", htmlSpecialChars($col), "</td>\n";
			}
			echo "\t</tr>\n";
			$i++;
		}

		if ($i === 0) {
			echo "\t<tr>\n\t\t<td><em>empty result set</em></td>\n\t</tr>\n</table>\n";
		} else {
			echo "</tbody>\n</table>\n";
		}
	}



	/**
	 * Returns syntax highlighted SQL command.
	 * @param  string
	 * @return string
	 */
	public static function dumpSql($sql)
	{
		static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|RLIKE|REGEXP|TRUE|FALSE';

		// insert new lines
		$sql = " $sql ";
		$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

		// reduce spaces
		$sql = preg_replace('#[ \t]{2,}#', " ", $sql);

		$sql = wordwrap($sql, 100);
		$sql = preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

		// syntax highlight
		$sql = htmlSpecialChars($sql);
		$sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", function($matches) {
			if (!empty($matches[1])) // comment
				return '<em style="color:gray">' . $matches[1] . '</em>';

			if (!empty($matches[2])) // error
				return '<strong style="color:red">' . $matches[2] . '</strong>';

			if (!empty($matches[3])) // most important keywords
				return '<strong style="color:blue">' . $matches[3] . '</strong>';

			if (!empty($matches[4])) // other keywords
				return '<strong style="color:green">' . $matches[4] . '</strong>';
		}, $sql);

		return '<pre class="dump">' . trim($sql) . "</pre>\n";
	}



	/**
	 * Heuristic type detection.
	 * @param  string
	 * @return string
	 * @internal
	 */
	public static function detectType($type)
	{
		static $cache;
		if (!isset($cache[$type])) {
			$cache[$type] = 'string';
			foreach (self::$typePatterns as $s => $val) {
				if (preg_match("#$s#i", $type)) {
					return $cache[$type] = $val;
				}
			}
		}
		return $cache[$type];
	}



	/**
	 * Import SQL dump from file - extreme fast.
	 * @return int  count of commands
	 */
	public static function loadFromFile(Connection $connection, $file)
	{
		@set_time_limit(0); // intentionally @

		$handle = @fopen($file, 'r'); // intentionally @
		if (!$handle) {
			throw new Nette\FileNotFoundException("Cannot open file '$file'.");
		}

		$count = 0;
		$sql = '';
		while (!feof($handle)) {
			$s = fgets($handle);
			$sql .= $s;
			if (substr(rtrim($s), -1) === ';') {
				$connection->exec($sql); // native query without logging
				$sql = '';
				$count++;
			}
		}
		if (trim($sql) !== '') {
			$connection->exec($sql);
			$count++;
		}
		fclose($handle);
		return $count;
	}

}
