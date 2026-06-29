<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\Edit;
use WP_Screen;

/**
 * Tests for the Edit class — covers hiding the Downloadable product permissions
 * meta box by default on the order edit screen.
 */
class EditTest extends \WC_Unit_Test_Case {

	/**
	 * Screen ID used for the order edit screen in these tests.
	 *
	 * @var string
	 */
	private const TEST_SCREEN_ID = 'woocommerce_page_wc-orders';

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// Remove only the default_hidden_meta_boxes callbacks this test class registers,
		// so we don't clobber callbacks owned by other code or tests.
		remove_all_filters( 'default_hidden_meta_boxes' );

		// Reset the meta boxes registered by add_order_meta_boxes() so global state stays clean.
		unset( $GLOBALS['wp_meta_boxes'][ self::TEST_SCREEN_ID ] );

		parent::tearDown();
	}

	/**
	 * @testdox Should hide the downloads meta box by default on the order edit screen.
	 */
	public function test_downloads_meta_box_is_hidden_by_default_on_order_screen(): void {
		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order' );

		$hidden = $this->apply_default_hidden_filter( self::TEST_SCREEN_ID );

		$this->assertContains(
			'woocommerce-order-downloads',
			$hidden,
			'The downloads meta box should be hidden by default on the order edit screen.'
		);
	}

	/**
	 * @testdox Should not hide the downloads meta box on screens other than the order edit screen.
	 */
	public function test_default_hidden_rule_only_applies_to_the_registered_screen(): void {
		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order' );

		$hidden = $this->apply_default_hidden_filter( 'dashboard' );

		$this->assertNotContains(
			'woocommerce-order-downloads',
			$hidden,
			'The hide-by-default rule must not leak to unrelated admin screens.'
		);
	}

	/**
	 * @testdox Should keep the downloads meta box registered so it stays available in Screen Options.
	 */
	public function test_downloads_meta_box_remains_registered(): void {
		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order' );

		$this->assertTrue(
			$this->is_meta_box_registered( 'woocommerce-order-downloads', self::TEST_SCREEN_ID ),
			'Hiding by default must not unregister the box — it has to remain available to re-enable via Screen Options.'
		);
	}

	/**
	 * Apply the default_hidden_meta_boxes filter as WordPress would, against a given screen.
	 *
	 * @param string $screen_id Screen ID to simulate.
	 * @return array<int, string>
	 */
	private function apply_default_hidden_filter( string $screen_id ): array {
		$screen = WP_Screen::get( $screen_id );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Simulating core WP filter to exercise the meta box visibility rule under test.
		return (array) apply_filters( 'default_hidden_meta_boxes', array(), $screen );
	}

	/**
	 * Whether a meta box id is registered against a screen, in any context or priority.
	 *
	 * @param string $meta_box_id Meta box id to look for.
	 * @param string $screen_id   Screen id whose registered meta boxes are searched.
	 * @return bool
	 */
	private function is_meta_box_registered( string $meta_box_id, string $screen_id ): bool {
		$contexts = $GLOBALS['wp_meta_boxes'][ $screen_id ] ?? array();

		foreach ( $contexts as $priorities ) {
			foreach ( $priorities as $boxes ) {
				if ( is_array( $boxes ) && array_key_exists( $meta_box_id, $boxes ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
