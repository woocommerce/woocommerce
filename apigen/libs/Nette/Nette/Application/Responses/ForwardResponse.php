<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\Responses;

use Nette;



/**
 * Forwards to new request.
 *
 * @author     David Grudl
 *
 * @property-read Nette\Application\Request $request
 */
class ForwardResponse extends Nette\Object implements Nette\Application\IResponse
{
	/** @var Nette\Application\Request */
	private $request;



	/**
	 * @param  Nette\Application\Request  new request
	 */
	public function __construct(Nette\Application\Request $request)
	{
		$this->request = $request;
	}



	/**
	 * @return Nette\Application\Request
	 */
	final public function getRequest()
	{
		return $this->request;
	}



	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
	}

}
