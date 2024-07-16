<?php

namespace Automattic\WooCommerce\Blueprint\Schemas;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;

class ZipSchema extends JsonSchema {
	use UseWPFunctions;

	protected string $unzip_path;
	protected string $unzipped_path;

	public function __construct( $zip_path, $unzip_path = null ) {
		// Set the unzip path, defaulting to the WordPress upload directory if not provided
		$this->unzip_path = $unzip_path ?? $this->wp_upload_dir()['path'];

		// Attempt to unzip the file
		if (!$this->wp_unzip_file($zip_path, $this->unzip_path)) {
			throw new \Exception("Unable to unzip the file to {$zip_path}. Please check the directory permission.");
		}

		// Determine the name of the unzipped directory
		$unzipped_dir_name = str_replace('.zip', '', basename($zip_path));

		// Define the paths to the JSON file and the unzipped directory
		$json_path = "{$this->unzip_path}/{$unzipped_dir_name}/woo-blueprint.json";
		$this->unzipped_path = "{$this->unzip_path}/{$unzipped_dir_name}";

		// Check if the JSON file exists in the expected location
		if (!file_exists($json_path)) {
			// Update paths if the JSON file is in the unzip root directory
			$this->unzipped_path = $this->unzip_path;
			$json_path = "{$this->unzip_path}/woo-blueprint.json";
		}

		parent::__construct( $json_path );
	}

	public function get_unzipped_path() {
		return $this->unzipped_path;
	}
}
