<?php

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
		delete_option( 'woocommerce_verified_installation_version' );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->recursive_rmdir( $this->temp_dir );
		delete_option( 'woocommerce_verified_installation_version' );
		parent::tearDown();
	}

	/**
	 * @testdox Verification passes and caches the version when all manifest files exist and the version matches.
	 */
	public function test_verify_installation_passes_when_all_files_exist(): void {
		$this->create_plugin_file( '10.5.0' );
		mkdir( $this->temp_dir . '/src', 0755, true );
		file_put_contents( $this->temp_dir . '/src/Autoloader.php', '<?php // stub' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/Autoloader.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
		$this->assertSame( '10.5.0', get_option( 'woocommerce_verified_installation_version' ) );
	}

	/**
	 * @testdox Verification fails when a file listed in the manifest is missing from disk.
	 */
	public function test_verify_installation_fails_when_file_missing(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/MissingFile.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
		$this->assertFalse( get_option( 'woocommerce_verified_installation_version' ) );
	}

	/**
	 * @testdox Verification fails when the manifest version does not match the plugin header version.
	 */
	public function test_verify_installation_fails_on_version_mismatch(): void {
		$this->create_plugin_file( '10.5.0' );
		$this->create_manifest( '10.4.0', array( 'woocommerce.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox Verification passes when no manifest file exists (development environment).
	 */
	public function test_verify_installation_passes_when_no_manifest(): void {
		$this->create_plugin_file( '10.5.0' );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Verification skips the file check when the current version is already cached as verified.
	 */
	public function test_verify_installation_skips_check_when_already_verified(): void {
		$this->create_plugin_file( '10.5.0' );
		update_option( 'woocommerce_verified_installation_version', '10.5.0' );

		// Even with a manifest that lists missing files, the check should pass
		// because the version is already verified.
		$this->create_manifest( '10.5.0', array( 'woocommerce.php', 'src/DoesNotExist.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox A version change triggers a fresh verification even if the previous version was cached as verified.
	 */
	public function test_verify_installation_rechecks_on_version_change(): void {
		$this->create_plugin_file( '10.6.0' );
		update_option( 'woocommerce_verified_installation_version', '10.5.0' );
		$this->create_manifest( '10.6.0', array( 'woocommerce.php', 'src/NewFile.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox Pre-release suffixes are stripped before comparing the manifest and plugin versions.
	 */
	public function test_verify_installation_handles_prerelease_versions(): void {
		$this->create_plugin_file( '10.5.0-beta1' );
		$this->create_manifest( '10.5.0-beta1', array( 'woocommerce.php' ) );

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Verification passes when the manifest file has an invalid structure.
	 */
	public function test_verify_installation_passes_with_invalid_manifest(): void {
		$this->create_plugin_file( '10.5.0' );
		file_put_contents(
			$this->temp_dir . '/file-manifest.php',
			'<?php return "not an array";'
		);

		$result = FileManifest::verify_installation( $this->temp_dir . '/woocommerce.php' );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox enumerate_php_files excludes every directory listed in .distignore.
	 */
	public function test_enumerate_excludes_all_distignore_directories(): void {
		$distignore_path = dirname( WC_PLUGIN_FILE ) . '/.distignore';
		$this->assertFileExists( $distignore_path, '.distignore must exist for this test.' );

		// Parse .distignore for directory exclusion patterns.
		$lines         = file( $distignore_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$excluded_dirs = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}

			// Skip generic dotfile/dotdir globs and file-level patterns (no path separator).
			if ( '.*' === $line || '.*/' === $line || false === strpos( $line, '/' ) ) {
				continue;
			}

			$dir = trim( $line, '/' );

			// Replace wildcards with concrete names so we can create real directories.
			$dir             = str_replace( '*', 'test-pkg', $dir );
			$excluded_dirs[] = $dir;
		}

		$this->assertNotEmpty( $excluded_dirs, '.distignore should contain directory patterns.' );

		// Create a production file that must be included.
		mkdir( $this->temp_dir . '/src', 0755, true );
		file_put_contents( $this->temp_dir . '/src/Example.php', '<?php // production' );

		// Create a PHP file inside each excluded directory.
		$excluded_files = array();
		foreach ( $excluded_dirs as $dir ) {
			$full_dir = $this->temp_dir . '/' . $dir;
			if ( ! is_dir( $full_dir ) ) {
				mkdir( $full_dir, 0755, true );
			}
			file_put_contents( $full_dir . '/stub.php', '<?php // dev' );
			$excluded_files[] = $dir . '/stub.php';
		}

		$result = FileManifest::enumerate_php_files( $this->temp_dir );

		$this->assertContains( 'src/Example.php', $result, 'Production file should be included.' );

		foreach ( $excluded_files as $excluded_file ) {
			$this->assertNotContains(
				$excluded_file,
				$result,
				sprintf( 'File "%s" should be excluded per .distignore but was found in the manifest.', $excluded_file )
			);
		}
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
		$files_export = var_export( $files, true );
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
