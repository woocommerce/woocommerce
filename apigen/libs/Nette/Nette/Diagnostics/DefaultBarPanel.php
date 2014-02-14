<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * IDebugPanel implementation helper.
 *
 * @author     David Grudl
 * @internal
 */
final class DefaultBarPanel extends Nette\Object implements IBarPanel
{
	private $id;

	public $data;


	public function __construct($id)
	{
		$this->id = $id;
	}



	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	public function getTab()
	{
		ob_start();
		$data = $this->data;
		if ($this->id === 'time') {
			require __DIR__ . '/templates/bar.time.tab.phtml';
		} elseif ($this->id === 'memory') {
			require __DIR__ . '/templates/bar.memory.tab.phtml';
		} elseif ($this->id === 'dumps' && $this->data) {
			require __DIR__ . '/templates/bar.dumps.tab.phtml';
		} elseif ($this->id === 'errors' && $this->data) {
			require __DIR__ . '/templates/bar.errors.tab.phtml';
		}
		return ob_get_clean();
	}



	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	public function getPanel()
	{
		ob_start();
		$data = $this->data;
		if ($this->id === 'dumps') {
			require __DIR__ . '/templates/bar.dumps.panel.phtml';
		} elseif ($this->id === 'errors') {
			require __DIR__ . '/templates/bar.errors.panel.phtml';
		}
		return ob_get_clean();
	}

}
