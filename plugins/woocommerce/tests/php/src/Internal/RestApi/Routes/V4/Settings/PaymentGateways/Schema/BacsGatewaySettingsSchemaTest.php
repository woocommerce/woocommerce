<?php
/**
 * Tests bank account country preservation in the v4 settings API.
 *
 * @package WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema\BacsGatewaySettingsSchema;
use PHPUnit\Framework\TestCase;
use WC_Payment_Gateway;

/**
 * Tests the account validator without gateway construction or database fixtures.
 */
class BacsGatewaySettingsSchemaTest extends TestCase {

	/**
	 * @testdox A bank account keeps its sanitized country when gateway settings are saved.
	 */
	public function test_preserves_account_country(): void {
		$sut     = new BacsGatewaySettingsSchema();
		$gateway = $this->createStub( WC_Payment_Gateway::class );

		$validated = $sut->validate_and_sanitize_special_fields(
			$gateway,
			array(
				'account_details' => array(
					array(
						'account_name' => 'Merchant',
						'country_code' => '<b>GB</b>',
					),
				),
			)
		);

		$this->assertIsArray( $validated );
		$this->assertSame( 'GB', $validated['account_details'][0]['country_code'] );
	}

	/**
	 * @testdox Selecting a country alone does not create an empty bank account.
	 */
	public function test_ignores_accounts_with_only_a_country(): void {
		$sut     = new BacsGatewaySettingsSchema();
		$gateway = $this->createStub( WC_Payment_Gateway::class );

		$validated = $sut->validate_and_sanitize_special_fields(
			$gateway,
			array( 'account_details' => array( array( 'country_code' => 'GB' ) ) )
		);

		$this->assertSame( array( 'account_details' => array() ), $validated );
	}

	/**
	 * @testdox A legacy bank account without a country does not gain an empty country field.
	 */
	public function test_preserves_legacy_accounts_without_country(): void {
		$sut     = new BacsGatewaySettingsSchema();
		$gateway = $this->createStub( WC_Payment_Gateway::class );

		$validated = $sut->validate_and_sanitize_special_fields(
			$gateway,
			array( 'account_details' => array( array( 'account_name' => 'Merchant' ) ) )
		);

		$this->assertIsArray( $validated );
		$this->assertArrayNotHasKey( 'country_code', $validated['account_details'][0] );
	}
}
