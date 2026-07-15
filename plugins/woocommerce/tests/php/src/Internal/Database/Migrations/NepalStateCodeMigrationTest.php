<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Database\Migrations;

use Automattic\WooCommerce\Internal\Database\Migrations\NepalStateCodeMigration;
use WC_Admin_Notices;
use WC_Shipping_Zone;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * Tests for NepalStateCodeMigration.
 */
class NepalStateCodeMigrationTest extends WC_Unit_Test_Case {

	private const NOTICE_NAME = 'nepal_legacy_state_configuration';

	/**
	 * The System Under Test.
	 *
	 * @var NepalStateCodeMigration
	 */
	private $sut;

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
	private $shipping_zone;

	/**
	 * Created tax rate ID.
	 *
	 * @var int|null
	 */
	private $tax_rate_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut                     = wc_get_container()->get( NepalStateCodeMigration::class );
		$this->original_store_location = get_option( 'woocommerce_default_country', false );
		$this->shipping_zone           = null;
		$this->tax_rate_id             = null;

		update_option( 'woocommerce_default_country', 'US:CA' );
		$this->clear_notice();
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

		$this->clear_notice();
		parent::tearDown();
	}

	/**
	 * @testdox No notice is added when operational settings use current state codes.
	 */
	public function test_no_notice_is_added_without_legacy_configuration(): void {
		$this->sut->run();

		$this->assertNotContains( self::NOTICE_NAME, WC_Admin_Notices::get_notices(), 'Current settings should not trigger a legacy-state notice.' );
	}

	/**
	 * @testdox A notice is added when the store location uses a legacy Nepal zone.
	 */
	public function test_notice_is_added_for_legacy_store_location(): void {
		update_option( 'woocommerce_default_country', 'NP:BAG' );

		$this->sut->run();

		$this->assert_notice_added();
	}

	/**
	 * @testdox A notice is added when a shipping zone uses a legacy Nepal zone.
	 */
	public function test_notice_is_added_for_legacy_shipping_zone(): void {
		$this->shipping_zone = new WC_Shipping_Zone();
		$this->shipping_zone->set_zone_name( 'Legacy Nepal zone' );
		$this->shipping_zone->add_location( 'NP:BAG', 'state' );
		$this->shipping_zone->save();

		$this->sut->run();

		$this->assert_notice_added();
	}

	/**
	 * @testdox A notice is added when a tax rate uses a legacy Nepal zone.
	 */
	public function test_notice_is_added_for_legacy_tax_rate(): void {
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

		$this->sut->run();

		$this->assert_notice_added();
	}

	/**
	 * Assert that the migration notice was added.
	 */
	private function assert_notice_added(): void {
		$this->assertContains( self::NOTICE_NAME, WC_Admin_Notices::get_notices(), 'Legacy operational settings should trigger a remediation notice.' );
		$this->assertStringContainsString(
			'Review settings',
			(string) get_option( 'woocommerce_admin_notice_' . self::NOTICE_NAME ),
			'The remediation notice should link to WooCommerce settings.'
		);
	}

	/**
	 * Remove the migration notice and its stored content.
	 */
	private function clear_notice(): void {
		WC_Admin_Notices::remove_notice( self::NOTICE_NAME );
		delete_option( 'woocommerce_admin_notice_' . self::NOTICE_NAME );
	}
}
