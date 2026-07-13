<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Schemas\V1\BillingAddressSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use WC_Unit_Test_Case;

/**
 * Tests that AbstractAddressSchema::sanitize_callback() does not strip
 * backslashes from address fields.
 *
 * The Store API reads a JSON request body via json_decode(), which is never
 * subject to WordPress "magic quotes". Calling wp_unslash() on that data used
 * to silently drop real backslashes the user typed (e.g. "apt 4\"). These
 * tests guard against a regression of that behaviour.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/58214
 */
class AbstractAddressSchemaTest extends WC_Unit_Test_Case {

	/**
	 * The system under test. BillingAddressSchema is the concrete child of
	 * AbstractAddressSchema and exercises the shared sanitize_callback().
	 *
	 * @var BillingAddressSchema
	 */
	private $sut;

	/**
	 * Set up before test.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$extend            = new ExtendSchema( $formatters );
		$schema_controller = new SchemaController( $extend );
		$this->sut         = $schema_controller->get( BillingAddressSchema::IDENTIFIER );
	}

	/**
	 * Build a minimal valid address with the given overrides.
	 *
	 * @param array $overrides Fields to override on the base address.
	 * @return array
	 */
	private function make_address( array $overrides = array() ): array {
		return array_merge(
			array(
				'first_name' => 'Jane',
				'last_name'  => 'Doe',
				'company'    => '',
				'address_1'  => '123 Main Street',
				'address_2'  => '',
				'city'       => 'New York',
				'state'      => 'NY',
				'postcode'   => '10001',
				'country'    => 'US',
				'phone'      => '',
				'email'      => 'jane@example.com',
			),
			$overrides
		);
	}

	/**
	 * @testdox Should preserve a trailing backslash in address_2.
	 */
	public function test_preserves_trailing_backslash_in_address_2(): void {
		$address = $this->make_address( array( 'address_2' => 'apt 4\\' ) );

		$result = $this->sut->sanitize_callback( $address, null, 'billing_address' );

		$this->assertSame( 'apt 4\\', $result['address_2'], 'A trailing backslash should not be stripped.' );
	}

	/**
	 * @testdox Should preserve a mid-string backslash in address_1.
	 */
	public function test_preserves_mid_string_backslash_in_address_1(): void {
		$address = $this->make_address( array( 'address_1' => 'a\\b Street' ) );

		$result = $this->sut->sanitize_callback( $address, null, 'billing_address' );

		$this->assertSame( 'a\\b Street', $result['address_1'], 'A mid-string backslash should not be stripped.' );
	}

	/**
	 * @testdox Should not corrupt plain backslash-free fields.
	 */
	public function test_does_not_corrupt_backslash_free_fields(): void {
		$address = $this->make_address(
			array(
				'address_1' => '123 Main Street',
				'address_2' => 'Suite 100',
			)
		);

		$result = $this->sut->sanitize_callback( $address, null, 'billing_address' );

		$this->assertSame( '123 Main Street', $result['address_1'], 'A plain field should be unchanged.' );
		$this->assertSame( 'Suite 100', $result['address_2'], 'A plain field should be unchanged.' );
	}

	/**
	 * @testdox Should not texturize billing email addresses in API responses.
	 */
	public function test_get_item_response_does_not_texturize_billing_email_address(): void {
		$customer = new \WC_Customer();
		$customer->set_billing_email( 'info@48x17.com' );

		$result = $this->sut->get_item_response( $customer );

		$this->assertArrayHasKey( 'email', $result );
		$this->assertSame(
			'info@48x17.com',
			$result['email'],
			'Billing email addresses should be returned as raw data, not typographic display text.'
		);
	}
}
