<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Latte\Macros;

use Nette,
	Nette\Latte;



/**
 * Macro {cache} ... {/cache}
 *
 * @author     David Grudl
 */
class CacheMacro extends Nette\Object implements Latte\IMacro
{
	/** @var bool */
	private $used;



	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	public function initialize()
	{
		$this->used = FALSE;
	}



	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		if ($this->used) {
			return array('Nette\Latte\Macros\CacheMacro::initRuntime($template, $_g);');
		}
	}



	/**
	 * New node is found.
	 * @return bool
	 */
	public function nodeOpened(Latte\MacroNode $node)
	{
		$this->used = TRUE;
		$node->isEmpty = FALSE;
		$node->openingCode = Latte\PhpWriter::using($node)
			->write('<?php if (Nette\Latte\Macros\CacheMacro::createCache($netteCacheStorage, %var, $_g->caches, %node.array?)) { ?>',
				Nette\Utils\Strings::random()
			);
	}



	/**
	 * Node is closed.
	 * @return void
	 */
	public function nodeClosed(Latte\MacroNode $node)
	{
		$node->closingCode = '<?php $_l->tmp = array_pop($_g->caches); if (!$_l->tmp instanceof stdClass) $_l->tmp->end(); } ?>';
	}



	/********************* run-time helpers ****************d*g**/



	/**
	 * @return void
	 */
	public static function initRuntime(Nette\Templating\FileTemplate $template, \stdClass $global)
	{
		if (!empty($global->caches)) {
			end($global->caches)->dependencies[Nette\Caching\Cache::FILES][] = $template->getFile();
		}
	}



	/**
	 * Starts the output cache. Returns Nette\Caching\OutputHelper object if buffering was started.
	 * @param  Nette\Caching\IStorage
	 * @param  string
	 * @param  Nette\Caching\OutputHelper[]
	 * @param  array
	 * @return Nette\Caching\OutputHelper
	 */
	public static function createCache(Nette\Caching\IStorage $cacheStorage, $key, & $parents, array $args = NULL)
	{
		if ($args) {
			if (array_key_exists('if', $args) && !$args['if']) {
				return $parents[] = (object) NULL;
			}
			$key = array_merge(array($key), array_intersect_key($args, range(0, count($args))));
		}
		if ($parents) {
			end($parents)->dependencies[Nette\Caching\Cache::ITEMS][] = $key;
		}

		$cache = new Nette\Caching\Cache($cacheStorage, 'Nette.Templating.Cache');
		if ($helper = $cache->start($key)) {
			if (isset($args['expire'])) {
				$args['expiration'] = $args['expire']; // back compatibility
			}
			$helper->dependencies = array(
				Nette\Caching\Cache::TAGS => isset($args['tags']) ? $args['tags'] : NULL,
				Nette\Caching\Cache::EXPIRATION => isset($args['expiration']) ? $args['expiration'] : '+ 7 days',
			);
			$parents[] = $helper;
		}
		return $helper;
	}

}
