<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use WC_Unit_Test_Case;
use WP_Filesystem_Base;

/**
 * FilesystemUtilTest class.
 */
class FilesystemUtilTest extends WC_Unit_Test_Case {
	/**
	 * Set up before running any tests.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		unset( $GLOBALS['wp_filesystem'] );
	}

	/**
	 * Tear down between each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset( $GLOBALS['wp_filesystem'] );

		parent::tearDown();
	}

	/**
	 * @testdox Check that the get_wp_filesystem method returns an appropriate class instance.
	 */
	public function test_get_wp_filesystem_success(): void {
		$callback = fn() => 'direct';
		add_filter( 'filesystem_method', $callback );

		$this->assertInstanceOf( WP_Filesystem_Base::class, FilesystemUtil::get_wp_filesystem() );

		remove_filter( 'filesystem_method', $callback );
	}

	/**
	 * @testdox Check that the get_wp_filesystem method throws an exception when the filesystem cannot be initialized.
	 */
	public function test_get_wp_filesystem_failure(): void {
		$this->expectException( 'Exception' );

		$callback = fn() => 'asdf';
		add_filter( 'filesystem_method', $callback );

		FilesystemUtil::get_wp_filesystem();

		remove_filter( 'filesystem_method', $callback );
	}
}
