<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette;

use Nette;



/**
 * DateTime with serialization and timestamp support for PHP 5.2.
 *
 * @author     David Grudl
 */
class DateTime extends \DateTime
{
	/** minute in seconds */
	const MINUTE = 60;

	/** hour in seconds */
	const HOUR = 3600;

	/** day in seconds */
	const DAY = 86400;

	/** week in seconds */
	const WEEK = 604800;

	/** average month in seconds */
	const MONTH = 2629800;

	/** average year in seconds */
	const YEAR = 31557600;



	/**
	 * DateTime object factory.
	 * @param  string|int|\DateTime
	 * @return DateTime
	 */
	public static function from($time)
	{
		if ($time instanceof \DateTime) {
			return new self($time->format('Y-m-d H:i:s'), $time->getTimezone());

		} elseif (is_numeric($time)) {
			if ($time <= self::YEAR) {
				$time += time();
			}
			return new static(date('Y-m-d H:i:s', $time));

		} else { // textual or NULL
			return new static($time);
		}
	}



	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}



	public function modifyClone($modify = '')
	{
		$dolly = clone $this;
		return $modify ? $dolly->modify($modify) : $dolly;
	}



	/*5.2*
	public function modify($modify)
	{
		parent::modify($modify);
		return $this;
	}



	public static function __set_state($state)
	{
		return new self($state['date'], new \DateTimeZone($state['timezone']));
	}



	public function __sleep()
	{
		$this->fix = array($this->format('Y-m-d H:i:s'), $this->getTimezone()->getName());
		return array('fix');
	}



	public function __wakeup()
	{
		$this->__construct($this->fix[0], new \DateTimeZone($this->fix[1]));
		unset($this->fix);
	}



	public function getTimestamp()
	{
		return (int) $this->format('U');
	}



	public function setTimestamp($timestamp)
	{
		return $this->__construct(
			gmdate('Y-m-d H:i:s', $timestamp + $this->getOffset()),
			new \DateTimeZone($this->getTimezone()->getName()) // simply getTimezone() crashes in PHP 5.2.6
		);
	}
	*/

}
