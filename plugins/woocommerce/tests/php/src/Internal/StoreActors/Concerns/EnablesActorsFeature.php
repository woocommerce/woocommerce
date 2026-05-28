<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StoreActors\Concerns;

use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;

/**
 * Test helper trait: ensures the store-actor tables exist for the duration of
 * the test. Calls dbDelta directly against the repository's schema so the
 * `point_of_sale_actors` feature flag does not need to be toggled globally.
 *
 * @internal
 * @since 10.9.0
 */
trait EnablesActorsFeature {

	/**
	 * Run dbDelta for the actor + access tables. Idempotent.
	 *
	 * @return void
	 */
	protected function install_actor_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$schema = wc_get_container()->get( ActorRepository::class )->get_database_schema();
		dbDelta( $schema );
	}
}
