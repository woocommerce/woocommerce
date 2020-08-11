<?php
/**
 * Handles stored state setup for products.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\PluginsProvider\PluginsProvider;
use \Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;

/**
 * Handles stored state setup for products.
 */
class StoredStateSetupForProducts {
	/**
	 * Initialize the class
	 */
	public static function init() {
		add_action( 'product_page_product_importer', array( __CLASS__, 'run_on_product_importer' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'run_on_transition_post_status' ), 10, 3 );
	}

	/**
	 * Set initial stored state values.
	 *
	 * @param object $stored_state The stored state.
	 *
	 * @return object The stored state.
	 */
	public static function init_stored_state( $stored_state ) {
		$stored_state->there_were_no_products = ! self::are_there_products();
		$stored_state->there_are_now_products = ! $stored_state->there_were_no_products;

		return $stored_state;
	}

	/**
	 * Are there products query.
	 *
	 * @return bool
	 */
	private static function are_there_products() {
		$query    = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
			)
		);
		$products = $query->get_products();
		$count    = $products->total;

		return $count > 0;
	}

	/**
	 * Runs on product importer steps.
	 */
	public static function run_on_product_importer() {
		// We're only interested in when the importer completes.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['step'] ) ) {
			return;
		}
		if ( 'done' !== $_REQUEST['step'] ) {
			return;
		}
		// phpcs:enable

		$stored_state                         = RemoteInboxNotificationsEngine::get_stored_state();
		$stored_state->there_are_now_products = true;
		RemoteInboxNotificationsEngine::update_stored_state( $stored_state );

		RemoteInboxNotificationsEngine::run();
	}

	/**
	 * Runs when a post status transitions, but we're only interested if it is
	 * a product being published.
	 *
	 * @param string $new_status The new status.
	 * @param string $old_status The old status.
	 * @param Post   $post       The post.
	 */
	public static function run_on_transition_post_status( $new_status, $old_status, $post ) {
		if (
			'product' !== $post->post_type ||
			'publish' !== $new_status
		) {
			return;
		}

		$stored_state                         = RemoteInboxNotificationsEngine::get_stored_state();
		$stored_state->there_are_now_products = true;
		RemoteInboxNotificationsEngine::update_stored_state( $stored_state );

		RemoteInboxNotificationsEngine::run();
	}
}
