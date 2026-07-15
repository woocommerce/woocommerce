<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Address;

use Automattic\WooCommerce\Internal\Address\NepalStateCodeRemediation;
use WC_Shipping_Zone;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * Tests for NepalStateCodeRemediation.
 */
class NepalStateCodeRemediationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NepalStateCodeRemediation
	 */
	private NepalStateCodeRemediation $sut;

	/**
	 * Original store location.
	 *
	 * @var string|false
	 */
	private $original_store_location;

	/**
	 * Created shipping zone.
	 *
	 * @var WC_Shipping_Zone|null
	 */
	private ?WC_Shipping_Zone $shipping_zone;

	/**
	 * Created tax rate ID.
	 *
	 * @var int|null
	 */
	private ?int $tax_rate_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut                     = wc_get_container()->get( NepalStateCodeRemediation::class );
		$this->original_store_location = get_option( 'woocommerce_default_country', false );
		$this->shipping_zone           = null;
		$this->tax_rate_id             = null;

		update_option( 'woocommerce_default_country', 'US:CA' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->shipping_zone ) {
			$this->shipping_zone->delete();
		}

		if ( $this->tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $this->tax_rate_id );
		}

		if ( false === $this->original_store_location ) {
			delete_option( 'woocommerce_default_country' );
		} else {
			update_option( 'woocommerce_default_country', $this->original_store_location );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Current operational settings produce a clean status.
	 */
	public function test_status_is_clean_without_legacy_configuration(): void {
		$this->assertSame( array(), $this->sut->get_status(), 'Current settings should not require remediation.' );
	}

	/**
	 * @testdox Legacy configuration is detected every time the status is evaluated.
	 */
	public function test_status_dynamically_detects_and_clears_legacy_store_location(): void {
		update_option( 'woocommerce_default_country', 'NP:BAG' );

		$this->assertSame( array( 'store_location' => true ), $this->sut->get_status(), 'Legacy store locations should require remediation.' );

		update_option( 'woocommerce_default_country', 'NP:P3' );

		$this->assertSame( array(), $this->sut->get_status(), 'The status should clear as soon as the configuration uses a current code.' );
	}

	/**
	 * @testdox Detection remains active when an extension disables address aliases.
	 */
	public function test_status_uses_known_codes_independently_of_compatibility_filter(): void {
		update_option( 'woocommerce_default_country', 'NP:BAG' );
		add_filter( 'woocommerce_legacy_state_codes', '__return_empty_array' );

		try {
			$this->assertSame( array( 'store_location' => true ), $this->sut->get_status(), 'Disabling address aliases should not hide stale operational configuration.' );
		} finally {
			remove_filter( 'woocommerce_legacy_state_codes', '__return_empty_array' );
		}
	}

	/**
	 * @testdox Legacy shipping zones and tax rates are detected.
	 */
	public function test_status_detects_legacy_shipping_and_tax_configuration(): void {
		$this->shipping_zone = new WC_Shipping_Zone();
		$this->shipping_zone->set_zone_name( 'Legacy Nepal zone' );
		$this->shipping_zone->add_location( 'NP:BAG', 'state' );
		$this->shipping_zone->save();

		$this->tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'NP',
				'tax_rate_state'    => 'BAG',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Legacy Nepal tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$this->assertSame(
			array(
				'shipping_zones' => true,
				'tax_rates'      => true,
			),
			$this->sut->get_status(),
			'Legacy operational rules should be reported.'
		);
	}

	/**
	 * @testdox Database query failures are reported instead of appearing clean.
	 */
	public function test_status_reports_database_query_failures(): void {
		global $wpdb;

		$original_prefix = $wpdb->prefix;
		$wpdb->prefix    = 'missing_woocommerce_test_';

		try {
			$this->assertSame( array( 'database_error' => true ), $this->sut->get_status(), 'Database failures should produce an inconclusive status.' );
		} finally {
			$wpdb->prefix = $original_prefix;
			$wpdb->flush();
		}
	}
}
