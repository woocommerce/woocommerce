<?php

namespace Automattic\WooCommerce\Blueprint;

class ZipSchema extends JsonSchema {
	protected string $unzip_path;
	protected string $unzipped_path;
	protected bool $filesystem_initialized = false;

	public function __construct( $zip_path, $unzip_path = null ) {
		$this->unzip_path = $unzip_path ?? wp_upload_dir()['path'];
		$unzip            = $this->unzip( $zip_path, $this->unzip_path );
		if ( ! $unzip ) {
			throw new \Exception( "Unable to unzip the file to {$zip_path}. Please check the directory permission." );
		}

		$unzipped_dir_name   = str_replace( '.zip', '', basename( $zip_path ) );
		$json_path           = $this->unzip_path . '/' . $unzipped_dir_name . '/woo-blueprint.json';
		$this->unzipped_path = $this->unzip_path . '/' . $unzipped_dir_name;
		if ( ! file_exists( $json_path ) ) {
			$this->unzipped_path = $this->unzip_path;
			$json_path           = $this->unzip_path . '/woo-blueprint.json';
		}

		parent::__construct( $json_path );
	}

	public function get_unzipped_path() {
		return $this->unzipped_path;
	}

	protected function unzip( $zip_path, $to ) {
		if ( $this->filesystem_initialized === false ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			\WP_Filesystem();
		}

		$unzip = \unzip_file( $zip_path, $to );
		if ( ! $unzip ) {
			throw new \Exception( "Unable to unzip the file to {$zip_path}. Please check directory permission." );
		}

		return $unzip;
	}
}
