<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use WC_Order;

/**
 * Helper class for creating and managing fulfillments in tests.
 *
 * This helper class should ONLY be used for unit tests!.
 *
 * @since 9.0.0
 */
class FulfillmentsHelper {
	/**
	 * Create the fulfillments tables without registering any fulfillments hooks.
	 *
	 * Call it from setUpBeforeClass(): creating tables inside a test's transaction is not allowed.
	 * Hooks added there would stay for the whole run when the class runs first, so add them per test
	 * with enable_fulfillments() instead.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		$controller = wc_get_container()->get( FulfillmentsController::class );
		$method     = new \ReflectionMethod( $controller, 'maybe_create_db_tables' );
		$method->setAccessible( true );
		$method->invoke( $controller );
	}

	/**
	 * Turn the fulfillments feature on for the current test and register its hooks.
	 *
	 * Call it from setUp(), after parent::setUp(): the test transaction rolls back the option and the
	 * base tear-down removes the hooks. The shipping provider taxonomy it registers stays for the rest
	 * of the process. The tables must already exist (see create_tables()).
	 *
	 * @return void
	 */
	public static function enable_fulfillments(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		wc_get_container()->get( FulfillmentsController::class )->initialize_fulfillments();
	}

	/**
	 * Helper function to create a fulfillment.
	 *
	 * @param array $args Arguments to create the fulfillment.
	 * @param array $metadata Metadata to add to the fulfillment.
	 *
	 * @return Fulfillment The created fulfillment object.
	 */
	public static function create_fulfillment( array $args = array(), array $metadata = array() ) {
		$fulfillment = new Fulfillment();
		$fulfillment->set_props(
			array_merge(
				array(
					'id'           => 0,
					'entity_type'  => WC_Order::class,
					'entity_id'    => 123,
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
				),
				$args
			)
		);

		if ( $metadata ) {
			foreach ( $metadata as $key => $value ) {
				$fulfillment->add_meta_data(
					$key,
					$value,
					true
				);
			}
		} else {
			$fulfillment->add_meta_data(
				'test_meta_key',
				'test_meta_value',
				true
			);

			$fulfillment->set_items(
				array(
					array(
						'item_id' => 1,
						'qty'     => 2,
					),
					array(
						'item_id' => 2,
						'qty'     => 3,
					),
				)
			);
		}

		$fulfillment->save();

		return $fulfillment;
	}
}
