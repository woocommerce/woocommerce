<?php
/**
 * SupportsStagedChanges interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An object that takes changes in memory first, and saves them later in one
 * write. An ActionableAbility stages the change, writes the extension fields,
 * runs the validators, and then commits. Nothing is saved, and no action that
 * announces the change fires, before commit_changes().
 *
 * Every WC_Data object satisfies this contract: stage_changes() sets props and
 * commit_changes() saves. Satisfying it is not enough for an ability to use
 * it. The exact class is registered with
 * AbilityExtensions::register_staged_class(), so a class that extends a
 * registered one, and may keep its own rules in a method that sets and saves
 * at once, keeps its current path until it is registered too.
 *
 * @since 11.3.0
 */
interface SupportsStagedChanges {

	/**
	 * Take the changes in memory. Nothing is saved.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $changes Changes keyed by name, for example `status` and `note` for an order.
	 * @return mixed A WP_Error refuses the changes.
	 */
	public function stage_changes( array $changes );

	/**
	 * Save the staged changes in one write, and fire the actions that announce them.
	 *
	 * @since 11.3.0
	 *
	 * @return mixed
	 */
	public function commit_changes();
}
