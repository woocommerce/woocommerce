<?php
// phpcs:disable WordPress.WP.AlternativeFunctions

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\FileManifest;
use WC_Unit_Test_Case;

/**
 * Unit tests for the FileManifest::verify_installation method.
 *
 * @covers \Automattic\WooCommerce\Internal\FileManifest
 */
class FileManifestTest extends WC_Unit_Test_Case {

	/**
	 * Temporary plugin directory used by each test.
	 *
	 * @var string
	 */
	private $temp_dir;

	/**
	 * Set up a temporary plugin directory structure for each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->temp_dir = sys_get_temp_dir() . '/wc-filemanifest-test-' . uniqid();
		mkdir( $this->temp_dir, 0755, true );
		delete_option( 'woocommerce_file_manifest_check_result' );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->recursive_rmdir( $this->temp_dir );
		delete_option( 'woocommerce_file_manifest_check_result' );
		$this->set_disabling_mode( null );
		parent::tearDown();
	}

	/**
	 * Set the disabling mode override via reflection.
	 *
	 * @param bool|null $value True to force disabling mode, false to force non-disabling, null to reset.
	 */
	private function set_disabling_mode( ?bool $value ): void {
		$property = new \ReflectionProperty( FileManifest::class, 'disabling_mode_override' );
		$property->setAccessible( true );
		$property->setValue( null, $value );
	}

	// ---- Disabling mode tests (filter=true) ----

