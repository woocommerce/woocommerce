<?php
declare( strict_types = 1 );

/**
 * Tests for WC_WCCOM_Site_Installation_Step_Move_Product.
 */
class WC_WCCOM_Site_Installation_Step_Move_Product_Test extends WC_Unit_Test_Case {

	private const SLUG = 'wc-move-product-test-extension';

	/**
	 * Directory the fixture package is unpacked into.
	 *
	 * @var string
	 */
	private $unpacked_path;

	/**
	 * Load the installer classes, which WooCommerce only loads for WooCommerce.com requests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once WC_ABSPATH . 'includes/wccom-site/class-wc-wccom-site-installer.php';
		require_once WC_ABSPATH . 'includes/wccom-site/rest-api/class-wc-rest-wccom-site-installer-error-codes.php';
		require_once WC_ABSPATH . 'includes/wccom-site/rest-api/class-wc-rest-wccom-site-installer-error.php';
		require_once WC_ABSPATH . 'includes/wccom-site/installation/class-wc-wccom-site-installation-state.php';
		require_once WC_ABSPATH . 'includes/wccom-site/installation/installation-steps/interface-installaton-step.php';
		require_once WC_ABSPATH . 'includes/wccom-site/installation/installation-steps/class-wc-wccom-site-installation-step-move-product.php';
	}

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->unpacked_path = trailingslashit( WP_CONTENT_DIR ) . 'upgrade/' . self::SLUG . '-' . wp_generate_password( 8, false );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		try {
			// Files on disk, which the database rollback doesn't cover.
			foreach ( array( $this->unpacked_path, WP_PLUGIN_DIR . '/' . self::SLUG, get_theme_root() . '/' . self::SLUG ) as $dir ) {
				$this->delete_dir( $dir );
			}
			wp_clean_plugins_cache();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Requirements a package can declare, and the error the step should report.
	 *
	 * @return array
	 */
	public function unmet_requirements(): array {
		return array(
			'plugin needing a newer WordPress' => array( 'plugin', '99.0', '', 'incompatible_wp_required_version', '/This extension needs WordPress 99\.0 or later, and this site runs WordPress \d/' ),
			'plugin needing a newer PHP'       => array( 'plugin', '', '99.0', 'incompatible_php_required_version', '/This extension needs PHP 99\.0 or later, and this server runs PHP \d/' ),
			'theme needing a newer WordPress'  => array( 'theme', '99.0', '', 'incompatible_wp_required_version', '/This extension needs WordPress 99\.0 or later/' ),
			'theme needing a newer PHP'        => array( 'theme', '', '99.0', 'incompatible_php_required_version', '/This extension needs PHP 99\.0 or later/' ),
		);
	}

	/**
	 * @testdox The step stops before installing a package the site can't run, and says why: $_dataName.
	 * @dataProvider unmet_requirements
	 *
	 * @param string $type          'plugin' or 'theme'.
	 * @param string $requires_wp   Requires at least header.
	 * @param string $requires_php  Requires PHP header.
	 * @param string $expected_code Expected installer error code.
	 * @param string $message_regex Pattern the error message should match.
	 */
	public function test_stops_install_when_requirements_are_not_met( string $type, string $requires_wp, string $requires_php, string $expected_code, string $message_regex ): void {
		$this->unpack_fixture( $type, $requires_wp, $requires_php );

		$error = null;
		try {
			( new WC_WCCOM_Site_Installation_Step_Move_Product( $this->state( $type ) ) )->run();
		} catch ( WC_REST_WCCOM_Site_Installer_Error $e ) {
			$error = $e;
		}

		$this->assertNotNull( $error, 'The step should fail' );
		$this->assertSame( $expected_code, $error->get_error_code() );
		$this->assertMatchesRegularExpression( $message_regex, $error->get_error_message(), 'The message should name the required and the current version' );
		$this->assertDirectoryDoesNotExist( $this->destination( $type ), 'Nothing should be installed' );
		$this->assertDirectoryDoesNotExist( $this->unpacked_path, 'The unpacked package should be removed' );
	}

