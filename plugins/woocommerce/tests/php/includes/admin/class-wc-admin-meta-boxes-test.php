<?php

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for meta box-related functionality in the product editor.
 */
class WC_Admin_Meta_Boxes_Test extends WC_Unit_Test_Case {
	/**
	 * @var WC_Admin_Meta_Boxes
	 */
	private $sut;

	/**
	 * Create subject-under-test.
	 */
	public function set_up() {
		$this->sut = new WC_Admin_Meta_Boxes();
		parent::set_up();
	}

	/**
	 * @testdox Test that meta box errors can be stored and retrieved as expected.
	 */
	public function test_persistence_of_meta_box_errors() {
		WC_Admin_Meta_Boxes::add_error( 'Oh no!' );
		WC_Admin_Meta_Boxes::add_error( 'Crikey!' );

		$error_output = $this->get_meta_box_error_output();
		$this->assertEmpty( $error_output, 'If the errors have not first been saved to the database, they cannot be retrieved for display.' );

		$this->simulate_shutdown();
		$error_output = $this->get_meta_box_error_output();
		$this->assertStringContainsString( 'Oh no!', $error_output, 'The error output contains the expected error string (test #1).' );
		$this->assertStringContainsString( 'Crikey!', $error_output, 'The error output contains the expected error string (test #2).' );

		$error_output = $this->get_meta_box_error_output();
		$this->assertEmpty( $error_output, 'The error store is cleared after errors have been output.' );
	}

	/**
	 * @testdox Test that the stored meta box errors are not accidentally cleared by concurrent requests before they are rendered.
	 */
	public function test_meta_box_errors_are_not_accidentally_cleared_during_shutdown() {
		WC_Admin_Meta_Boxes::add_error( 'Yikes!' );

		$this->simulate_shutdown();
		$this->simulate_shutdown();

		$error_output = $this->get_meta_box_error_output();
		$this->assertStringContainsString( 'Yikes!', $error_output, 'The stored error persisted across requests.' );
	}

	/**
	 * @testdox A new CPT order auto-draft immediately persists its creation-time tax mode.
	 *
	 * @param string $option_value        Store tax-mode setting.
	 * @param string $expected_meta_value Persisted order metadata value.
	 * @param bool   $expected_value      Order property value.
	 * @dataProvider provide_prices_include_tax_settings
	 */
	public function test_new_order_auto_draft_persists_prices_include_tax( string $option_value, string $expected_meta_value, bool $expected_value ): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$previous_pagenow    = $GLOBALS['pagenow'] ?? null;
		$had_pagenow         = array_key_exists( 'pagenow', $GLOBALS );
		$order_id            = 0;
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		OrderHelper::toggle_cot_feature_and_usage( false );

		try {
			update_option( 'woocommerce_prices_include_tax', $option_value );
			$GLOBALS['pagenow'] = 'post-new.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$order_id = wp_insert_post(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'auto-draft',
					'post_title'  => 'Auto Draft',
				)
			);

			$this->assertSame( $expected_meta_value, get_post_meta( $order_id, '_prices_include_tax', true ) );

			$order = wc_get_order( $order_id );
			$this->assertInstanceOf( WC_Order::class, $order, 'The inserted auto-draft should be readable before form submission.' );
			$this->assertSame( $expected_value, $order->get_prices_include_tax() );

			update_option( 'woocommerce_prices_include_tax', 'yes' === $option_value ? 'no' : 'yes' );
			wp_cache_flush();

			$this->assertSame( $expected_value, wc_get_order( $order_id )->get_prices_include_tax() );
		} finally {
			if ( $order_id ) {
				wp_delete_post( $order_id, true );
			}
			if ( $had_pagenow ) {
				$GLOBALS['pagenow'] = $previous_pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $GLOBALS['pagenow'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
			OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox A programmatic CPT auto-draft keeps an explicit false tax mode outside Add Order.
	 */
	public function test_programmatic_auto_draft_keeps_explicit_false_prices_include_tax(): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$previous_pagenow    = $GLOBALS['pagenow'] ?? null;
		$had_pagenow         = array_key_exists( 'pagenow', $GLOBALS );
		$order_id            = 0;
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		OrderHelper::toggle_cot_feature_and_usage( false );

		try {
			update_option( 'woocommerce_prices_include_tax', 'yes' );
			$GLOBALS['pagenow'] = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$order_id = wp_insert_post(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'auto-draft',
					'meta_input'  => array(
						'_prices_include_tax' => 'no',
					),
				)
			);

			$this->assertSame( 'no', get_post_meta( $order_id, '_prices_include_tax', true ) );
			$this->assertFalse( wc_get_order( $order_id )->get_prices_include_tax() );
		} finally {
			if ( $order_id ) {
				wp_delete_post( $order_id, true );
			}
			if ( $had_pagenow ) {
				$GLOBALS['pagenow'] = $previous_pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $GLOBALS['pagenow'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
			OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Tax-mode metadata is not added to existing orders or unrelated auto-drafts.
	 */
	public function test_prices_include_tax_is_not_added_to_updates_or_unrelated_auto_drafts(): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$previous_pagenow    = $GLOBALS['pagenow'] ?? null;
		$had_pagenow         = array_key_exists( 'pagenow', $GLOBALS );
		$order_id            = 0;
		$post_id             = 0;
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		OrderHelper::toggle_cot_feature_and_usage( false );

		try {
			update_option( 'woocommerce_prices_include_tax', 'yes' );
			$GLOBALS['pagenow'] = 'post-new.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$order_id = wp_insert_post(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'wc-pending',
				)
			);
			wp_update_post(
				array(
					'ID'          => $order_id,
					'post_status' => 'auto-draft',
				)
			);

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'post',
					'post_status' => 'auto-draft',
				)
			);

			$this->assertFalse( metadata_exists( 'post', $order_id, '_prices_include_tax' ) );
			$this->assertFalse( metadata_exists( 'post', $post_id, '_prices_include_tax' ) );
		} finally {
			if ( $order_id ) {
				wp_delete_post( $order_id, true );
			}
			if ( $post_id ) {
				wp_delete_post( $post_id, true );
			}
			if ( $had_pagenow ) {
				$GLOBALS['pagenow'] = $previous_pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $GLOBALS['pagenow'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
			OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Provides store tax-mode settings and their persisted values.
	 *
	 * @return array<string, array{string, string, bool}>
	 */
	public function provide_prices_include_tax_settings(): array {
		return array(
			'prices entered inclusive of tax' => array( 'yes', 'yes', true ),
			'prices entered exclusive of tax' => array( 'no', 'no', false ),
		);
	}

	/**
	 * Calls the WC_Admin_Meta_Boxes::output_errors() method, capturing and returning the output.
	 *
	 * @return string
	 */
	private function get_meta_box_error_output(): string {
		ob_start();
		$this->sut->output_errors();
		return ob_get_clean();
	}

	/**
	 * Simulates what normally happens when `shutdown` occurs, in relation to the WC_Admin_Meta_Boxes class.
	 * We avoid actually calling `do_action( 'shutdown' )` because we do not have perfect isolation between tests, and
	 * wish to avoid unwanted side-effects unrelated to this set of tests.
	 */
	private function simulate_shutdown() {
		// Previously (prior to 6.5.0), $this->sut->save_errors() would have been called during shutdown.
		$this->sut->append_to_error_store();
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
	}
}
