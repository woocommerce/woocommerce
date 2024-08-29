<?php

namespace Automattic\WooCommerce\Tests\ComingSoon;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;

/**
 * Tests for the coming soon helper class.
 */
class ComingSoonHelperTest extends \WC_Unit_Test_Case {


	/**
	 * System under test.
	 *
	 * @var ComingSoonHelper;
	 */
	private $sut;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ComingSoonHelper::class );
	}

	/**
	 * @testdox Test is_site_live() behavior when coming soon option is no.
	 */
	public function test_is_site_live_when_coming_soon_is_no() {
		update_option( 'woocommerce_coming_soon', 'no' );
		$this->assertTrue( $this->sut->is_site_live() );
	}

	/**
	 * @testdox Test is_site_live() behavior when coming soon option is yes.
	 */
	public function test_is_site_live_when_coming_soon_is_yes() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		$this->assertFalse( $this->sut->is_site_live() );
	}

	/**
	 * @testdox Test is_site_live() behavior when coming soon option is not available.
	 */
	public function test_is_site_live_when_coming_soon_is_na() {
		delete_option( 'woocommerce_coming_soon' );
		$this->assertTrue( $this->sut->is_site_live() );
	}

	/**
	 * @testdox Test is_site_coming_soon() behavior when coming soon option is no.
	 */
	public function test_is_site_coming_soon_when_coming_soon_is_no() {
		update_option( 'woocommerce_coming_soon', 'no' );
		$this->assertFalse( $this->sut->is_site_coming_soon() );
	}

	/**
	 * @testdox Test is_site_coming_soon() behavior when coming soon option is not available.
	 */
	public function test_is_site_coming_soon_when_coming_soon_is_na() {
		delete_option( 'woocommerce_coming_soon', 'no' );
		$this->assertFalse( $this->sut->is_site_coming_soon() );
	}

	/**
	 * @testdox Test is_site_coming_soon() behavior when store pages only option is no.
	 */
	public function test_is_site_coming_soon_when_store_pages_only_is_no() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'no' );
		$this->assertTrue( $this->sut->is_site_coming_soon() );
	}

	/**
	 * @testdox Test is_site_coming_soon() behavior when store pages only option is yes.
	 */
	public function test_is_site_coming_soon_when_store_pages_only_is_yes() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'yes' );
		$this->assertFalse( $this->sut->is_site_coming_soon() );
	}

	/**
	 * @testdox Test is_store_coming_soon() behavior when coming soon option is no.
	 */
	public function test_is_srote_coming_soon_when_coming_soon_is_no() {
		update_option( 'woocommerce_coming_soon', 'no' );
		$this->assertFalse( $this->sut->is_site_coming_soon() );
	}

	/**
	 * @testdox Test is_store_coming_soon() behavior when coming soon option is not available.
	 */
	public function test_is_store_coming_soon_when_coming_soon_is_na() {
		delete_option( 'woocommerce_coming_soon', 'no' );
		$this->assertFalse( $this->sut->is_store_coming_soon() );
	}

	/**
	 * @testdox Test is_store_coming_soon() behavior when store pages only option is no.
	 */
	public function test_is_store_coming_soon_when_store_pages_only_is_no() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'no' );
		$this->assertFalse( $this->sut->is_store_coming_soon() );
	}

	/**
	 * @testdox Test is_store_coming_soon() behavior when store pages only option is yes.
	 */
	public function test_is_store_coming_soon_when_store_pages_only_is_yes() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'yes' );
		$this->assertTrue( $this->sut->is_store_coming_soon() );
	}
}
