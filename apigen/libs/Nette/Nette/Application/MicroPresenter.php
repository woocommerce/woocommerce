<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace NetteModule;

use Nette,
	Nette\Application,
	Nette\Application\Responses,
	Nette\Http;



/**
 * Micro presenter.
 *
 * @author     David Grudl
 *
 * @property-read Nette\Application\IRequest $request
 */
class MicroPresenter extends Nette\Object implements Application\IPresenter
{
	/** @var Nette\DI\Container */
	private $context;

	/** @var Nette\Application\Request */
	private $request;



	public function __construct(Nette\DI\Container $context)
	{
		$this->context = $context;
	}



	/**
	 * Gets the context.
	 * @return \SystemContainer|Nette\DI\Container
	 */
	final public function getContext()
	{
		return $this->context;
	}



	/**
	 * @param  Nette\Application\Request
	 * @return Nette\Application\IResponse
	 */
	public function run(Application\Request $request)
	{
		$this->request = $request;

		$httpRequest = $this->context->getByType('Nette\Http\IRequest');
		if (!$httpRequest->isAjax() && ($request->isMethod('get') || $request->isMethod('head'))) {
			$refUrl = clone $httpRequest->getUrl();
			$url = $this->context->router->constructUrl($request, $refUrl->setPath($refUrl->getScriptPath()));
			if ($url !== NULL && !$httpRequest->getUrl()->isEqual($url)) {
				return new Responses\RedirectResponse($url, Http\IResponse::S301_MOVED_PERMANENTLY);
			}
		}

		$params = $request->getParameters();
		if (!isset($params['callback'])) {
			return;
		}
		$params['presenter'] = $this;
		$callback = new Nette\Callback($params['callback']);
		$response = $callback->invokeArgs(Application\UI\PresenterComponentReflection::combineArgs($callback->toReflection(), $params));

		if (is_string($response)) {
			$response = array($response, array());
		}
		if (is_array($response)) {
			if ($response[0] instanceof \SplFileInfo) {
				$response = $this->createTemplate('Nette\Templating\FileTemplate')
					->setParameters($response[1])->setFile($response[0]);
			} else {
				$response = $this->createTemplate('Nette\Templating\Template')
					->setParameters($response[1])->setSource($response[0]);
			}
		}
		if ($response instanceof Nette\Templating\ITemplate) {
			return new Responses\TextResponse($response);
		} else {
			return $response;
		}
	}



	/**
	 * Template factory.
	 * @param  string
	 * @param  callable
	 * @return Nette\Templating\ITemplate
	 */
	public function createTemplate($class = NULL, $latteFactory = NULL)
	{
		$template = $class ? new $class : new Nette\Templating\FileTemplate;

		$template->setParameters($this->request->getParameters());
		$template->presenter = $this;
		$template->context = $context = $this->context;
		$url = $context->getByType('Nette\Http\IRequest')->getUrl();
		$template->baseUrl = rtrim($url->getBaseUrl(), '/');
		$template->basePath = rtrim($url->getBasePath(), '/');

		$template->registerHelperLoader('Nette\Templating\Helpers::loader');
		$template->setCacheStorage($context->nette->templateCacheStorage);
		$template->onPrepareFilters[] = function($template) use ($latteFactory, $context) {
			$template->registerFilter($latteFactory ? $latteFactory() : new Nette\Latte\Engine);
		};
		return $template;
	}



	/**
	 * Redirects to another URL.
	 * @param  string
	 * @param  int HTTP code
	 * @return void
	 */
	public function redirectUrl($url, $code = Http\IResponse::S302_FOUND)
	{
		return new Responses\RedirectResponse($url, $code);
	}



	/**
	 * Throws HTTP error.
	 * @param  string
	 * @param  int HTTP error code
	 * @return void
	 * @throws Nette\Application\BadRequestException
	 */
	public function error($message = NULL, $code = Http\IResponse::S404_NOT_FOUND)
	{
		throw new Application\BadRequestException($message, $code);
	}



	/**
	 * @return Nette\Application\IRequest
	 */
	public function getRequest()
	{
		return $this->request;
	}

}
