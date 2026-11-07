<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Internal\Admin\Screens\Settings\SettingsScreen;
use WC_Unit_Test_Case;

/**
 * Tests for the SettingsScreen class.
 */
class SettingsScreenTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SettingsScreen
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new SettingsScreen();
		add_action( 'admin_notices', '__return_null' );
		add_action( 'all_admin_notices', '__return_null' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET['page'] );
		parent::tearDown();
	}

	/**
	 * @testdox Should remove admin notices on the settings screen.
	 */
	public function test_removes_admin_notices_on_the_screen(): void {
		$_GET['page'] = 'wc-settings-screen-wp-admin';

		$this->sut->handle_in_admin_header();

		$this->assertFalse( has_action( 'admin_notices' ), 'admin_notices callbacks should be removed' );
		$this->assertFalse( has_action( 'all_admin_notices' ), 'all_admin_notices callbacks should be removed' );
	}

	/**
	 * @testdox Should keep admin notices on other admin pages.
	 */
	public function test_keeps_admin_notices_on_other_pages(): void {
		$_GET['page'] = 'wc-settings';

		$this->sut->handle_in_admin_header();

		$this->assertNotFalse( has_action( 'admin_notices', '__return_null' ), 'admin_notices callbacks should stay' );
		$this->assertNotFalse( has_action( 'all_admin_notices', '__return_null' ), 'all_admin_notices callbacks should stay' );
	}

	/**
	 * @testdox Should keep the WooCommerce admin header off the settings screen.
	 */
	public function test_disconnects_the_screen_from_the_admin_header(): void {
		$_GET['page'] = 'wc-settings-screen-wp-admin';

		$this->assertFalse( $this->sut->handle_is_connected_page( true ) );
	}

	/**
	 * @testdox Should leave the admin header setting unchanged on other admin pages.
	 */
	public function test_keeps_other_pages_connected(): void {
		$_GET['page'] = 'wc-settings';

		$this->assertTrue( $this->sut->handle_is_connected_page( true ) );
	}
}
