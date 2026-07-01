<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\BillingAddressSchema;
use WC_Unit_Test_Case;

/**
 * Tests for the BillingAddressSchema class.
 */
class BillingAddressSchemaTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
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
		$formatters->register( 'html', HtmlFormatter::class );

		$extend            = new ExtendSchema( $formatters );
		$schema_controller = new SchemaController( $extend );
		$this->sut         = $schema_controller->get( BillingAddressSchema::IDENTIFIER );
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
