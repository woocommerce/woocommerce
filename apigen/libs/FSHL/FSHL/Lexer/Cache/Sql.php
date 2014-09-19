<?php

/**
 * FSHL 2.1.0                                  | Fast Syntax HighLighter |
 * -----------------------------------------------------------------------
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

namespace FSHL\Lexer\Cache;

/**
 * Optimized and cached Sql lexer.
 *
 * This file is generated. All changes made in this file will be lost.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 * @see \FSHL\Generator
 * @see \FSHL\Lexer\Sql
 */
class Sql
{
	/**
	 * Language name.
	 *
	 * @var array
	 */
	public $language;

	/**
	 * Transitions table.
	 *
	 * @var array
	 */
	public $trans;

	/**
	 * Id of the initial state.
	 *
	 * @var integer
	 */
	public $initialState;

	/**
	 * Id of the return state.
	 *
	 * @var integer
	 */
	public $returnState;

	/**
	 * Id of the quit state.
	 *
	 * @var integer
	 */
	public $quitState;

	/**
	 * List of flags for all states.
	 *
	 * @var array
	 */
	public $flags;

	/**
	 * Data for all states.
	 *
	 * @var array
	 */
	public $data;

	/**
	 * List of CSS classes.
	 *
	 * @var array
	 */
	public $classes;

	/**
	 * List of keywords.
	 *
	 * @var array
	 */
	public $keywords;

