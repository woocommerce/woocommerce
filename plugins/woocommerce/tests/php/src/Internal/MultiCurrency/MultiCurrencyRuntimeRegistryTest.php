<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeRegistry;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyRuntimeRegistry class.
 */
class MultiCurrencyRuntimeRegistryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should expose no native hook groups while plugin multi-currency owns the runtime.
	 */
	public function test_blocks_registration_when_plugin_multi_currency_owns_runtime(): void {
		$sut = $this->create_registry( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$manifest = $sut->get_registration_manifest();

		$this->assertSame( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, $manifest['owner'] );
		$this->assertFalse( $manifest['should_register'], 'Core multi-currency must not register while plugin multi-currency owns the runtime.' );
		$this->assertSame( array( 'plugin_multi_currency_active' ), $manifest['blockers'] );
		$this->assertSame( array(), $manifest['hook_groups'] );
	}

	/**
	 * @testdox Should expose no native hook groups when no multi-currency runtime owns the site.
	 */
	public function test_blocks_registration_when_no_multi_currency_runtime_owns_site(): void {
		$sut = $this->create_registry( MultiCurrencyRuntimeArbiter::OWNER_NONE );

		$manifest = $sut->get_registration_manifest();

		$this->assertSame( MultiCurrencyRuntimeArbiter::OWNER_NONE, $manifest['owner'] );
		$this->assertFalse( $manifest['should_register'], 'Core multi-currency must not register when no runtime owns multi-currency.' );
		$this->assertSame( array( 'no_multi_currency_owner' ), $manifest['blockers'] );
		$this->assertSame( array(), $manifest['hook_groups'] );
	}

	/**
	 * @testdox Should expose native hook groups only when core multi-currency owns the runtime.
	 */
	public function test_exposes_core_hook_groups_when_core_owns_runtime(): void {
		$sut = $this->create_registry( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$manifest = $sut->get_registration_manifest();

		$this->assertSame( MultiCurrencyRuntimeArbiter::OWNER_CORE, $manifest['owner'] );
		$this->assertTrue( $manifest['should_register'], 'Core multi-currency may register only in native ownership mode.' );
		$this->assertSame( array(), $manifest['blockers'] );
		$this->assertSame(
			array(
				'frontend_prices',
				'frontend_currencies',
				'store_currency_lifecycle',
				'selected_currency',
				'analytics',
				'compatibility',
				'bookings_compatibility',
				'deposits_compatibility',
				'pre_orders_compatibility',
				'ups_compatibility',
				'fedex_compatibility',
				'points_rewards_compatibility',
				'name_your_price_compatibility',
				'subscriptions_compatibility',
				'async_prices',
				'storefront',
				'settings',
				'rest',
				'rest_request_overrides',
				'user_settings',
				'admin_notices',
				'admin_notes',
				'tracking',
			),
			array_keys( $manifest['hook_groups'] )
		);
	}

	/**
	 * @testdox Should preserve the WooPayments store currency lifecycle hook surface.
	 */
	public function test_store_currency_lifecycle_manifest_contains_preserved_hook(): void {
		$hook_groups = MultiCurrencyRuntimeRegistry::get_core_hook_groups();

		$this->assertSame(
			array(
				array(
					'hook'          => 'init',
					'callback'      => 'sync_store_currency',
					'priority'      => 10,
					'accepted_args' => 1,
				),
			),
			$hook_groups['store_currency_lifecycle']['actions']
		);
		$this->assertSame( array(), $hook_groups['store_currency_lifecycle']['filters'] );
	}

	/**
	 * @testdox Should preserve the WooPayments frontend price hook surface once in the native manifest.
	 */
	public function test_frontend_price_manifest_contains_preserved_hooks_once(): void {
		$hook_groups = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$price_hooks = array_column( $hook_groups['frontend_prices']['filters'], 'hook' );

		$this->assertSame(
			array(
				'woocommerce_product_get_price',
				'woocommerce_product_get_regular_price',
				'woocommerce_product_get_sale_price',
				'woocommerce_product_variation_get_price',
				'woocommerce_product_variation_get_regular_price',
				'woocommerce_product_variation_get_sale_price',
				'woocommerce_variation_prices',
				'woocommerce_get_variation_prices_hash',
				'woocommerce_shipping_zone_shipping_methods',
				'woocommerce_shipping_method_add_rate_args',
				'woocommerce_coupon_get_amount',
				'woocommerce_coupon_get_minimum_amount',
				'woocommerce_coupon_get_maximum_amount',
				'woocommerce_new_order',
				'rest_post_dispatch',
				'query_loop_block_query_vars',
			),
			$price_hooks
		);
		$this->assertSame( $price_hooks, array_values( array_unique( $price_hooks ) ), 'The frontend price manifest must not declare duplicate price hooks.' );
	}

	/**
	 * @testdox Should preserve the WooPayments frontend currency hook surface.
	 */
	public function test_frontend_currency_manifest_contains_preserved_hooks(): void {
		$hook_groups    = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$currency_hooks = array_merge(
			array_column( $hook_groups['frontend_currencies']['filters'], 'hook' ),
			array_column( $hook_groups['frontend_currencies']['actions'], 'hook' )
		);

		$this->assertSame(
			array(
				'woocommerce_currency',
				'wc_get_price_decimals',
				'wc_get_price_decimal_separator',
				'wc_get_price_thousand_separator',
				'woocommerce_price_format',
				'option_woocommerce_currency_pos',
				'woocommerce_order_get_total',
				'woocommerce_get_formatted_order_total',
				'woocommerce_thankyou_order_id',
				'woocommerce_cart_hash',
				'woocommerce_shipping_method_add_rate_args',
				'before_woocommerce_pay',
				'woocommerce_account_view-order_endpoint',
			),
			$currency_hooks
		);
	}

	/**
	 * @testdox Should preserve the WooPayments analytics hook surface.
	 */
	public function test_analytics_manifest_contains_preserved_hooks(): void {
		$hook_groups     = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$analytics_hooks = array_column( $hook_groups['analytics']['filters'], 'hook' );

		$this->assertSame(
			array(
				'woocommerce_analytics_report_should_use_cache',
				'woocommerce_analytics_update_order_stats_data',
				'woocommerce_analytics_orders_query_args',
				'woocommerce_analytics_orders_stats_query_args',
				'woocommerce_analytics_clauses_select',
				'woocommerce_analytics_clauses_join',
				'woocommerce_analytics_clauses_where_orders_subquery',
				'woocommerce_analytics_clauses_where_orders_stats_total',
				'woocommerce_analytics_clauses_where_orders_stats_interval',
				'woocommerce_analytics_clauses_select_orders_subquery',
				'woocommerce_analytics_clauses_select_orders_stats_total',
			),
			$analytics_hooks
		);
	}

	/**
	 * @testdox Should preserve the WooPayments selected currency hook surface.
	 */
	public function test_selected_currency_manifest_contains_preserved_hooks(): void {
		$hook_groups             = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$selected_currency_hooks = array_column( $hook_groups['selected_currency']['actions'], 'hook' );

		$this->assertSame(
			array(
				'init',
				'init',
				'wp_footer',
				'woocommerce_created_customer',
				'woocommerce_edit_account_form',
				'woocommerce_save_account_details',
			),
			$selected_currency_hooks
		);
		$this->assertSame( 11, $hook_groups['selected_currency']['actions'][0]['priority'] );
		$this->assertSame( 12, $hook_groups['selected_currency']['actions'][1]['priority'] );
		$this->assertSame( 10, $hook_groups['selected_currency']['actions'][2]['priority'] );
	}

	/**
	 * @testdox Should preserve the WooPayments REST request override filter surface.
	 */
	public function test_rest_request_override_manifest_contains_preserved_filters(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['rest_request_overrides']['filters'], 'hook' );

		$this->assertSame(
			array(
				'wcpay_multi_currency_override_selected_currency',
				'wcpay_multi_currency_should_return_store_currency',
				'wcpay_multi_currency_should_convert_product_price',
			),
			$filter_hooks
		);
	}

	/**
	 * @testdox Should preserve the WooPayments subscriptions compatibility filter surface.
	 */
	public function test_subscriptions_compatibility_manifest_contains_preserved_filters(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['subscriptions_compatibility']['filters'], 'hook' );

		$this->assertSame(
			array(
				'wcpay_multi_currency_override_selected_currency',
				'wcpay_multi_currency_should_disable_currency_switching',
				'wcpay_multi_currency_should_convert_product_price',
				'wcpay_multi_currency_should_convert_coupon_amount',
				'woocommerce_subscriptions_product_price',
				'woocommerce_product_get__subscription_sign_up_fee',
				'woocommerce_product_variation_get__subscription_sign_up_fee',
				'woocommerce_subscription_price_string_details',
				'woocommerce_get_formatted_subscription_total',
				'wc_price',
				'option_woocommerce_subscriptions_multiple_purchase',
			),
			$filter_hooks
		);
	}

	/**
	 * @testdox Should preserve the WooPayments Bookings compatibility hook surface.
	 */
	public function test_bookings_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['bookings_compatibility']['filters'], 'hook' );
		$action_hooks = array_column( $hook_groups['bookings_compatibility']['actions'], 'hook' );

		$this->assertSame(
			array(
				'woocommerce_bookings_calculated_booking_cost',
				'woocommerce_product_get_block_cost',
				'woocommerce_product_get_cost',
				'woocommerce_product_get_display_cost',
				'woocommerce_product_booking_person_type_get_block_cost',
				'woocommerce_product_booking_person_type_get_cost',
				'woocommerce_product_get_resource_base_costs',
				'woocommerce_product_get_resource_block_costs',
				'wcpay_multi_currency_should_convert_product_price',
				'woocommerce_bookings_process_cost_rules_cost',
				'woocommerce_bookings_process_cost_rules_base_cost',
			),
			$filter_hooks
		);
		$this->assertSame(
			array(
				'wp_ajax_wc_bookings_calculate_costs',
				'wp_ajax_nopriv_wc_bookings_calculate_costs',
			),
			$action_hooks
		);
	}

	/**
	 * @testdox Should preserve the WooPayments Deposits compatibility hook surface.
	 */
	public function test_deposits_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups = MultiCurrencyRuntimeRegistry::get_core_hook_groups();

		$this->assertSame(
			array(
				'woocommerce_get_cart_contents',
				'woocommerce_product_get__wc_deposit_amount',
				'wcpay_multi_currency_should_convert_product_price',
			),
			array_column( $hook_groups['deposits_compatibility']['filters'], 'hook' )
		);
		$this->assertSame(
			array( 'woocommerce_deposits_create_order' ),
			array_column( $hook_groups['deposits_compatibility']['actions'], 'hook' )
		);
	}

	/**
	 * @testdox Should preserve the WooPayments Pre-Orders compatibility hook surface.
	 */
	public function test_pre_orders_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['pre_orders_compatibility']['filters'], 'hook' );

		$this->assertSame( array( 'wc_pre_orders_fee' ), $filter_hooks );
		$this->assertSame( array(), $hook_groups['pre_orders_compatibility']['actions'] );
	}

	/**
	 * @testdox Should preserve the WooPayments UPS compatibility hook surface.
	 */
	public function test_ups_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['ups_compatibility']['filters'], 'hook' );

		$this->assertSame(
			array( 'wcpay_multi_currency_should_return_store_currency' ),
			$filter_hooks
		);
		$this->assertSame( array(), $hook_groups['ups_compatibility']['actions'] );
	}

	/**
	 * @testdox Should preserve the WooPayments FedEx compatibility hook surface.
	 */
	public function test_fedex_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['fedex_compatibility']['filters'], 'hook' );

		$this->assertSame(
			array(
				'wcpay_multi_currency_should_convert_product_price',
				'wcpay_multi_currency_should_return_store_currency',
			),
			$filter_hooks
		);
		$this->assertSame( array(), $hook_groups['fedex_compatibility']['actions'] );
	}

	/**
	 * @testdox Should preserve the WooPayments Points and Rewards compatibility hook surface.
	 */
	public function test_points_rewards_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups  = MultiCurrencyRuntimeRegistry::get_core_hook_groups();
		$filter_hooks = array_column( $hook_groups['points_rewards_compatibility']['filters'], 'hook' );

		$this->assertSame(
			array(
				'option_wc_points_rewards_earn_points_ratio',
				'option_wc_points_rewards_redeem_points_ratio',
			),
			$filter_hooks
		);
		$this->assertSame( array(), $hook_groups['points_rewards_compatibility']['actions'] );
	}

	/**
	 * @testdox Should preserve the WooPayments Name Your Price compatibility hook surface.
	 */
	public function test_name_your_price_compatibility_manifest_contains_preserved_hooks(): void {
		$hook_groups = MultiCurrencyRuntimeRegistry::get_core_hook_groups();

		$this->assertSame(
			array(
				'wc_nyp_raw_minimum_price',
				'wc_nyp_raw_maximum_price',
				'wc_nyp_raw_suggested_price',
				'woocommerce_get_cart_item_from_session',
				'wcpay_multi_currency_should_convert_product_price',
				'wc_nyp_edit_in_cart_args',
				'wc_nyp_get_initial_price',
			),
			array_column( $hook_groups['name_your_price_compatibility']['filters'], 'hook' )
		);
		$this->assertSame(
			array( 'woocommerce_add_cart_item_data' ),
			array_column( $hook_groups['name_your_price_compatibility']['actions'], 'hook' )
		);
	}

	/**
	 * Create a runtime registry with a static arbiter owner.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeRegistry
	 */
	private function create_registry( string $owner ): MultiCurrencyRuntimeRegistry {
		$registry = new MultiCurrencyRuntimeRegistry();
		$registry->init(
			new class( $owner ) extends MultiCurrencyRuntimeArbiter {
				/**
				 * Runtime owner.
				 *
				 * @var string
				 */
				private string $owner;

				/**
				 * Constructor.
				 *
				 * @param string $owner Runtime owner.
				 */
				public function __construct( string $owner ) {
					$this->owner = $owner;
				}

				/**
				 * Get the multi-currency runtime owner for the current site.
				 *
				 * @return string
				 */
				public function get_runtime_owner(): string {
					return $this->owner;
				}

				/**
				 * Tell whether core multi-currency may register price/currency hooks.
				 *
				 * @return bool
				 */
				public function should_core_register(): bool {
					return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
				}
			}
		);

		return $registry;
	}
}
