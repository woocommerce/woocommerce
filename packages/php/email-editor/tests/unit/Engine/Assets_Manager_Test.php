<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;

/**
 * Unit test class for Assets_Manager.
 */
class Assets_Manager_Test extends \Email_Editor_Unit_Test {
	/**
	 * Test that render_email_editor_html outputs the editor container once and nothing on repeated calls.
	 *
	 * Integrations render from the `replace_editor` filter, which can re-enter while
	 * admin-header.php runs (a plugin calling WP_Screen::get() from admin_enqueue_scripts
	 * re-fires the filter); the second call must not echo another editor container.
	 */
	public function testItRendersEditorHtmlOnlyOnce(): void {
		$this->define_abspath_with_stub_admin_header();

		$assets_manager = new Assets_Manager(
			$this->createMock( Settings_Controller::class ),
			$this->createMock( Theme_Controller::class ),
			$this->createMock( User_Theme::class ),
			$this->createMock( Email_Editor_Logger::class )
		);

		ob_start();
		$assets_manager->render_email_editor_html();
		$first_output = ob_get_clean();
		$this->assertStringContainsString( 'id="woocommerce-email-editor"', (string) $first_output, 'The first render call must output the editor container' );

		ob_start();
		$assets_manager->render_email_editor_html();
		$this->assertSame( '', ob_get_clean(), 'A repeated render call must not output a second editor container' );
	}

	/**
	 * Point ABSPATH at a temp directory with a stub wp-admin/admin-header.php so
	 * render_email_editor_html() can run outside a WordPress install.
	 */
	private function define_abspath_with_stub_admin_header(): void {
		if ( defined( 'ABSPATH' ) ) {
			if ( ! file_exists( ABSPATH . 'wp-admin/admin-header.php' ) ) {
				$this->markTestSkipped( 'ABSPATH is already defined and has no admin-header.php to stub.' );
			}
			return;
		}

		$abspath = sys_get_temp_dir() . '/email-editor-assets-manager-test-' . uniqid() . '/';
		mkdir( $abspath . 'wp-admin', 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- No WP_Filesystem in package unit tests.
		file_put_contents( $abspath . 'wp-admin/admin-header.php', "<?php\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- No WP_Filesystem in package unit tests.
		define( 'ABSPATH', $abspath );
	}
}
