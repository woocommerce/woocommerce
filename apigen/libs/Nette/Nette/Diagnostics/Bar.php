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
 * Debug Bar.
 *
 * @author     David Grudl
 */
class Bar extends Nette\Object
{
	/** @var array */
	private $panels = array();



	/**
	 * Add custom panel.
	 * @param  IBarPanel
	 * @param  string
	 * @return Bar  provides a fluent interface
	 */
	public function addPanel(IBarPanel $panel, $id = NULL)
	{
		if ($id === NULL) {
			$c = 0;
			do {
				$id = get_class($panel) . ($c++ ? "-$c" : '');
			} while (isset($this->panels[$id]));
		}
		$this->panels[$id] = $panel;
		return $this;
	}



	/**
	 * Renders debug bar.
	 * @return void
	 */
	public function render()
	{
		$obLevel = ob_get_level();
		$panels = array();
		foreach ($this->panels as $id => $panel) {
			try {
				$panels[] = array(
					'id' => preg_replace('#[^a-z0-9]+#i', '-', $id),
					'tab' => $tab = (string) $panel->getTab(),
					'panel' => $tab ? (string) $panel->getPanel() : NULL,
				);
			} catch (\Exception $e) {
				$panels[] = array(
					'id' => "error-" . preg_replace('#[^a-z0-9]+#i', '-', $id),
					'tab' => "Error in $id",
					'panel' => '<h1>Error: ' . $id . '</h1><div class="nette-inner">' . nl2br(htmlSpecialChars($e)) . '</div>',
				);
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}
			}
		}
		require __DIR__ . '/templates/bar.phtml';
	}

}
