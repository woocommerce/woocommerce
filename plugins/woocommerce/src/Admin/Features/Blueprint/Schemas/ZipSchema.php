<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class ZipSchema extends Schema {
	protected string $unzip_path;
	protected bool $filesystem_initialized = false;
	public function __construct($zip_path, $unzip_path = null) {
		$this->unzip_path = $unzip_path ?? wp_upload_dir()['path'];
		$unzip = $this->unzip($zip_path, $this->unzip_path);
		if (!$unzip) {
			throw new \Exception("Unable to unzip the file to {$zip_path}. Please check the directory permission.");
		}

		$schema = json_decode(file_get_contents($this->unzip_path.'/woo-blueprint.json'));
		if (!$this->validate()) {
			throw new \InvalidArgumentException($this->unzip_path.'/woo-blueprint.json is not a valid JSON.');
		}
		$this->schema = $schema;
	}

	public function get_unzip_path() {
		return $this->unzip_path;
	}

	protected function unzip($zip_path, $to) {
		if ($this->filesystem_initialized === false ) {
			if ( !function_exists('WP_Filesystem')) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			\WP_Filesystem();
		}

		$unzip =  \unzip_file($zip_path, $to);
		if (!$unzip) {
			throw new \Exception("Unable to unzip the file to {$zip_path}. Please check directory permission.");
		}

		return $unzip;
	}

	public function validate() {
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		// @todo validate with JSON Schema
		return true;
	}
}
