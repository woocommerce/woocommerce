<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Tests for EmailSettingsMigrator's legacy-to-Core email option-name pairing.
 *
 * Legacy `confirm` (the post-signup confirmation email) must map to Core `verified`, not
 * `verify` — `verify` is the double opt-in email and `verified` is the confirmation sent
 * after it, so reversing the two swaps their copy.
 */
class EmailSettingsMigratorTests extends WC_Unit_Test_Case {

	/**
	 * @testdox the OPTION_MAP should pair legacy confirm with Core verified, not verify.
	 */
	public function test_option_map_pairs_confirm_with_verified_not_verify(): void {
		$option_map = $this->get_option_map();

		$this->assertArrayHasKey( 'woocommerce_bis_notification_confirm_settings', $option_map );
		$this->assertSame(
			'woocommerce_customer_stock_notification_verified_settings',
			$option_map['woocommerce_bis_notification_confirm_settings'],
			'Legacy confirm must map to Core verified, not verify.'
		);
	}

	/**
	 * @testdox the OPTION_MAP should pair legacy verify with Core verify, not verified.
	 */
	public function test_option_map_pairs_verify_with_verify_not_verified(): void {
		$option_map = $this->get_option_map();

		$this->assertArrayHasKey( 'woocommerce_bis_notification_verify_settings', $option_map );
		$this->assertSame(
			'woocommerce_customer_stock_notification_verify_settings',
			$option_map['woocommerce_bis_notification_verify_settings']
		);
	}

	/**
	 * @testdox the OPTION_MAP should pair legacy received with Core stock notification settings.
	 */
	public function test_option_map_pairs_received_with_notification_settings(): void {
		$option_map = $this->get_option_map();

		$this->assertArrayHasKey( 'woocommerce_bis_notification_received_settings', $option_map );
		$this->assertSame(
			'woocommerce_customer_stock_notification_settings',
			$option_map['woocommerce_bis_notification_received_settings']
		);
	}

	/**
	 * Read EmailSettingsMigrator::OPTION_MAP via reflection, since it is a private constant
	 * and this pairing has no other public surface to assert against directly.
	 *
	 * @return array<string, string>
	 */
	private function get_option_map(): array {
		$reflection = new ReflectionClass( EmailSettingsMigrator::class );

		return $reflection->getConstant( 'OPTION_MAP' );
	}
}
