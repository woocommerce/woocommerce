<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Translations;
use WC_Unit_Test_Case;

/**
 * Tests for the Translations class.
 */
class TranslationsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Translations
	 */
	private $sut;

	/**
	 * Path to the language files directory.
	 *
	 * @var string
	 */
	private $lang_dir;

	/**
	 * Files created during the test, removed on teardown.
	 *
	 * @var string[]
	 */
	private $created_files = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut      = Translations::get_instance();
		$this->lang_dir = WP_LANG_DIR . '/plugins/';

		// The singleton keeps its once-per-request regeneration guard across tests.
		$guard = new \ReflectionProperty( Translations::class, 'regeneration_attempted' );
		$guard->setAccessible( true );
		$guard->setValue( $this->sut, false );

		wp_mkdir_p( $this->lang_dir );

		add_filter( 'pre_determine_locale', array( $this, 'force_de_locale' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_determine_locale', array( $this, 'force_de_locale' ) );

		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		if ( file_exists( $combined ) ) {
			wp_delete_file( $combined );
		}
		foreach ( $this->created_files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}
		$this->created_files = array();

		parent::tearDown();
	}

	/**
	 * Forces determine_locale() to return de_DE.
	 *
	 * @return string Locale.
	 */
	public function force_de_locale() {
		return 'de_DE';
	}

	/**
	 * Forces get_filesystem_method() to report a remote filesystem.
	 *
	 * @return string Filesystem method.
	 */
	public function force_ftpext_filesystem() {
		return 'ftpext';
	}

	/**
	 * Forces get_filesystem_method() to report an SSH2 filesystem.
	 *
	 * @return string Filesystem method.
	 */
	public function force_ssh2_filesystem() {
		return 'ssh2';
	}

	/**
	 * Writes a chunk translation JSON file in the official language pack format.
	 *
	 * @param string $md5_name  Fake md5 part of the filename.
	 * @param string $reference Reference file path stored in the JSON.
	 * @param array  $messages  Translation messages.
	 */
	private function create_chunk_json( $md5_name, $reference, $messages ) {
		$data = array(
			'translation-revision-date' => '2026-01-01 00:00:00+0000',
			'generator'                 => 'GlotPress',
			'domain'                    => 'messages',
			'locale_data'               => array(
				'messages' => array_merge(
					array(
						'' => array(
							'domain'       => 'messages',
							'plural-forms' => 'nplurals=2; plural=n != 1;',
							'lang'         => 'de',
						),
					),
					$messages
				),
			),
			'comment'                   => array( 'reference' => $reference ),
		);

		$path = $this->lang_dir . 'woocommerce-de_DE-' . $md5_name . '.json';
		file_put_contents( $path, wp_json_encode( $data ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->created_files[] = $path;
	}

	/**
	 * @testdox Should leave the translation file untouched for other script handles.
	 */
	public function test_ignores_other_script_handles(): void {
		$result = $this->sut->load_script_translation_file( '/some/file.json', 'some-other-script', 'woocommerce' );

		$this->assertSame( '/some/file.json', $result, 'Files for other handles should not be redirected' );
	}

	/**
	 * @testdox Should leave the translation file untouched for other text domains.
	 */
	public function test_ignores_other_text_domains(): void {
		$result = $this->sut->load_script_translation_file( '/some/file.json', WC_ADMIN_APP, 'some-other-domain' );

		$this->assertSame( '/some/file.json', $result, 'Files for other domains should not be redirected' );
	}

	/**
	 * @testdox Should return the combined translation file when it exists.
	 */
	public function test_returns_combined_file_when_present(): void {
		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		file_put_contents( $combined, '{}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$result = $this->sut->load_script_translation_file( '/some/file.json', WC_ADMIN_APP, 'woocommerce' );

		$this->assertSame( $combined, $result, 'The combined translation file should be served when present' );
	}

	/**
	 * @testdox Should rebuild the combined translation file on demand from chunk files.
	 */
	public function test_rebuilds_missing_combined_file_from_chunks(): void {
		$this->create_chunk_json(
			'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
			WC_ADMIN_DIST_JS_FOLDER . 'chunks/analytics-report-products.js',
			array( 'Compare Products' => array( 'Produkte vergleichen' ) )
		);
		$this->create_chunk_json(
			'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
			WC_ADMIN_DIST_JS_FOLDER . 'app/index.js',
			array( 'Show' => array( 'Anzeigen' ) )
		);

		$result = $this->sut->load_script_translation_file( '/some/file.json', WC_ADMIN_APP, 'woocommerce' );

		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		$this->assertSame( $combined, $result, 'The combined file should be rebuilt and served' );
		$this->assertFileExists( $combined, 'The combined file should be written to disk' );

		$messages = json_decode( file_get_contents( $combined ), true )['locale_data']['messages']; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertSame( array( 'Produkte vergleichen' ), $messages['Compare Products'], 'Chunk translations should be merged into the combined file' );
		$this->assertSame( array( 'Anzeigen' ), $messages['Show'], 'App translations should be merged into the combined file' );
		$this->assertSame( array(), glob( $this->lang_dir . '*.tmp' ), 'No temporary files should be left behind after the combined file is published' );
	}

	/**
	 * @testdox Should replace an existing combined file when rebuilding.
	 */
	public function test_replaces_existing_combined_file_on_rebuild(): void {
		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		file_put_contents( $combined, '{"stale":true}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->create_chunk_json(
			'cccccccccccccccccccccccccccccccc',
			WC_ADMIN_DIST_JS_FOLDER . 'chunks/analytics-report-products.js',
			array( 'Compare Products' => array( 'Produkte vergleichen' ) )
		);

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		\WP_Filesystem();
		$build = new \ReflectionMethod( Translations::class, 'build_and_save_translations' );
		$build->setAccessible( true );
		$build->invoke( $this->sut, $this->lang_dir, 'woocommerce', 'de_DE' );

		$data = json_decode( file_get_contents( $combined ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertArrayNotHasKey( 'stale', $data, 'The stale combined file should be replaced' );
		$this->assertSame( array( 'Produkte vergleichen' ), $data['locale_data']['messages']['Compare Products'], 'The replacement should contain the rebuilt translations' );
		$this->assertSame( array(), glob( $this->lang_dir . '*.tmp' ), 'No temporary files should be left behind after replacing the combined file' );
	}

	/**
	 * @testdox Should overwrite the combined file in place when a remote filesystem refuses to move onto it.
	 */
	public function test_replaces_existing_combined_file_when_remote_move_fails(): void {
		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		file_put_contents( $combined, '{"stale":true}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->create_chunk_json(
			'dddddddddddddddddddddddddddddddd',
			WC_ADMIN_DIST_JS_FOLDER . 'app/index.js',
			array( 'Show' => array( 'Anzeigen' ) )
		);

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		\WP_Filesystem();

		$previous_filesystem = $GLOBALS['wp_filesystem'];

		// Mirrors WP_Filesystem_FTPext against a server that refuses to rename onto an existing path.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Stub filesystem, restored below.
		$GLOBALS['wp_filesystem'] = new class( null ) extends \WP_Filesystem_Direct {
			/**
			 * Always reports the move as failed.
			 *
			 * @param string $source      Path to the source file.
			 * @param string $destination Path to the destination file.
			 * @param bool   $overwrite   Whether to overwrite the destination.
			 * @return bool Always false.
			 */
			public function move( $source, $destination, $overwrite = false ) {
				return false;
			}
		};
		add_filter( 'filesystem_method', array( $this, 'force_ftpext_filesystem' ), PHP_INT_MAX );

		try {
			$build = new \ReflectionMethod( Translations::class, 'build_and_save_translations' );
			$build->setAccessible( true );
			$build->invoke( $this->sut, $this->lang_dir, 'woocommerce', 'de_DE' );
		} finally {
			remove_filter( 'filesystem_method', array( $this, 'force_ftpext_filesystem' ), PHP_INT_MAX );
			$GLOBALS['wp_filesystem'] = $previous_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the real filesystem.
		}

		$data = json_decode( file_get_contents( $combined ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertArrayNotHasKey( 'stale', $data, 'The previous language pack file should not survive a failed move' );
		$this->assertSame( array( 'Anzeigen' ), $data['locale_data']['messages']['Show'], 'The refreshed translations should be written in place instead' );
		$this->assertSame( array(), glob( $this->lang_dir . '*.tmp' ), 'No temporary files should be left behind after the fallback write' );
	}

	/**
	 * @testdox Should overwrite the combined file in place when the temp file cannot be written.
	 */
	public function test_replaces_existing_combined_file_when_temp_write_fails(): void {
		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		file_put_contents( $combined, '{"stale":true}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->create_chunk_json(
			'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
			WC_ADMIN_DIST_JS_FOLDER . 'app/index.js',
			array( 'Show' => array( 'Anzeigen' ) )
		);

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		\WP_Filesystem();

		$previous_filesystem = $GLOBALS['wp_filesystem'];

		// Mirrors a language directory that allows writing its existing files but not creating new ones.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Stub filesystem, restored below.
		$GLOBALS['wp_filesystem'] = new class( null ) extends \WP_Filesystem_Direct {
			/**
			 * Rejects writes to the temp file, allowing every other write through.
			 *
			 * @param string    $file     Path to the file.
			 * @param string    $contents Contents to write.
			 * @param int|false $mode     Optional file permissions.
			 * @return bool Whether the contents were written.
			 */
			public function put_contents( $file, $contents, $mode = false ) {
				if ( str_ends_with( $file, '.tmp' ) ) {
					return false;
				}
				return parent::put_contents( $file, $contents, $mode );
			}
		};

		try {
			$build = new \ReflectionMethod( Translations::class, 'build_and_save_translations' );
			$build->setAccessible( true );
			$build->invoke( $this->sut, $this->lang_dir, 'woocommerce', 'de_DE' );
		} finally {
			$GLOBALS['wp_filesystem'] = $previous_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the real filesystem.
		}

		$data = json_decode( file_get_contents( $combined ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertArrayNotHasKey( 'stale', $data, 'The previous language pack file should not survive a failed temp write' );
		$this->assertSame( array( 'Anzeigen' ), $data['locale_data']['messages']['Show'], 'The refreshed translations should be written in place instead' );
		$this->assertSame( array(), glob( $this->lang_dir . '*.tmp' ), 'No temporary files should be left behind after the fallback write' );
	}

	/**
	 * @testdox Should recreate the combined file when a remote move removes it before failing.
	 */
	public function test_recreates_combined_file_when_remote_move_destroys_it(): void {
		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		file_put_contents( $combined, '{"stale":true}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->create_chunk_json(
			'ffffffffffffffffffffffffffffffff',
			WC_ADMIN_DIST_JS_FOLDER . 'app/index.js',
			array( 'Show' => array( 'Anzeigen' ) )
		);

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		\WP_Filesystem();

		$previous_filesystem = $GLOBALS['wp_filesystem'];

		// Mirrors WP_Filesystem_SSH2::move(), which removes the destination before renaming onto it.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Stub filesystem, restored below.
		$GLOBALS['wp_filesystem'] = new class( null ) extends \WP_Filesystem_Direct {
			/**
			 * Removes the destination, then reports the move as failed.
			 *
			 * @param string $source      Path to the source file.
			 * @param string $destination Path to the destination file.
			 * @param bool   $overwrite   Whether to overwrite the destination.
			 * @return bool Always false.
			 */
			public function move( $source, $destination, $overwrite = false ) {
				$this->delete( $destination, false, 'f' );
				return false;
			}
		};
		add_filter( 'filesystem_method', array( $this, 'force_ssh2_filesystem' ), PHP_INT_MAX );

		try {
			$build = new \ReflectionMethod( Translations::class, 'build_and_save_translations' );
			$build->setAccessible( true );
			$build->invoke( $this->sut, $this->lang_dir, 'woocommerce', 'de_DE' );
		} finally {
			remove_filter( 'filesystem_method', array( $this, 'force_ssh2_filesystem' ), PHP_INT_MAX );
			$GLOBALS['wp_filesystem'] = $previous_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the real filesystem.
		}

		$this->assertFileExists( $combined, 'A move that removes the destination should not leave the site without a combined file' );
		$data = json_decode( file_get_contents( $combined ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertSame( array( 'Anzeigen' ), $data['locale_data']['messages']['Show'], 'The recreated file should contain the rebuilt translations' );
	}

	/**
	 * @testdox Should remove the combined file when the in-place write leaves it truncated.
	 */
	public function test_removes_combined_file_when_fallback_write_is_truncated(): void {
		$combined = $this->lang_dir . 'woocommerce-de_DE-' . WC_ADMIN_APP . '.json';
		file_put_contents( $combined, '{"stale":true}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->create_chunk_json(
			'gggggggggggggggggggggggggggggggg',
			WC_ADMIN_DIST_JS_FOLDER . 'app/index.js',
			array( 'Show' => array( 'Anzeigen' ) )
		);

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		\WP_Filesystem();

		$previous_filesystem = $GLOBALS['wp_filesystem'];

		// Mirrors WP_Filesystem_Direct::put_contents() truncating the file before a short write fails.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Stub filesystem, restored below.
		$stub                = new class( null ) extends \WP_Filesystem_Direct {
			/**
			 * Path the truncated write is simulated for.
			 *
			 * @var string
			 */
			public $truncate_path = '';

			/**
			 * Always reports the move as failed.
			 *
			 * @param string $source      Path to the source file.
			 * @param string $destination Path to the destination file.
			 * @param bool   $overwrite   Whether to overwrite the destination.
			 * @return bool Always false.
			 */
			public function move( $source, $destination, $overwrite = false ) {
				return false;
			}

			/**
			 * Writes a partial payload and reports failure for the tracked path.
			 *
			 * @param string    $file     Path to the file.
			 * @param string    $contents Contents to write.
			 * @param int|false $mode     Optional file permissions.
			 * @return bool Whether the contents were written.
			 */
			public function put_contents( $file, $contents, $mode = false ) {
				if ( $file === $this->truncate_path ) {
					parent::put_contents( $file, '{"locale_data', $mode );
					return false;
				}
				return parent::put_contents( $file, $contents, $mode );
			}
		};
		$stub->truncate_path = $combined;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Stub filesystem, restored below.
		$GLOBALS['wp_filesystem'] = $stub;
		// The direct branch renames outside the filesystem object, so route publishing through move().
		add_filter( 'filesystem_method', array( $this, 'force_ftpext_filesystem' ), PHP_INT_MAX );

		try {
			$build = new \ReflectionMethod( Translations::class, 'build_and_save_translations' );
			$build->setAccessible( true );
			$build->invoke( $this->sut, $this->lang_dir, 'woocommerce', 'de_DE' );
		} finally {
			remove_filter( 'filesystem_method', array( $this, 'force_ftpext_filesystem' ), PHP_INT_MAX );
			$GLOBALS['wp_filesystem'] = $previous_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the real filesystem.
		}

		$this->assertFileDoesNotExist( $combined, 'A truncated write should not be left behind for readers to parse' );
		$this->assertSame( array(), glob( $this->lang_dir . '*.tmp' ), 'No temporary files should be left behind after a truncated write' );
	}

	/**
	 * @testdox Should fall back to the original file when nothing can be rebuilt.
	 */
	public function test_falls_back_to_original_file_when_rebuild_not_possible(): void {
		$result = $this->sut->load_script_translation_file( '/some/file.json', WC_ADMIN_APP, 'woocommerce' );

		$this->assertSame( '/some/file.json', $result, 'The original file should be used when no combined file can be generated' );
	}
}