	/**
	 * Initializes the lexer.
	 */
	public function __construct()
	{
		$this->language = 'Sql';
		$this->trans = array(
			0 => array(
				0 => array(
					0 => 1, 1 => -1
				), 1 => array(
					0 => 7, 1 => 1
				), 2 => array(
					0 => 7, 1 => 1
				), 3 => array(
					0 => 2, 1 => 1
				), 4 => array(
					0 => 3, 1 => 1
				), 5 => array(
					0 => 3, 1 => 1
				), 6 => array(
					0 => 3, 1 => 1
				), 7 => array(
					0 => 4, 1 => 1
				), 8 => array(
					0 => 5, 1 => 1
				), 9 => array(
					0 => 6, 1 => 1
				), 10 => array(
					0 => 0, 1 => 1
				), 11 => array(
					0 => 0, 1 => 1
				)
			), 1 => array(
				0 => array(
					0 => 10, 1 => -1
				)
			), 2 => array(
				0 => array(
					0 => 2, 1 => 1
				), 1 => array(
					0 => 2, 1 => 1
				), 2 => array(
					0 => 10, 1 => 0
				)
			), 3 => array(
				0 => array(
					0 => 10, 1 => -1
				), 1 => array(
					0 => 3, 1 => 1
				)
			), 4 => array(
				0 => array(
					0 => 10, 1 => 0
				), 1 => array(
					0 => 4, 1 => 1
				), 2 => array(
					0 => 4, 1 => 1
				), 3 => array(
					0 => 4, 1 => 1
				)
			), 5 => array(
				0 => array(
					0 => 10, 1 => 0
				), 1 => array(
					0 => 5, 1 => 1
				), 2 => array(
					0 => 5, 1 => 1
				), 3 => array(
					0 => 5, 1 => 1
				)
			), 6 => array(
				0 => array(
					0 => 10, 1 => 0
				), 1 => array(
					0 => 6, 1 => 1
				), 2 => array(
					0 => 6, 1 => 1
				), 3 => array(
					0 => 6, 1 => 1
				)
			), 7 => array(
				0 => array(
					0 => 8, 1 => 1
				), 1 => array(
					0 => 7, 1 => 1
				), 2 => array(
					0 => 10, 1 => -1
				)
			), 8 => array(
				0 => array(
					0 => 10, 1 => -1
				)
			), 9 => array(
				0 => array(
					0 => 9, 1 => 1
				), 1 => array(
					0 => 9, 1 => 1
				), 2 => array(
					0 => 9, 1 => 1
				), 3 => array(
					0 => 9, 1 => 1
				), 4 => array(
					0 => 9, 1 => 1
				)
			)
		);
		$this->initialState = 0;
		$this->returnState = 10;
		$this->quitState = 11;
		$this->flags = array(
			0 => 1, 1 => 5, 2 => 4, 3 => 4, 4 => 4, 5 => 4, 6 => 4, 7 => 4, 8 => 0, 9 => 4
		);
		$this->data = array(
			0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => NULL, 6 => NULL, 7 => NULL, 8 => NULL, 9 => NULL
		);
		$this->classes = array(
			0 => NULL, 1 => NULL, 2 => 'sql-comment', 3 => 'sql-comment', 4 => 'sql-value', 5 => 'sql-value', 6 => 'sql-value', 7 => 'sql-num', 8 => 'sql-num', 9 => 'sql-option'
		);
		$this->keywords = array(
			0 => 'sql-keyword', 1 => array(
				'a' => 1, 'abs' => 2, 'acos' => 2, 'add' => 1, 'add_months' => 1, 'after' => 1, 'all' => 1, 'alter' => 1, 'an' => 1, 'and' => 1, 'any' => 1, 'array' => 1, 'as' => 1, 'asc' => 1, 'ascii' => 2, 'asin' => 2, 'atan' => 2, 'atan2' => 2, 'avg' => 2, 'before' => 1, 'begin' => 1, 'between' => 1, 'bigint' => 3, 'binary' => 1, 'bind' => 1, 'binding' => 1,
			'bit' => 1, 'blob' => 3, 'boolean' => 3, 'by' => 1, 'call' => 1, 'cascade' => 1, 'case' => 1, 'cast' => 1, 'ceiling' => 2, 'char' => 3, 'char_length' => 2, 'character' => 2, 'character_length' => 2, 'chartorowid' => 1, 'check' => 1, 'chr' => 1, 'cleanup' => 1, 'close' => 1, 'clustered' => 1, 'coalesce' => 1, 'colgroup' => 1, 'collate' => 1, 'commit' => 1, 'complex' => 1, 'compress' => 1, 'concat' => 2,
			'connect' => 1, 'constraint' => 1, 'contains' => 1, 'continue' => 1, 'convert' => 1, 'cos' => 2, 'count' => 2, 'create' => 1, 'cross' => 1, 'curdate' => 2, 'current' => 1, 'cursor' => 1, 'curtime' => 2, 'cvar' => 1, 'database' => 1, 'datapages' => 1, 'date' => 2, 'dayname' => 2, 'dayofmonth' => 2, 'dayofweek' => 2, 'dayofyear' => 2, 'db_name' => 1, 'dba' => 1, 'dec' => 3, 'decimal' => 3, 'declaration' => 1,
			'declare' => 1, 'decode' => 2, 'default' => 1, 'definition' => 1, 'degrees' => 1, 'delete' => 1, 'desc' => 1, 'describe' => 1, 'descriptor' => 1, 'dhtype' => 1, 'difference' => 1, 'distinct' => 1, 'double' => 3, 'drop' => 1, 'each' => 1, 'else' => 1, 'end' => 1, 'escape' => 1, 'exclusive' => 1, 'exec' => 1, 'execute' => 1, 'exists' => 1, 'exit' => 1, 'exp' => 2, 'explicit' => 1, 'extent' => 1,
			'fetch' => 1, 'field file' => 1, 'float' => 3, 'floor' => 2, 'for' => 1, 'foreign' => 1, 'found' => 1, 'from' => 1, 'full' => 1, 'go' => 1, 'goto' => 1, 'grant' => 1, 'greatest' => 2, 'group' => 1, 'hash' => 1, 'having' => 1, 'hour' => 1, 'identified' => 1, 'ifnull' => 2, 'immediate' => 1, 'in' => 1, 'index' => 1, 'indexpages' => 1, 'indicator' => 1, 'initcap' => 1, 'inner' => 1,
			'inout' => 1, 'input' => 1, 'insert' => 1, 'instr' => 1, 'int' => 3, 'integer' => 3, 'interface' => 1, 'intersect' => 1, 'into' => 1, 'is' => 1, 'isnull' => 2, 'java_object' => 3, 'join' => 1, 'key' => 1, 'last_day' => 2, 'lcase' => 2, 'least' => 2, 'left' => 2, 'length' => 2, 'like' => 1, 'link' => 1, 'list' => 1, 'locate' => 1, 'lock' => 1, 'log' => 2, 'log10' => 2,
			'long' => 1, 'longblob' => 3, 'longtext' => 3, 'longvarbinary' => 3, 'longvarchar' => 3, 'lower' => 1, 'lpad' => 1, 'ltrim' => 2, 'lvarbinary' => 1, 'lvarchar' => 1, 'main' => 1, 'max' => 2, 'mediumint' => 3, 'metadata_only' => 1, 'min' => 2, 'minus' => 2, 'minute' => 2, 'mod' => 2, 'mode' => 1, 'modify' => 1, 'money' => 1, 'month' => 2, 'monthname' => 2, 'months_between' => 2, 'name' => 1, 'national' => 1,
			'natural' => 1, 'nchar' => 1, 'newrow' => 1, 'next_day' => 1, 'nocompress' => 1, 'not' => 1, 'now' => 1, 'nowait' => 1, 'null' => 1, 'nullif' => 1, 'nullvalue' => 1, 'number' => 1, 'numeric' => 1, 'nvl' => 1, 'object_id' => 1, 'odbc_convert' => 1, 'odbcinfo' => 1, 'of' => 1, 'oldrow' => 1, 'on' => 1, 'open' => 1, 'option' => 1, 'or' => 1, 'order' => 1, 'out' => 1, 'outer' => 1,
			'output' => 1, 'pctfree' => 1, 'pi' => 1, 'power' => 1, 'precision' => 1, 'prefix' => 1, 'prepare' => 1, 'primary' => 1, 'privileges' => 1, 'procedure' => 1, 'public' => 1, 'quarter' => 2, 'radians' => 2, 'rand' => 2, 'range' => 2, 'raw' => 1, 'real' => 3, 'record' => 1, 'references' => 1, 'referencing' => 1, 'rename' => 1, 'repeat' => 2, 'replace' => 1, 'resource' => 1, 'restrict' => 1, 'result' => 1,
			'return' => 2, 'revoke' => 2, 'right' => 2, 'rollback' => 1, 'row' => 2, 'rowid' => 2, 'rowidtochar' => 2, 'rownum' => 2, 'rpad' => 2, 'rtrim' => 2, 'searched_case' => 1, 'second' => 1, 'section' => 1, 'select' => 1, 'service' => 1, 'set' => 1, 'share' => 1, 'short' => 1, 'sign' => 1, 'simple_case' => 1, 'sin' => 2, 'size' => 2, 'smallint' => 3, 'some' => 1, 'soundex' => 1, 'space' => 1,
			'sql' => 1, 'sql_bigint' => 3, 'sql_binary' => 3, 'sql_bit' => 3, 'sql_char' => 3, 'sql_date' => 3, 'sql_decimal' => 3, 'sql_double' => 3, 'sql_float' => 1, 'sql_integer' => 3, 'sql_longvarbinary' => 3, 'sql_longvarchar' => 3, 'sql_numeric' => 3, 'sql_real' => 3, 'sql_smallint' => 3, 'sql_time' => 3, 'sql_timestamp' => 1, 'sql_tinyint' => 3, 'sql_tsi_day' => 3, 'sql_tsi_frac_second' => 3, 'sql_tsi_hour' => 3, 'sql_tsi_minute' => 3, 'sql_tsi_month' => 3, 'sql_tsi_quarter' => 3, 'sql_tsi_second' => 3, 'sql_tsi_week' => 3,
			'sql_tsi_year' => 3, 'sql_varbinary' => 3, 'sql_varchar' => 3, 'sqlerror' => 1, 'sqlwarning' => 1, 'sqrt' => 1, 'start' => 1, 'statement' => 1, 'statistics' => 1, 'stop' => 1, 'storage_attributes' => 1, 'storage_manager' => 1, 'store_in_progress' => 1, 'string' => 3, 'substr' => 2, 'substring' => 2, 'suffix' => 2, 'sum' => 2, 'suser_name' => 2, 'synonym' => 2, 'sysdate' => 2, 'systime' => 2, 'systimestamp' => 2, 'table' => 1, 'tan' => 2, 'text' => 3,
			'then' => 1, 'time' => 2, 'timeout' => 2, 'timestamp' => 3, 'timestampadd' => 2, 'timestampdiff' => 2, 'tinyint' => 3, 'to' => 2, 'to_char' => 2, 'to_date' => 2, 'to_number' => 2, 'to_time' => 2, 'to_timestamp' => 2, 'top' => 1, 'tpe' => 1, 'tran' => 1, 'transaction' => 1, 'translate' => 1, 'trigger' => 1, 'type' => 1, 'ucase' => 1, 'uid' => 1, 'union' => 1, 'unique' => 1, 'unsigned' => 1, 'update' => 1,
			'upper' => 1, 'user' => 1, 'user_id' => 1, 'user_name' => 1, 'using' => 1, 'uuid' => 1, 'values' => 1, 'varbinary' => 1, 'varchar' => 3, 'variables' => 1, 'varying' => 1, 'version' => 1, 'view' => 1, 'week' => 2, 'when' => 1, 'whenever' => 1, 'where' => 1, 'with' => 1, 'work' => 1, 'year' => 1
			), 2 => false
		);

	}

