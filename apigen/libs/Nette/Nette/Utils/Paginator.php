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
 * Paginating math.
 *
 * @author     David Grudl
 *
 * @property   int $page
 * @property-read int $firstPage
 * @property-read int|NULL $lastPage
 * @property   int $base
 * @property-read bool $first
 * @property-read bool $last
 * @property-read int|NULL $pageCount
 * @property   int $itemsPerPage
 * @property   int|NULL $itemCount
 * @property-read int $offset
 * @property-read int|NULL $countdownOffset
 * @property-read int|NULL $length
 */
class Paginator extends Nette\Object
{
	/** @var int */
	private $base = 1;

	/** @var int */
	private $itemsPerPage = 1;

	/** @var int */
	private $page;

	/** @var int|NULL */
	private $itemCount;



	/**
	 * Sets current page number.
	 * @param  int
	 * @return Paginator  provides a fluent interface
	 */
	public function setPage($page)
	{
		$this->page = (int) $page;
		return $this;
	}



	/**
	 * Returns current page number.
	 * @return int
	 */
	public function getPage()
	{
		return $this->base + $this->getPageIndex();
	}



	/**
	 * Returns first page number.
	 * @return int
	 */
	public function getFirstPage()
	{
		return $this->base;
	}



	/**
	 * Returns last page number.
	 * @return int|NULL
	 */
	public function getLastPage()
	{
		return $this->itemCount === NULL ? NULL : $this->base + max(0, $this->getPageCount() - 1);
	}



	/**
	 * Sets first page (base) number.
	 * @param  int
	 * @return Paginator  provides a fluent interface
	 */
	public function setBase($base)
	{
		$this->base = (int) $base;
		return $this;
	}



	/**
	 * Returns first page (base) number.
	 * @return int
	 */
	public function getBase()
	{
		return $this->base;
	}



	/**
	 * Returns zero-based page number.
	 * @return int
	 */
	protected function getPageIndex()
	{
		$index = max(0, $this->page - $this->base);
		return $this->itemCount === NULL ? $index : min($index, max(0, $this->getPageCount() - 1));
	}



	/**
	 * Is the current page the first one?
	 * @return bool
	 */
	public function isFirst()
	{
		return $this->getPageIndex() === 0;
	}



	/**
	 * Is the current page the last one?
	 * @return bool
	 */
	public function isLast()
	{
		return $this->itemCount === NULL ? FALSE : $this->getPageIndex() >= $this->getPageCount() - 1;
	}



	/**
	 * Returns the total number of pages.
	 * @return int|NULL
	 */
	public function getPageCount()
	{
		return $this->itemCount === NULL ? NULL : (int) ceil($this->itemCount / $this->itemsPerPage);
	}



	/**
	 * Sets the number of items to display on a single page.
	 * @param  int
	 * @return Paginator  provides a fluent interface
	 */
	public function setItemsPerPage($itemsPerPage)
	{
		$this->itemsPerPage = max(1, (int) $itemsPerPage);
		return $this;
	}



	/**
	 * Returns the number of items to display on a single page.
	 * @return int
	 */
	public function getItemsPerPage()
	{
		return $this->itemsPerPage;
	}



	/**
	 * Sets the total number of items.
	 * @param  int (or NULL as infinity)
	 * @return Paginator  provides a fluent interface
	 */
	public function setItemCount($itemCount)
	{
		$this->itemCount = ($itemCount === FALSE || $itemCount === NULL) ? NULL : max(0, (int) $itemCount);
		return $this;
	}



	/**
	 * Returns the total number of items.
	 * @return int|NULL
	 */
	public function getItemCount()
	{
		return $this->itemCount;
	}



	/**
	 * Returns the absolute index of the first item on current page.
	 * @return int
	 */
	public function getOffset()
	{
		return $this->getPageIndex() * $this->itemsPerPage;
	}



	/**
	 * Returns the absolute index of the first item on current page in countdown paging.
	 * @return int|NULL
	 */
	public function getCountdownOffset()
	{
		return $this->itemCount === NULL
			? NULL
			: max(0, $this->itemCount - ($this->getPageIndex() + 1) * $this->itemsPerPage);
	}



	/**
	 * Returns the number of items on current page.
	 * @return int|NULL
	 */
	public function getLength()
	{
		return $this->itemCount === NULL
			? $this->itemsPerPage
			: min($this->itemsPerPage, $this->itemCount - $this->getPageIndex() * $this->itemsPerPage);
	}

}
