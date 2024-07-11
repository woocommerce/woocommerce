<?php

/**
 * Tests for the block hooks versioning we set in the DB.
 */
class BlockHooksVersionTests extends WC_Unit_Test_Case {
	/**
	 * Option name for storing the block hooks version.
	 *
	 * @var string
	 */
	protected static $option_name = 'woocommerce_hooked_blocks_version';

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		switch_theme( 'default' );
		delete_option( self::$option_name );
	}

	/**
	 * Test wc_hooked_blocks_version option gets set on woocommerce_newly_installed action.
	 *
	 * @return void
	 */
	public function test_block_hooks_version_is_saved_on_install() {
		switch_theme( 'twentytwentytwo' );
		update_option( WC_Install::NEWLY_INSTALLED_OPTION, 'yes' );
		delete_option( 'woocommerce_version' );

		WC_Install::newly_installed();

		$this->assertEquals( WC()->version, get_option( self::$option_name ) );
	}

	/**
	 * Test wc_hooked_blocks_version option gets set on theme switch from a classic to a block theme.
	 *
	 * @return void
	 */
	public function test_block_hooks_version_is_saved_on_theme_switch() {
		$initial_option_value = get_option( self::$option_name );
		switch_theme( 'storefront' );
		switch_theme( 'twentytwentytwo' );

		// This fires the action that sets the block hooks version: 'after_switch_theme'.
		check_theme_switched();
		$this->assertEquals( WC()->version, get_option( self::$option_name ) );
		$this->assertEquals( $initial_option_value, false );
	}
}
