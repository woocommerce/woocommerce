<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;

/**
 * Class ZipExportedSchema
 *
 * Handles the creation of a ZIP archive from a schema.
 */
class ZipExportedSchema {
	use UsePluginHelpers;
	use UseWPFunctions;

	/**
	 * Exported schema from ExportSchema class.
	 *
	 * @var array
	 */
	protected array $schema;

	/**
	 * Full path for the ZIP file.
	 *
	 * @var string
	 */
	protected string $destination;

	/**
	 * Base path for working directory.
	 *
	 * @var string
	 */
	protected string $dir;

	/**
	 * Unique directory name for a single session.
	 *
	 * @var string
	 */
	protected string $working_dir;

	/**
	 * Array of files to be included in the ZIP archive.
	 *
	 * @var array
	 */
	protected array $files;

	/**
	 * Constructor.
	 *
	 * @param array       $schema Exported schema array.
	 * @param string|null $destination Optional. Path to the destination ZIP file.
	 */
	public function __construct( $schema, $destination = null ) {
		$this->schema = $schema;

		$this->dir         = $this->get_default_destination_dir();
		$this->destination = null === $destination ? $this->dir . '/woo-blueprint.zip' : Util::ensure_wp_content_path( $destination );
		$this->working_dir = $this->dir . '/' . gmdate( 'Ymd' ) . '_' . time();

		if ( ! class_exists( 'PclZip' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		}
	}

	/**
	 * Returns the full path for a file in the working directory.
	 *
	 * @param string $filename The name of the file.
	 * @return string Full path to the file.
	 */
	protected function get_working_dir_path( $filename ) {
		return $this->working_dir . '/' . $filename;
	}

	/**
	 * Creates a directory if it does not exist.
	 *
	 * @param string $dir Directory path.
	 */
	protected function maybe_create_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			// phpcs:ignore
			mkdir( $dir, 0777, true );
		}
	}

	/**
	 * Creates a ZIP archive of the schema and resources.
	 *
	 * @return string Path to the created ZIP file.
	 * @throws \Exception If there is an error creating the ZIP archive.
	 */
	public function zip() {
		$this->maybe_create_dir( $this->working_dir );

		// Create .json file.
		$this->files[] = $this->create_json_schema_file();
		$this->files   = array_merge( $this->files, $this->add_resource( InstallPlugin::get_step_name(), 'plugins' ) );
		$this->files   = array_merge( $this->files, $this->add_resource( InstallTheme::get_step_name(), 'themes' ) );

		$archive = new \PclZip( $this->destination );
		if ( $archive->create( $this->files, PCLZIP_OPT_REMOVE_PATH, $this->working_dir ) === 0 ) {
			throw new \Exception( 'Error : ' . $archive->errorInfo( true ) );
		}

		$this->clean();

		return $this->destination;
	}

	/**
	 * Returns the default destination directory for the ZIP file.
	 *
	 * @return string Default destination directory path.
	 */
	protected function get_default_destination_dir() {
		return WP_CONTENT_DIR . '/uploads/blueprint';
	}

	/**
	 * Finds steps in the schema matching the given step name.
	 *
	 * @param string $step_name Name of the step to find.
	 * @return array|null Array of matching steps, or null if none found.
	 */
	protected function find_steps( $step_name ) {
		$steps = array_filter(
			$this->schema['steps'],
			function ( $step ) use ( $step_name ) {
				return $step['step'] === $step_name;
			}
		);
		if ( count( $steps ) ) {
			return $steps;
		}
		return null;
	}

	/**
	 * Adds resources to the list of files for the ZIP archive.
	 *
	 * @param string $step Step name to find resources for.
	 * @param string $type Type of resources ('plugins' or 'themes').
	 * @return array Array of file paths to include in the ZIP archive.
	 *
	 * @throws \Exception If there is an error creating the ZIP archive.
	 * @throws \InvalidArgumentException If the given slug is not a valid plugin or theme.
	 */
	protected function add_resource( $step, $type ) {
		$steps = $this->find_steps( $step );
		if ( null === $steps ) {
			return array();
		}

		$steps = array_filter(
			$steps,
			// phpcs:ignore
			function ( $resource ) use ( $type ) {
				if ( 'plugins' === $type ) {
					return 'self/plugins' === $resource['pluginZipFile']['resource'];
				} elseif ( 'themes' === $type ) {
					return 'self/themes' === $resource['themeZipFile']['resource'];
				}

				return false;
			}
		);

		if ( count( $steps ) === 0 ) {
			return array();
		}

		// Create 'plugins' or 'themes' directory.
		$this->maybe_create_dir( $this->working_dir . '/' . $type );

		$files = array();

		foreach ( $steps as $step ) {
			$resource = $step[ 'plugins' === $type ? 'pluginZipFile' : 'themeZipFile' ];
			if ( ! $this->is_plugin_dir( $resource['slug'] ) ) {
				throw new \InvalidArgumentException( 'Invalid plugin slug: ' . $resource['slug'] );
			}

			$destination = $this->working_dir . '/' . $type . '/' . $resource['slug'] . '.zip';
			$plugin_dir  = WP_CONTENT_DIR . '/' . $type . '/' . $resource['slug'];
			if ( ! is_dir( $plugin_dir ) ) {
				$plugin_dir = $plugin_dir . '.php';
				if ( ! file_exists( $plugin_dir ) ) {
					continue;
				}
			}
			$archive = new \PclZip( $destination );
			$result  = $archive->create( $plugin_dir, PCLZIP_OPT_REMOVE_PATH, WP_CONTENT_DIR . '/' . $type );
			if ( 0 === $result ) {
				throw new \Exception( $archive->errorInfo( true ) );
			}
			$files[] = $destination;
		}

		return $files;
	}

	/**
	 * Creates a JSON file from the schema.
	 *
	 * @return string Path to the created JSON schema file.
	 */
	private function create_json_schema_file() {
		$schema_file = $this->get_working_dir_path( 'woo-blueprint.json' );
		$this->wp_filesystem_put_contents( $schema_file, json_encode( $this->schema, JSON_PRETTY_PRINT ) );
		return $schema_file;
	}

	/**
	 * Cleans up the working directory by deleting it.
	 */
	private function clean() {
		Util::delete_dir( $this->working_dir );
	}
}