	/**
	 * @testdox [Disabling mode] Verification passes and caches the result when all manifest files exist and the version matches.
	 */
	public function test_disabling_mode_passes_when_all_files_exist(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		mkdir( $this->temp_dir . '/src', 0755, true );
		file_put_contents( $this->temp_dir . '/src/Autoloader.php', '<?php // stub' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/Autoloader.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertIsArray( $stored );
		$this->assertSame( 'pass', $stored['status'] );
		$this->assertSame( '10.5.0', $stored['version'] );
	}

	/**
	 * @testdox [Disabling mode] Verification fails when a file listed in the manifest is missing from disk.
	 */
	public function test_disabling_mode_fails_when_file_missing(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/MissingFile.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox [Disabling mode] Verification fails when the manifest version does not match the plugin header version.
	 */
	public function test_disabling_mode_fails_on_version_mismatch(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.4.0', array( 'woocommerce.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox [Disabling mode] Verification passes when no manifest file exists (development environment).
	 */
	public function test_disabling_mode_passes_when_no_manifest(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Disabling mode] Verification skips the file check when the stored result shows a pass for the current version.
	 */
	public function test_disabling_mode_skips_check_when_already_verified(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'pass' );

		// Even with a manifest that lists missing files, the check should pass
		// because the stored result already shows a pass for this version.
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/DoesNotExist.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Disabling mode] A version change triggers a fresh verification even if the previous version was cached as passed.
	 */
	public function test_disabling_mode_rechecks_on_version_change(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.6.0' );
		$this->store_check_result( '10.5.0', 'pass' );
		$this->create_manifest( '10.6.0', array( 'woocommerce.php', 'src/NewFile.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox [Disabling mode] Pre-release suffixes are stripped before comparing the manifest and plugin versions.
	 */
	public function test_disabling_mode_handles_prerelease_versions(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0-beta1' );
		$this->create_manifest( '10.5.0-beta1', array( 'woocommerce.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Disabling mode] Verification passes when the manifest file has an invalid structure.
	 */
	public function test_disabling_mode_passes_with_invalid_manifest(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		file_put_contents(
			$this->temp_dir . '/file-manifest.php',
			'<?php return "not an array";'
		);

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Disabling mode] A stored pass is still trusted when the manifest file has been deleted (requires manual recheck).
	 */
	public function test_disabling_mode_skips_check_when_manifest_deleted_after_pass(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'pass' );
		// No manifest file on disk — it was deleted after the previous pass.

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		// The stored pass is still trusted; manifest changes require manual recheck.
		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Disabling mode] A stored failure result is trusted (no re-check until option is deleted).
	 */
	public function test_disabling_mode_trusts_stored_failure(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'missing_files' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/StillMissing.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Disabling mode] When the stored result is deleted, a fresh check runs and fails on missing files.
	 */
	public function test_disabling_mode_fails_after_recheck_with_missing_files(): void {
		$this->set_disabling_mode( true );

		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/StillMissing.php' ) );
		// No stored result — simulates option deleted by recheck tool.

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
	}

	// ---- Non-disabling mode tests (filter=false, default) ----

	/**
	 * @testdox [Non-disabling mode] Verification always returns true even when files are missing.
	 */
	public function test_non_disabling_mode_always_returns_true_on_missing_files(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/MissingFile.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Non-disabling mode] Verification always returns true even on version mismatch.
	 */
	public function test_non_disabling_mode_always_returns_true_on_version_mismatch(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.4.0', array( 'woocommerce.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox [Non-disabling mode] A passing check stores status 'pass' in the check result option.
	 */
	public function test_non_disabling_mode_stores_pass_result(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php' ) );

		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertIsArray( $stored );
		$this->assertSame( 'pass', $stored['status'] );
		$this->assertSame( '10.5.0', $stored['version'] );
		$this->assertArrayHasKey( 'date', $stored );
		$this->assertEmpty( $stored['details'] );
	}

	/**
	 * @testdox [Non-disabling mode] A version mismatch stores status 'version_mismatch' in the check result option.
	 */
	public function test_non_disabling_mode_stores_version_mismatch_result(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.4.0', array( 'woocommerce.php' ) );

		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertIsArray( $stored );
		$this->assertSame( 'version_mismatch', $stored['status'] );
		$this->assertSame( '10.5.0', $stored['version'] );
		$this->assertSame( '10.4.0', $stored['manifest_version'] );
		$this->assertNotEmpty( $stored['details'] );
	}

	/**
	 * @testdox [Non-disabling mode] Missing files stores status 'missing_files' in the check result option.
	 */
	public function test_non_disabling_mode_stores_missing_files_result(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/MissingFile.php' ) );

		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertIsArray( $stored );
		$this->assertSame( 'missing_files', $stored['status'] );
		$this->assertContains( 'src/MissingFile.php', $stored['details'] );
	}

	/**
	 * @testdox [Non-disabling mode] No manifest stores status 'no_manifest' in the check result option.
	 */
	public function test_non_disabling_mode_stores_no_manifest_result(): void {
		$this->create_plugin_file( '10.5.0' );

		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertIsArray( $stored );
		$this->assertSame( 'no_manifest', $stored['status'] );
	}

	/**
	 * @testdox [Non-disabling mode] The check is skipped when the stored result version matches the current version.
	 */
	public function test_non_disabling_mode_skips_check_when_already_verified(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'pass', '2026-01-01 00:00:00' );

		// Even with a manifest that lists missing files, the check should be skipped
		// because the stored result already covers this version.
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/DoesNotExist.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
		// The stored check result should remain unchanged (not overwritten).
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertSame( '2026-01-01 00:00:00', $stored['date'] );
	}

	/**
	 * @testdox [Non-disabling mode] A stored failure result also skips re-check for the same version.
	 */
	public function test_non_disabling_mode_skips_check_on_stored_failure(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'missing_files', '2026-01-01 00:00:00' );

		// Fix the issue (all files present now), but check should still be skipped.
		$this->create_manifest( '10.5.0', array( 'woocommerce.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertSame( 'missing_files', $stored['status'] );
		$this->assertSame( '2026-01-01 00:00:00', $stored['date'] );
	}

	/**
	 * @testdox [Non-disabling mode] A stored pass is kept when the manifest file has been deleted (requires manual recheck).
	 */
	public function test_non_disabling_mode_skips_check_when_manifest_deleted_after_pass(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'pass', '2026-01-01 00:00:00' );
		// No manifest file on disk — it was deleted after the previous pass.

		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		// The stored pass is still trusted; manifest changes require manual recheck.
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertSame( 'pass', $stored['status'] );
		$this->assertSame( '2026-01-01 00:00:00', $stored['date'] );
	}

	/**
	 * @testdox [Non-disabling mode] A stored no_manifest is kept even when a manifest file appears (requires manual recheck).
	 */
	public function test_non_disabling_mode_skips_check_when_manifest_appears_after_no_manifest(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->store_check_result( '10.5.0', 'no_manifest', '2026-01-01 00:00:00' );
		// Now a manifest file exists (e.g. after regeneration).
		$this->create_manifest( '10.5.0', array( 'woocommerce.php' ) );

		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		// The stored no_manifest is still trusted; manifest changes require manual recheck.
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertSame( 'no_manifest', $stored['status'] );
		$this->assertSame( '2026-01-01 00:00:00', $stored['date'] );
	}

	/**
	 * @testdox [Non-disabling mode] A version change triggers a fresh check and updates the result option.
	 */
	public function test_non_disabling_mode_rechecks_on_version_change(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php' ) );

		// First call: stores pass for 10.5.0.
		FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertSame( 'pass', $stored['status'] );
		$this->assertSame( '10.5.0', $stored['version'] );

		// Simulate version upgrade with missing file.
		$this->create_plugin_file( '10.6.0' );
		$this->create_manifest( '10.6.0', array( 'woocommerce.php', 'src/NewFile.php' ) );

		// Second call: should re-check and store missing_files for 10.6.0.
		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertSame( 'missing_files', $stored['status'] );
		$this->assertSame( '10.6.0', $stored['version'] );
	}

	// ---- Recheck tool tests ----

	/**
	 * @testdox The recheck tool is added to the debug tools list when the stored result is a failure.
	 */
	public function test_recheck_tool_added_on_failure(): void {
		$this->store_check_result( '10.5.0', 'missing_files' );

		$tools = FileManifest::handle_woocommerce_debug_tools( array() );

		$this->assertArrayHasKey( 'recheck_file_manifest', $tools );
		$this->assertSame( 'Installation integrity check', $tools['recheck_file_manifest']['name'] );
		$this->assertSame( 'Recheck', $tools['recheck_file_manifest']['button'] );
		$this->assertArrayHasKey( 'desc', $tools['recheck_file_manifest'] );
		$this->assertArrayHasKey( 'callback', $tools['recheck_file_manifest'] );
	}

	/**
	 * @testdox The recheck tool is added to the debug tools list even when the stored result is a pass.
	 */
	public function test_recheck_tool_added_on_pass(): void {
		$this->store_check_result( '10.5.0', 'pass' );

		$tools = FileManifest::handle_woocommerce_debug_tools( array() );

		$this->assertArrayHasKey( 'recheck_file_manifest', $tools );
	}

	/**
	 * @testdox The recheck tool callback deletes the stored check result option.
	 */
	public function test_recheck_tool_callback_clears_option(): void {
		$this->store_check_result( '10.5.0', 'missing_files' );
		$this->assertIsArray( get_option( 'woocommerce_file_manifest_check_result' ) );

		$message = FileManifest::recheck_tool_callback();

		$this->assertFalse( get_option( 'woocommerce_file_manifest_check_result' ) );
		$this->assertSame( 'Integrity check will run on the next page load.', $message );
	}

	// ---- get_check_result tests ----

	/**
	 * @testdox get_check_result returns the stored result when the option exists.
	 */
	public function test_get_check_result_returns_stored_result(): void {
		$this->store_check_result( '10.5.0', 'pass' );

		$result = FileManifest::get_check_result();

		$this->assertIsArray( $result );
		$this->assertSame( 'pass', $result['status'] );
		$this->assertSame( '10.5.0', $result['version'] );
		$this->assertArrayHasKey( 'date', $result );
		$this->assertArrayHasKey( 'details', $result );
	}

	/**
	 * @testdox get_check_result returns null when no option has been stored.
	 */
	public function test_get_check_result_returns_null_when_no_option(): void {
		$result = FileManifest::get_check_result();

		$this->assertNull( $result );
	}

	// ---- run_fresh_verification tests ----

	/**
	 * @testdox run_fresh_verification returns a passing result without storing it in the database.
	 */
	public function test_run_fresh_verification_returns_result_without_storing(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php' ) );

		$result = FileManifest::run_fresh_verification( $this->temp_dir . '/woocommerce.php' );

		$this->assertSame( 'pass', $result['status'] );
		$this->assertSame( '10.5.0', $result['version'] );
		$this->assertEmpty( $result['details'] );
		$this->assertFalse( get_option( 'woocommerce_file_manifest_check_result' ), 'The result should not be stored in the database.' );
	}

	/**
	 * @testdox run_fresh_verification detects missing files without storing the result in the database.
	 */
	public function test_run_fresh_verification_detects_missing_files(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/MissingFile.php' ) );

		$result = FileManifest::run_fresh_verification( $this->temp_dir . '/woocommerce.php' );

		$this->assertSame( 'missing_files', $result['status'] );
		$this->assertContains( 'src/MissingFile.php', $result['details'] );
		$this->assertFalse( get_option( 'woocommerce_file_manifest_check_result' ), 'The result should not be stored in the database.' );
	}

	// ---- store_fresh_result tests ----

	/**
	 * @testdox store_fresh_result stores the verification result and replaces any previously cached result.
	 */
	public function test_store_fresh_result_stores_and_replaces(): void {
		$this->store_check_result( '10.4.0', 'pass' );

		$result = array(
			'status'  => 'missing_files',
			'details' => array( 'src/MissingFile.php' ),
			'version' => '10.5.0',
		);

		FileManifest::store_fresh_result( $result );

		$stored = get_option( 'woocommerce_file_manifest_check_result' );
		$this->assertIsArray( $stored );
		$this->assertSame( 'missing_files', $stored['status'] );
		$this->assertSame( '10.5.0', $stored['version'] );
		$this->assertArrayHasKey( 'date', $stored );
		$this->assertContains( 'src/MissingFile.php', $stored['details'] );
	}

	// ---- Shared utility tests ----

	/**
	 * @testdox enumerate_php_files includes all PHP files in the directory except the manifest itself.
	 */
	public function test_enumerate_includes_all_php_files(): void {
		mkdir( $this->temp_dir . '/src', 0755, true );
		mkdir( $this->temp_dir . '/vendor/acme/lib', 0755, true );
		file_put_contents( $this->temp_dir . '/src/Example.php', '<?php // src' );
		file_put_contents( $this->temp_dir . '/vendor/acme/lib/Helper.php', '<?php // vendor' );
		file_put_contents( $this->temp_dir . '/file-manifest.php', '<?php // manifest' );
		file_put_contents( $this->temp_dir . '/readme.txt', 'not php' );

		$result = FileManifest::enumerate_php_files( $this->temp_dir );

		$this->assertContains( 'src/Example.php', $result );
		$this->assertContains( 'vendor/acme/lib/Helper.php', $result );
		$this->assertNotContains( 'file-manifest.php', $result, 'The manifest file itself should be excluded.' );
		$this->assertNotContains( 'readme.txt', $result, 'Non-PHP files should be excluded.' );
	}

	/**
	 * Create a minimal woocommerce.php file with the given version.
	 *
	 * @param string $version The plugin version.
	 */
	private function create_plugin_file( string $version ): void {
		$content = <<<PHP
<?php
/**
 * Plugin Name: WooCommerce
 * Version: {$version}
 */
PHP;
		file_put_contents( $this->temp_dir . '/woocommerce.php', $content );
	}

	/**
	 * Create a file-manifest.php with the given version and file list.
	 *
	 * @param string   $version The manifest version.
	 * @param string[] $files   The list of relative file paths.
	 */
	private function create_manifest( string $version, array $files ): void {
		$files_export = var_export( $files, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$content      = <<<PHP
<?php
return array(
	'version' => '{$version}',
	'files'   => {$files_export},
);
PHP;
		file_put_contents( $this->temp_dir . '/file-manifest.php', $content );
	}

	/**
	 * Store a check result option directly for test setup.
	 *
	 * @param string $version The version string.
	 * @param string $status  The check status.
	 * @param string $date    Optional date string.
	 */
	private function store_check_result( string $version, string $status, string $date = '2026-01-15 12:00:00' ): void {
		update_option(
			'woocommerce_file_manifest_check_result',
			array(
				'version' => $version,
				'date'    => $date,
				'status'  => $status,
				'details' => array(),
			)
		);
	}

	/**
	 * Recursively remove a directory and its contents.
	 *
	 * @param string $dir The directory path.
	 */
	private function recursive_rmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			is_dir( $path ) ? $this->recursive_rmdir( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}
}
