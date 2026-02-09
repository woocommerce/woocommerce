<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

/**
 * Allows a step to have an alias.
 *
 * An alias is useful for selective export.
 *
 * Let's say you have three exporters and all of them use `setSiteOptions` step.
 *
 * Step A: Exports options from WooCommerce -> Settings
 * Step B: Exports options for the core profiler selection.
 * Step C: Exports options for the task list.
 *
 * You also have a UI where a client can select which steps to export. In this case, we have three checkboxes.
 *
 * [ ] WooCommerce Settings
 * [ ] WooCommerce Core Profiler
 * [ ] WooCommerce Task List
 *
 * Without alias, the client would see three `setSiteOptions` steps and it's not possible
 * to distinguish between them from the ExportSchema class.
 *
 * With alias, you can give each step a unique alias while keeping the step name the same.
 *
 * @todo Link to an example class that uses this interface.
 *
 * Interface HasAlias
 */
interface HasAlias {
	/**
	 * Get the alias for the step.
	 *
	 * @return string The alias for the step.
	 */
	public function get_alias();
}
