<?php
/**
 * SupportsStagedChanges interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An object that takes a change in memory first, and saves it later in one
 * write. An ActionableAbility stages the change, writes the extension fields,
 * runs the validators, and then commits. Nothing is saved, and no action that
 * announces the change fires, before commit_changes().
 *
 * A class that extends a Core object and keeps its own rules in a method that
 * sets and saves at once, such as an update_status() override, declares this
 * interface once those rules run from stage_changes() and commit_changes().
 * Until then, the ability keeps calling the method it called before.
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
