<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Database;

use WC_Install;

/**
 * Tests for the block hooks versioning we set in the DB.
 */
class StoreNoticeOnThemeSwitch extends \WC_Unit_Test_Case {
	/**
	 * Option name for storing whether the Store Notice was active in a classic theme.
	 *
	 * @var string
	 */
	protected static $enable_store_notice_in_classic_theme_option = 'woocommerce_enable_store_notice_in_classic_theme';

	/**
	 * Option name for storing whether the Store Notice is active.
	 *
	 * @var string
	 */
	protected static $is_store_notice_active_option = 'woocommerce_demo_store';

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		switch_theme( 'storefront' );
		delete_option( self::$enable_store_notice_in_classic_theme_option );
		delete_option( self::$is_store_notice_active_option );
	}

	/**
	 * Test the Store Notice gets disabled when switching from a classic theme to a block theme
	 * and then re-enabled when switching back to a classic theme.
	 *
	 * @return void
	 */
	public function test_store_notice_is_disabled_when_switching_from_classic_to_block_theme() {
		update_option( self::$is_store_notice_active_option, 'yes' );
		switch_theme( 'twentytwentyfour' );
		// This fires the 'after_switch_theme' action.
		check_theme_switched();

		$this->assertEquals( 'no', get_option( self::$is_store_notice_active_option ), 'Store Notice should be disabled when switching from a classic theme to a block theme.' );

		switch_theme( 'storefront' );
		check_theme_switched();

		$this->assertEquals( 'yes', get_option( self::$is_store_notice_active_option ), 'Store Notice should be enabled when switching back to a classic theme that had the Store Notice enabled.' );
	}

	/**
	 * Test the Store Notice gets disabled when switching from a classic theme to a block theme
	 * and then re-enabled when switching back to a classic theme.
	 *
	 * @return void
	 */
	public function test_store_notice_is_not_enabled_when_switching_back_to_classic_theme() {
		update_option( self::$is_store_notice_active_option, 'no' );
		switch_theme( 'twentytwentyfour' );
		check_theme_switched();

		$this->assertEquals( 'no', get_option( self::$is_store_notice_active_option ), 'Store Notice should be disabled when switching from a classic theme to a block theme.' );

		switch_theme( 'storefront' );
		check_theme_switched();

		$this->assertEquals( 'no', get_option( self::$is_store_notice_active_option ), 'Store Notice should not be enabled when switching back to a classic theme that had the Store Notice disabled.' );
	}
}
