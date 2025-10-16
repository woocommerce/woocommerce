<?php
/**
 * Unit tests for WC_Gateway_Paypal_Notices class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Notices as PayPalNotices;

/**
 * Class WC_Gateway_Paypal_Notices_Test
 */
class NoticesTests extends \WC_Unit_Test_Case {
	/**
	 * Tests for `add_paypal_migration_notice` method.
	 *
	 * @param array  $current_user_can Capabilities for current user.
	 * @param bool   $gateway_available Whether the gateway is available.
	 * @param bool   $notice_dismissed Whether the notice has been dismissed.
	 * @param string $expected Expected output.
	 * @return void
	 *
	 * @dataProvider provide_test_add_paypal_migration_notice
	 */
	public function test_add_paypal_migration_notice( array $current_user_can, bool $gateway_available, bool $notice_dismissed, string $expected ): void {
		$filter_callback = fn() => $current_user_can;
		add_filter( 'user_has_cap', $filter_callback );

		if ( $gateway_available ) {
			update_option(
				'woocommerce_paypal_settings',
				array(
					'enabled'      => 'yes',
					'_should_load' => 'yes',
				)
			);
		} else {
			update_option( 'woocommerce_paypal_settings', array() );
		}

		$user_id = $this->factory->user->create(
			array(
				'user_login' => uniqid(),
				'role'       => 'editor',
			)
		);
		wp_set_current_user( $user_id );

		update_user_meta( $user_id, 'dismissed_paypal_migration_completed_notice', $notice_dismissed );

		$mocked_gateway = $this->getMockBuilder( \WC_Gateway_Paypal::class )
			->setMethods( array( 'should_use_orders_v2' ) )
			->getMock();
		$mocked_gateway->method( 'should_use_orders_v2' )
			->willReturn( true );

		\WC_Gateway_Paypal::set_instance( $mocked_gateway );

		$notices = new PayPalNotices();

		ob_start();
		$notices->add_paypal_migration_notice();
		$output = ob_get_clean();

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
		update_option( 'woocommerce_paypal_settings', array() );
		delete_user_meta( $user_id, 'dismissed_paypal_migration_completed_notice' );

		if ( empty( $expected ) ) {
			$this->assertEmpty( $output );
		} else {
			$this->assertStringContainsString( $expected, $output );
		}
	}

	/**
	 * Data provider for `test_add_paypal_migration_notice` method.
	 *
	 * @return array
	 */
	public function provide_test_add_paypal_migration_notice(): array {
		return array(
			'user cannot manage site' => array(
				'current user can'  => array(),
				'gateway available' => true,
				'notice dismissed'  => false,
				'expected'          => '',
			),
			'gateway not available'   => array(
				'current user can'  => array(
					'manage_woocommerce' => true,
					'manage_options'     => true,
				),
				'gateway available' => false,
				'notice dismissed'  => false,
				'expected'          => '',
			),
			'notice dismissed'        => array(
				'current user can'  => array(
					'manage_woocommerce' => true,
					'manage_options'     => true,
				),
				'gateway available' => true,
				'notice dismissed'  => true,
				'expected'          => '',
			),
			'notice shown'            => array(
				'current user can'  => array(
					'manage_woocommerce' => true,
					'manage_options'     => true,
				),
				'gateway available' => true,
				'notice dismissed'  => false,
				'expected'          => '<p>WooCommerce has upgraded your PayPal integration from PayPal Standard to PayPal Payments (PPCP), for a more reliable and modern checkout experience. If you do not prefer the upgraded integration in WooCommerce, we recommend switching to <a href="https://woocommerce.com/document/woocommerce-paypal-payments/paypal-payments-upgrade-guide/" target="_blank" rel="noopener noreferrer">PayPal Payments</a> extension.</p>',
			),
		);
	}

	/**
	 * Tests for `add_paypal_migration_notice_on_payments_settings_page` method.
	 *
	 * @param bool $is_settings_page Whether the current page is the payments settings page.
	 * @param bool $expected_to_render Whether the notice is expected to render.
	 * @return void
	 *
	 * @dataProvider provide_test_add_paypal_migration_notice_on_payments_settings_page
	 */
	public function test_add_paypal_migration_notice_on_payments_settings_page( bool $is_settings_page, bool $expected_to_render ): void {
		if ( $is_settings_page ) {
			set_current_screen( 'woocommerce_page_wc-settings' );
			global $current_tab, $current_section;
			$current_tab     = 'checkout';
			$current_section = '';
		} else {
			set_current_screen( 'dashboard' );
		}

		$mocked_notices = $this->getMockBuilder( PayPalNotices::class )
			->setMethods( array( 'add_paypal_migration_notice' ) )
			->getMock();
		$mocked_notices->expects( $expected_to_render ? $this->once() : $this->never() )
			->method( 'add_paypal_migration_notice' )
			->willReturn( null );

		$mocked_notices->add_paypal_migration_notice_on_payments_settings_page();
	}

	/**
	 * Data provider for `test_add_paypal_migration_notice_on_payments_settings_page` method.
	 *
	 * @return array
	 */
	public function provide_test_add_paypal_migration_notice_on_payments_settings_page(): array {
		return array(
			'not settings page' => array(
				'is settings page'   => false,
				'expected to render' => false,
			),
			'is settings page'  => array(
				'is settings page'   => true,
				'expected to render' => true,
			),
		);
	}
}
