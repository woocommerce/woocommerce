<?php
/**
 * \Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\OrderAttribution test class.
 */

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\OrderAttribution;
use WC_Helper_Order;
use WP_UnitTestCase;

/**
 * Tests for the OrderAttribution class.
 */
class OrderAttributionTest extends WP_UnitTestCase {

	/**
	 * System under test.
	 *
	 * @var OrderAttribution
	 */
	private $sut;

	/**
	 * Set up the test fixture.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderAttribution();
	}

	/**
	 * Test default output.
	 *
	 * @return void
	 */
	public function test_default_arguments_passed_into_output() {
		$order = WC_Helper_Order::create_order();

		// Hook into the template to check the args.
		add_action(
			'woocommerce_before_template_part',
			function( $template_name, $template_path, $located, $args ) {
				$this->assertEquals( 'Unknown', $args['meta']['origin'] ?? '' );
				$this->assertFalse( $args['has_more_details'] );
			},
			10,
			4
		);

		ob_start();
		$this->sut->output( $order );
		ob_end_clean();
	}

	/**
	 * Test that additional order meta passed into the output changes `show more details` value.
	 *
	 * @return void
	 */
	public function test_more_than_one_related_meta_affects_show_more() {
		$order = WC_Helper_Order::create_order();
		$order->add_meta_data( '_wc_order_attribution_device_type', 'Desktop' );

		// Hook into the template to check the args.
		add_action(
			'woocommerce_before_template_part',
			function( $template_name, $template_path, $located, $args ) {
				$this->assertEquals( 'Unknown', $args['meta']['origin'] ?? '' );
				$this->assertEquals( 'Desktop', $args['meta']['device_type'] ?? '' );
				$this->assertTrue( $args['has_more_details'] );
			},
			10,
			4
		);

		ob_start();
		$this->sut->output( $order );
		ob_get_contents();
		ob_end_clean();
	}

	/**
	 * Test that additional order meta passed into the output.
	 *
	 * @return void
	 */
	public function test_additional_order_meta_affects_arguments() {
		$order = WC_Helper_Order::create_order();

		$meta = array(
			'_wc_order_attribution_origin'             => 'Referral: WooCommerce.com',
			'_wc_order_attribution_device_type'        => 'Desktop',
			'_wc_order_attribution_user_agent'         => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
			'_wc_order_attribution_session_count'      => 1,
			'_wc_order_attribution_session_pages'      => 4,
			'_wc_order_attribution_session_start_time' => '2023-11-16 13:47:50',
			'_wc_order_attribution_session_entry'      => 'https://wordpress.ddev.site/product/belt/',
			'_wc_order_attribution_utm_content'        => '/',
			'_wc_order_attribution_utm_medium'         => 'referral',
			'_wc_order_attribution_utm_source'         => 'woocommerce.com',
			'_wc_order_attribution_referrer'           => 'https://woocommerce.com/',
			'_wc_order_attribution_source_type'        => 'referral',
		);
		foreach ( $meta as $key => $value ) {
			$order->add_meta_data( $key, $value );
		}

		// Hook into the template to check the args.
		add_action(
			'woocommerce_before_template_part',
			function( $template_name, $template_path, $located, $args ) {
				$this->assertEquals( 'Referral: Woocommerce.com', $args['meta']['origin'] ?? '' );
				$this->assertEquals( 'Desktop', $args['meta']['device_type'] ?? '' );
				$this->assertEquals(
					'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
					$args['meta']['user_agent'] ?? ''
				);
				$this->assertEquals( 1, $args['meta']['session_count'] ?? '' );
				$this->assertEquals( 4, $args['meta']['session_pages'] ?? '' );
				$this->assertEquals( '2023-11-16 13:47:50', $args['meta']['session_start_time'] ?? '' );
				$this->assertEquals( 'https://wordpress.ddev.site/product/belt/', $args['meta']['session_entry'] ?? '' );
				$this->assertEquals( '/', $args['meta']['utm_content'] ?? '' );
				$this->assertEquals( 'referral', $args['meta']['utm_medium'] ?? '' );
				$this->assertEquals( 'woocommerce.com', $args['meta']['utm_source'] ?? '' );
				$this->assertEquals( 'https://woocommerce.com/', $args['meta']['referrer'] ?? '' );
				$this->assertEquals( 'referral', $args['meta']['source_type'] ?? '' );

				$this->assertTrue( $args['has_more_details'] );
			},
			10,
			4
		);

		ob_start();
		$this->sut->output( $order );
		ob_get_contents();
		ob_end_clean();
	}
}
