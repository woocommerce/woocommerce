<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

/**
 * Integration test for Dependency_Check, which MailPoet calls to decide whether to offer the editor.
 */
class Dependency_Check_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * WordPress version before the test.
	 *
	 * @var string
	 */
	private string $original_wp_version;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_wp_version = get_bloginfo( 'version' );
	}

	/**
	 * Restore the WordPress version.
	 */
	public function tearDown(): void {
		$GLOBALS['wp_version'] = $this->original_wp_version; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restores the test's change.
		parent::tearDown();
	}

	/**
	 * Test the editor's dependencies are met from the minimum WordPress version on.
	 *
	 * @testWith ["6.6.2", false]
	 *           ["6.7", true]
	 *           ["7.1", true]
	 *
	 * @param string $wp_version WordPress version.
	 * @param bool   $expected   Whether the dependencies are met.
	 */
	public function testItRequiresTheMinimumWordPressVersion( string $wp_version, bool $expected ): void {
		$GLOBALS['wp_version'] = $wp_version; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- sets the version under test.

		$this->assertSame( $expected, ( new Dependency_Check() )->are_dependencies_met() );
	}
}