	/**
	 * Finds a delimiter for state OUT.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter0($text, $textLength, $textPos)
	{
		static $delimiters = array(
			3 => '/*', 4 => '//', 5 => '#', 6 => '--', 7 => '"', 8 => '\'', 9 => '`', 10 => "\n", 11 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if (preg_match('~^[a-z]+~i', $part, $matches)) {
				return array(0, $matches[0], $buffer);
			}
			if (preg_match('~^\\d+~', $part, $matches)) {
				return array(1, $matches[0], $buffer);
			}
			if (preg_match('~^\.\\d+~', $part, $matches)) {
				return array(2, $matches[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			if (0 === strpos($part, $delimiters[4])) {
				return array(4, $delimiters[4], $buffer);
			}
			if ($delimiters[5] === $letter) {
				return array(5, $delimiters[5], $buffer);
			}
			if (0 === strpos($part, $delimiters[6])) {
				return array(6, $delimiters[6], $buffer);
			}
			if ($delimiters[7] === $letter) {
				return array(7, $delimiters[7], $buffer);
			}
			if ($delimiters[8] === $letter) {
				return array(8, $delimiters[8], $buffer);
			}
			if ($delimiters[9] === $letter) {
				return array(9, $delimiters[9], $buffer);
			}
			if ($delimiters[10] === $letter) {
				return array(10, $delimiters[10], $buffer);
			}
			if ($delimiters[11] === $letter) {
				return array(11, $delimiters[11], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state FUNCTION.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter1($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);

			if (preg_match('~^\\W+~', $part, $matches)) {
				return array(0, $matches[0], $buffer);
			}
			$buffer .= $text[$textPos];
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state COMMENT_BLOCK.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter2($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t", 2 => '*/'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state COMMENT_LINE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter3($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => "\n", 1 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {

			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if ($delimiters[1] === $letter) {
				return array(1, $delimiters[1], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state QUOTE_DOUBLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter4($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '"', 1 => '\\"', 2 => "\n", 3 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[1])) {
				return array(1, $delimiters[1], $buffer);
			}
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state QUOTE_SINGLE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter5($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '\'', 1 => '\\\'', 2 => "\n", 3 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[1])) {
				return array(1, $delimiters[1], $buffer);
			}
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state QUOTE_BACK_APOSTROPHE.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter6($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => '`', 1 => '\\`', 2 => "\n", 3 => "\t"
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[1])) {
				return array(1, $delimiters[1], $buffer);
			}
			if ($delimiters[2] === $letter) {
				return array(2, $delimiters[2], $buffer);
			}
			if ($delimiters[3] === $letter) {
				return array(3, $delimiters[3], $buffer);
			}
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state NUMBER.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter7($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => 'x'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);
			$letter = $text[$textPos];

			if ($delimiters[0] === $letter) {
				return array(0, $delimiters[0], $buffer);
			}
			if (preg_match('~^\.\\d+~', $part, $matches)) {
				return array(1, $matches[0], $buffer);
			}
			return array(2, $letter, $buffer);
			$buffer .= $letter;
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state HEXA.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter8($text, $textLength, $textPos)
	{

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);

			if (preg_match('~^[^a-f\\d]+~i', $part, $matches)) {
				return array(0, $matches[0], $buffer);
			}
			$buffer .= $text[$textPos];
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

	/**
	 * Finds a delimiter for state OPTION.
	 *
	 * @param string $text
	 * @param string $textLength
	 * @param string $textPos
	 * @return array
	 */
	public function findDelimiter9($text, $textLength, $textPos)
	{
		static $delimiters = array(
			0 => 'BLOB', 1 => 'TEXT', 2 => 'INTEGER', 3 => 'CHAR', 4 => 'DATE'
		);

		$buffer = false;
		while ($textPos < $textLength) {
			$part = substr($text, $textPos, 10);

			if (0 === strpos($part, $delimiters[0])) {
				return array(0, $delimiters[0], $buffer);
			}
			if (0 === strpos($part, $delimiters[1])) {
				return array(1, $delimiters[1], $buffer);
			}
			if (0 === strpos($part, $delimiters[2])) {
				return array(2, $delimiters[2], $buffer);
			}
			if (0 === strpos($part, $delimiters[3])) {
				return array(3, $delimiters[3], $buffer);
			}
			if (0 === strpos($part, $delimiters[4])) {
				return array(4, $delimiters[4], $buffer);
			}
			$buffer .= $text[$textPos];
			$textPos++;
		}
		return array(-1, -1, $buffer);
	}

}