<?php
/**
 * Store Info Ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Enums\OrderStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Store Info Ability for WooCommerce.
 * 
 * Provides basic WooCommerce store information via WordPress Abilities API.
 * Can be consumed by MCP or other tools.
 */
class StoreInfoAbility {

	/**
	 * Initialize the ability.
	 */
	public static function init(): void {
		// Register the ability when Abilities API is ready
		add_action( 'abilities_api_init', array( __CLASS__, 'register_ability' ) );
	}

	/**
	 * Register the store info ability.
	 */
	public static function register_ability(): void {
		// Only proceed if wp_register_ability function exists
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/store-info',
			array(
				'label'             => __( 'Get Store Information', 'woocommerce' ),
				'description'       => __( 'Retrieves basic information about the WooCommerce store including name, URL, version, and basic statistics.', 'woocommerce' ),
				'input_schema'      => array(
					'type'       => 'object',
					'properties' => array(
						'include_stats' => array(
							'type'        => 'boolean',
							'description' => 'Whether to include basic store statistics (product count, order count, etc.)',
							'default'     => false,
						),
					),
				),
				'output_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'store_name'          => array( 'type' => 'string' ),
						'store_url'           => array( 'type' => 'string' ),
						'admin_email'         => array( 'type' => 'string' ),
						'woocommerce_version' => array( 'type' => 'string' ),
						'wordpress_version'   => array( 'type' => 'string' ),
						'currency'            => array( 'type' => 'string' ),
						'country'             => array( 'type' => 'string' ),
						'stats'               => array(
							'type'       => 'object',
							'properties' => array(
								'product_count'  => array( 'type' => 'integer' ),
								'order_count'    => array( 'type' => 'integer' ),
								'customer_count' => array( 'type' => 'integer' ),
							),
						),
					),
					'required'   => array( 'store_name', 'store_url', 'woocommerce_version' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_ability' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			)
		);
	}

	/**
	 * Execute the store info ability.
	 *
	 * @param array $input Input parameters.
	 * @return array Store information.
	 */
	public static function execute_ability( array $input ): array {
		$result = array(
			'store_name'          => get_bloginfo( 'name' ),
			'store_url'           => get_site_url(),
			'admin_email'         => get_bloginfo( 'admin_email' ),
			'woocommerce_version' => WC()->version,
			'wordpress_version'   => get_bloginfo( 'version' ),
			'currency'            => get_woocommerce_currency(),
			'country'             => WC()->countries->get_base_country(),
		);

		// Include statistics if requested
		if ( ! empty( $input['include_stats'] ) ) {
			// Products use 'publish' status.
			$product_count = (int) wp_count_posts( 'product' )->publish;

			// Orders using wc_orders_count for efficient counting with caching
			$completed_count  = wc_orders_count( OrderStatus::COMPLETED );
			$processing_count = wc_orders_count( OrderStatus::PROCESSING );
			$pending_count    = wc_orders_count( OrderStatus::PENDING );
			$on_hold_count    = wc_orders_count( OrderStatus::ON_HOLD );
			$cancelled_count  = wc_orders_count( OrderStatus::CANCELLED );
			$refunded_count   = wc_orders_count( OrderStatus::REFUNDED );
			$failed_count     = wc_orders_count( OrderStatus::FAILED );

			$order_breakdown = array(
				'completed'  => $completed_count,
				'processing' => $processing_count,
				'pending'    => $pending_count,
				'on-hold'    => $on_hold_count,
				'cancelled'  => $cancelled_count,
				'refunded'   => $refunded_count,
				'failed'     => $failed_count,
			);

			$order_count = $completed_count + $processing_count + $pending_count + $on_hold_count + $cancelled_count + $refunded_count + $failed_count;

			// Customers only.
			$users_counts   = count_users();
			$customer_count = isset( $users_counts['avail_roles']['customer'] ) ? (int) $users_counts['avail_roles']['customer'] : 0;

			$result['stats'] = array(
				'product_count'    => $product_count,
				'order_count'      => $order_count,
				'order_breakdown'  => $order_breakdown,
				'customer_count'   => $customer_count,
			);
		}

		return $result;
	}

	/**
	 * Check permission for the store info ability.
	 *
	 * @return bool Whether user has permission.
	 */
	public static function check_permission(): bool {
		// Allow users who can view WooCommerce reports
		return current_user_can( 'view_woocommerce_reports' );
	}
}