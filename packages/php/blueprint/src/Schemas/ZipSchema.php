<?php

namespace Automattic\WooCommerce\Blueprint\Schemas;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ZipSchema
 *
 * Handles the import schema functionality for WooCommerce.
 *
 * @package Automattic\WooCommerce\Blueprint
 */
class ZipSchema extends JsonSchema {
	use UseWPFunctions;

	/**
	 * Path to the unzip.
	 *
	 * @var string|mixed The path to the zip file.
	 */
	protected string $unzip_path;

	/**
	 * Path to the unzipped file.
	 *
	 * @var string|mixed The path to the unzipped file.
	 */
	protected string $unzipped_path;

	/**
	 * ZipSchema constructor.
	 *
	 * @param string $zip_path The path to the zip file.
	 * @param string $unzip_path The path to unzip the file to.
	 *
	 * @throws \Exception If the file cannot be unzipped.
	 */
	public function __construct( $zip_path, $unzip_path = null ) {
		// Set the unzip path, defaulting to the WordPress upload directory if not provided.
		$this->unzip_path = $unzip_path ?? $this->wp_upload_dir()['path'];

		// Attempt to unzip the file.
		$unzip_result = $this->wp_unzip_file( $zip_path, $this->unzip_path );
		if ( $unzip_result instanceof \WP_Error ) {
			throw new \Exception( $unzip_result->get_error_message() );
		}

		// Determine the name of the unzipped directory.
		$unzipped_dir_name = str_replace( '.zip', '', basename( $zip_path ) );

		// Define the paths to the JSON file and the unzipped directory.
		$json_path           = "{$this->unzip_path}/{$unzipped_dir_name}/woo-blueprint.json";
		$this->unzipped_path = "{$this->unzip_path}/{$unzipped_dir_name}";

		// Check if the JSON file exists in the expected location.
		if ( ! file_exists( $json_path ) ) {
			// Update paths if the JSON file is in the unzip root directory.
			$this->unzipped_path = $this->unzip_path;
			$json_path           = "{$this->unzip_path}/woo-blueprint.json";
		}

		parent::__construct( $json_path );
	}

	/**
	 * Get the path to the unzipped file.
	 *
	 * @return mixed|string
	 */
	public function get_unzipped_path() {
		return $this->unzipped_path;
	}
}
