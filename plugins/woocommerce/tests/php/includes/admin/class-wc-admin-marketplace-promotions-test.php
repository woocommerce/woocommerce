<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Marketplace_Promotions.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Marketplace_Promotions_Test extends WC_Unit_Test_Case {

	/**
	 * Clean up state between tests.
	 */
	public function tearDown(): void {
		delete_transient( WC_Admin_Marketplace_Promotions::TRANSIENT_NAME );
		delete_option( 'woocommerce_allow_tracking' );

		parent::tearDown();
	}

	/**
	 * @testdox Eligible rule-based promotions are converted into promo cards.
	 */
	public function test_eligible_rule_based_promotions_are_converted_to_promo_cards(): void {
		set_transient(
			WC_Admin_Marketplace_Promotions::TRANSIENT_NAME,
			array(
				array(
					'date_from_gmt' => '2025-01-01 00:00:00',
					'date_to_gmt'   => '2099-01-01 00:00:00',
					'format'        => WC_Admin_Marketplace_Promotions::RULE_BASED_FORMAT,
					'pages'         => array(
						array(
							'page' => 'wc-admin',
							'path' => '/',
						),
					),
					'content'       => array(
						'en_US' => 'You’ve started getting steady orders. Bring back shoppers who leave before checkout with automated follow-up emails.',
					),
					'title'         => array(
						'en_US' => 'Recover abandoned carts automatically',
					),
					'cta_label'     => array(
						'en_US' => 'See AutomateWoo',
					),
					'cta_link'      => 'https://woocommerce.com/products/automatewoo/',
					'local_rules'   => array(
						array(
							'type' => 'pass',
						),
					),
				),
			)
		);

		$promotions = WC_Admin_Marketplace_Promotions::get_active_promotions();

		$this->assertCount( 1, $promotions );
		$this->assertSame( 'promo-card', $promotions[0]['format'] );
		$this->assertArrayNotHasKey( 'local_rules', $promotions[0] );
	}

	/**
	 * @testdox Rule-based promotions are suppressed when local rules fail.
	 */
	public function test_rule_based_promotions_are_suppressed_when_rules_fail(): void {
		set_transient(
			WC_Admin_Marketplace_Promotions::TRANSIENT_NAME,
			array(
				array(
					'date_from_gmt' => '2025-01-01 00:00:00',
					'date_to_gmt'   => '2099-01-01 00:00:00',
					'format'        => WC_Admin_Marketplace_Promotions::RULE_BASED_FORMAT,
					'pages'         => array(
						array(
							'page' => 'wc-admin',
							'path' => '/',
						),
					),
					'content'       => array(
						'en_US' => 'You’ve started getting steady orders. Bring back shoppers who leave before checkout with automated follow-up emails.',
					),
					'local_rules'   => array(
						array(
							'type' => 'fail',
						),
					),
				),
			)
		);

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox Rule-based promotions can use local tracking opt-in state.
	 */
	public function test_rule_based_promotions_can_check_tracking_opt_in_state(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		set_transient(
			WC_Admin_Marketplace_Promotions::TRANSIENT_NAME,
			array(
				array(
					'date_from_gmt' => '2025-01-01 00:00:00',
					'date_to_gmt'   => '2099-01-01 00:00:00',
					'format'        => WC_Admin_Marketplace_Promotions::RULE_BASED_FORMAT,
					'pages'         => array(
						array(
							'page' => 'wc-admin',
							'path' => '/',
						),
					),
					'content'       => array(
						'en_US' => 'You’ve started getting steady orders. Bring back shoppers who leave before checkout with automated follow-up emails.',
					),
					'title'         => array(
						'en_US' => 'Recover abandoned carts automatically',
					),
					'cta_label'     => array(
						'en_US' => 'See AutomateWoo',
					),
					'cta_link'      => 'https://woocommerce.com/products/automatewoo/',
					'local_rules'   => array(
						array(
							'type'        => 'option',
							'option_name' => 'woocommerce_allow_tracking',
							'operation'   => '=',
							'value'       => 'yes',
						),
					),
				),
			)
		);

		$promotions = WC_Admin_Marketplace_Promotions::get_active_promotions();

		$this->assertCount( 1, $promotions );
		$this->assertSame( 'promo-card', $promotions[0]['format'] );
	}

	/**
	 * @testdox Malformed rule-based promotions fail closed.
	 */
	public function test_malformed_rule_based_promotions_fail_closed(): void {
		set_transient(
			WC_Admin_Marketplace_Promotions::TRANSIENT_NAME,
			array(
				array(
					'date_from_gmt' => '2025-01-01 00:00:00',
					'date_to_gmt'   => '2099-01-01 00:00:00',
					'format'        => WC_Admin_Marketplace_Promotions::RULE_BASED_FORMAT,
					'pages'         => array(
						array(
							'page' => 'wc-admin',
							'path' => '/',
						),
					),
					'content'       => array(
						'en_US' => 'You’ve started getting steady orders. Bring back shoppers who leave before checkout with automated follow-up emails.',
					),
					'local_rules'   => array(
						array(
							'type' => 'order_count',
						),
					),
				),
			)
		);

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}
}
