<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Database\Reflection;

use Nette;



/**
 * Reflection metadata class for a database.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @property-write Nette\Database\Connection $connection
 */
class ConventionalReflection extends Nette\Object implements Nette\Database\IReflection
{
	/** @var string */
	protected $primary;

	/** @var string */
	protected $foreign;

	/** @var string */
	protected $table;



	/**
	 * Create conventional structure.
	 * @param  string %s stands for table name
	 * @param  string %1$s stands for key used after ->, %2$s for table name
	 * @param  string %1$s stands for key used after ->, %2$s for table name
	 */
	public function __construct($primary = 'id', $foreign = '%s_id', $table = '%s')
	{
		$this->primary = $primary;
		$this->foreign = $foreign;
		$this->table = $table;
	}



	public function getPrimary($table)
	{
		return sprintf($this->primary, $this->getColumnFromTable($table));
	}



	public function getHasManyReference($table, $key)
	{
		$table = $this->getColumnFromTable($table);
		return array(
			sprintf($this->table, $key, $table),
			sprintf($this->foreign, $table, $key),
		);
	}



	public function getBelongsToReference($table, $key)
	{
		$table = $this->getColumnFromTable($table);
		return array(
			sprintf($this->table, $key, $table),
			sprintf($this->foreign, $key, $table),
		);
	}



	public function setConnection(Nette\Database\Connection $connection)
	{}



	protected function getColumnFromTable($name)
	{
		if ($this->table !== '%s' && preg_match('(^' . str_replace('%s', '(.*)', preg_quote($this->table)) . '$)', $name, $match)) {
			return $match[1];
		}

		return $name;
	}

}