	/**
	 * @testdox The step installs a package whose requirements the site meets, or that declares none.
	 * @testWith ["1.0", "5.6"]
	 *           ["", ""]
	 *
	 * @param string $requires_wp  Requires at least header.
	 * @param string $requires_php Requires PHP header.
	 */
	public function test_installs_when_requirements_are_met( string $requires_wp, string $requires_php ): void {
		$this->unpack_fixture( 'plugin', $requires_wp, $requires_php );

		$state = ( new WC_WCCOM_Site_Installation_Step_Move_Product( $this->state( 'plugin' ) ) )->run();

		$this->assertFileExists( $this->destination( 'plugin' ) . '/' . self::SLUG . '.php' );
		$this->assertSame( trailingslashit( $this->destination( 'plugin' ) ), trailingslashit( $state->get_installed_path() ) );
	}

	/**
	 * @testdox A copy that's already installed is reused, even when the new package needs a newer WordPress.
	 */
	public function test_reuses_an_installed_copy_without_checking_the_new_package(): void {
		$this->write_extension( 'plugin', $this->destination( 'plugin' ), '', '' );
		wp_clean_plugins_cache();
		$this->unpack_fixture( 'plugin', '99.0', '' );

		$state = ( new WC_WCCOM_Site_Installation_Step_Move_Product( $this->state( 'plugin' ) ) )->run();

		$this->assertSame( trailingslashit( $this->destination( 'plugin' ) ), trailingslashit( $state->get_installed_path() ), 'The installed copy should be used, as before' );
	}

	/**
	 * Installation state for the fixture product.
	 *
	 * @param string $type 'plugin' or 'theme'.
	 * @return WC_WCCOM_Site_Installation_State
	 */
	private function state( string $type ): WC_WCCOM_Site_Installation_State {
		$state = WC_WCCOM_Site_Installation_State::initiate_new( 123, 'test-key' );
		$state->set_product_type( $type );
		$state->set_unpacked_path( $this->unpacked_path );

		return $state;
	}

	/**
	 * Where the fixture would be installed.
	 *
	 * @param string $type 'plugin' or 'theme'.
	 * @return string
	 */
	private function destination( string $type ): string {
		return ( 'plugin' === $type ? WP_PLUGIN_DIR : get_theme_root() ) . '/' . self::SLUG;
	}

	/**
	 * Lay out an unpacked package, as WP_Upgrader::unpack_package() leaves it.
	 *
	 * @param string $type         'plugin' or 'theme'.
	 * @param string $requires_wp  Requires at least header.
	 * @param string $requires_php Requires PHP header.
	 */
	private function unpack_fixture( string $type, string $requires_wp, string $requires_php ): void {
		$this->write_extension( $type, $this->unpacked_path . '/' . self::SLUG, $requires_wp, $requires_php );
	}

	/**
	 * Write a minimal plugin or theme.
	 *
	 * @param string $type         'plugin' or 'theme'.
	 * @param string $dir          Directory to write into.
	 * @param string $requires_wp  Requires at least header.
	 * @param string $requires_php Requires PHP header.
	 */
	private function write_extension( string $type, string $dir, string $requires_wp, string $requires_php ): void {
		wp_mkdir_p( $dir );

		$headers  = '' !== $requires_wp ? " * Requires at least: {$requires_wp}\n" : '';
		$headers .= '' !== $requires_php ? " * Requires PHP: {$requires_php}\n" : '';

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixtures.
		if ( 'plugin' === $type ) {
			file_put_contents( $dir . '/' . self::SLUG . '.php', "<?php\n/**\n * Plugin Name: Move product test extension\n * Version: 1.0.0\n{$headers} */\n" );
		} else {
			file_put_contents( $dir . '/style.css', "/*\n * Theme Name: Move product test extension\n * Version: 1.0.0\n{$headers} */\n" );
			file_put_contents( $dir . '/index.php', "<?php\n" );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Delete a fixture directory.
	 *
	 * @param string $dir Directory to delete.
	 */
	private function delete_dir( string $dir ): void {
		global $wp_filesystem;

		if ( ! is_dir( $dir ) ) {
			return;
		}

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$wp_filesystem->delete( $dir, true );
	}
}
