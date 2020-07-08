<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Package;

/**
 * Library class.
 */
class Library {

	/**
	 * Initialize block library features.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'init', array( __CLASS__, 'define_tables' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'register_draft_order_status' ) );
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( __CLASS__, 'register_draft_order_post_status' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( __CLASS__, 'append_draft_order_post_status' ) );
		add_action( 'woocommerce_cleanup_draft_orders', array( __CLASS__, 'delete_expired_draft_orders' ) );
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	public static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'wc_reserved_stock' => 'wc_reserved_stock',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public static function register_blocks() {
		global $wp_version;
		$blocks = [
			'AllReviews',
			'FeaturedCategory',
			'FeaturedProduct',
			'HandpickedProducts',
			'ProductBestSellers',
			'ProductCategories',
			'ProductCategory',
			'ProductNew',
			'ProductOnSale',
			'ProductsByAttribute',
			'ProductTopRated',
			'ReviewsByProduct',
			'ReviewsByCategory',
			'ProductSearch',
			'ProductTag',
		];
		// Note: as a part of refactoring dynamic block registration, this will be moved
		// to block level config.
		if ( version_compare( $wp_version, '5.2', '>' ) ) {
			$blocks[] = 'AllProducts';
			$blocks[] = 'PriceFilter';
			$blocks[] = 'AttributeFilter';
			$blocks[] = 'ActiveFilters';

			if ( Package::is_feature_plugin_build() ) {
				$blocks[] = 'Checkout';
				$blocks[] = 'Cart';
			}
		}
		if ( Package::is_experimental_build() ) {
			$blocks[] = 'SingleProduct';
		}
		foreach ( $blocks as $class ) {
			$class    = __NAMESPACE__ . '\\BlockTypes\\' . $class;
			$instance = new $class();
			$instance->register_block_type();
		}
		self::register_atomic_blocks();
	}

	/**
	 * Register atomic blocks on the PHP side.
	 */
	protected static function register_atomic_blocks() {
		$atomic_blocks = [
			'product-title',
			'product-button',
			'product-image',
			'product-price',
			'product-rating',
			'product-sale-badge',
			'product-summary',
			'product-sku',
		];
		foreach ( $atomic_blocks as $atomic_block ) {
			$instance = new \Automattic\WooCommerce\Blocks\BlockTypes\AtomicBlock( $atomic_block );
			$instance->register_block_type();
		}
	}

	/**
	 * Register custom order status for orders created via the API during checkout.
	 *
	 * Draft order status is used before payment is attempted, during checkout, when a cart is converted to an order.
	 *
	 * @param array $statuses Array of statuses.
	 * @return array
	 */
	public static function register_draft_order_status( array $statuses ) {
		$statuses['wc-checkout-draft'] = _x( 'Draft', 'Order status', 'woocommerce' );
		return $statuses;
	}

	/**
	 * Register custom order post status for orders created via the API during checkout.
	 *
	 * @param array $statuses Array of statuses.
	 * @return array
	 */
	public static function register_draft_order_post_status( array $statuses ) {
		$statuses['wc-checkout-draft'] = [
			'label'                     => _x( 'Draft', 'Order status', 'woocommerce' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop( 'Drafts <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>', 'woocommerce' ),
		];
		return $statuses;
	}

	/**
	 * Append draft status to a list of statuses.
	 *
	 * @param array $statuses Array of statuses.
	 * @return array
	 */
	public static function append_draft_order_post_status( $statuses ) {
		$statuses[] = 'checkout-draft';
		return $statuses;
	}

	/**
	 * Delete draft orders older than a day in batches of 20.
	 *
	 * Ran on a daily cron schedule.
	 */
	public static function delete_expired_draft_orders() {
		$count      = 0;
		$batch_size = 20;
		$orders     = wc_get_orders(
			[
				'date_modified' => '<=' . strtotime( '-1 DAY' ),
				'limit'         => $batch_size,
				'status'        => 'wc-checkout-draft',
				'type'          => 'shop_order',
			]
		);

		if ( $orders ) {
			foreach ( $orders as $order ) {
				$order->delete( true );
				$count ++;
			}
		}

		if ( $batch_size === $count && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'woocommerce_cleanup_draft_orders' );
		}
	}
}
