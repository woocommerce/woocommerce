<?php
// phpcs:ignoreFile

if ( ! class_exists( UPDATE_WP_JSON::class ) ) {
	class UPDATE_WP_JSON {

		private $wp_env_path  = __DIR__ . '/../../../.wp-env.json';
		private $wp_json      = [];
		private $wc_version   = null;
		private $wp_version   = null;
		private $php_version  = null;

		public function __construct() {
			if ( file_exists( $this->wp_env_path ) ) {
				$this->wp_json = json_decode( file_get_contents( $this->wp_env_path ), true );
			} else {
				throw new Exception( ".wp-env.json doesn't exist!" );
			}

			$env = getenv();

			$this->wp_version  = isset( $env['WP_VERSION'] ) ? $env['WP_VERSION'] : null;
			$this->wc_version  = isset( $env['WC_TEST_VERSION'] ) ? $env['WC_TEST_VERSION'] : null;
			$this->php_version = isset( $env['PHP_VERSION'] ) ? $env['PHP_VERSION'] : null;
		}

		public function set_wp_version(){
			if ( $this->wp_version ) {

				$version = "WordPress/WordPress#tags/$this->wp_version";

				if ( 'trunk' === $this->wp_version ) {
					$version = "WordPress/WordPress";
				}

				if ( 'nightly' === $this->wp_version ) {
					$version = "https://wordpress.org/nightly-builds/wordpress-latest.zip";
				}

				echo "Set WP Version to $version \n";
				$this->wp_json["core"] = $version;
			}
		}

		public function revert_wp_version(){
			unset( $this->wp_json["core"] );
		}

		public function set_wc_version(){
			if ( $this->wc_version ) {
				echo "Set WC Version to $this->wc_version \n";
				$this->wp_json["plugins"] = [ "https://github.com/woocommerce/woocommerce/releases/download/$this->wc_version/woocommerce.zip" ];
			}
		}

		public function revert_wc_version(){
			$this->wp_json["plugins"] = [ "." ];
		}

		public function set_php_version(){
			if ( $this->php_version ) {
				echo "Set PHP Version to $this->php_version \n";
				$this->wp_json["phpVersion"] = $this->php_version;
			}
		}

		public function revert_php_version(){
			$this->wp_json["phpVersion"] = "7.4";
		}

		public function update(){
			$this->set_wp_version();
			$this->set_wc_version();
			$this->set_php_version();
			file_put_contents( $this->wp_env_path, json_encode( $this->wp_json, JSON_PRETTY_PRINT ) );
		}

		public function revert(){
			$this->revert_wp_version();
			$this->revert_wc_version();
			$this->revert_php_version();
			file_put_contents( $this->wp_env_path, json_encode( $this->wp_json, JSON_PRETTY_PRINT ) );

			echo "Reverted .wp-env.json \n";
		}
	}
}


$env = getenv();
$update_wp_json = isset( $env['UPDATE_WP_JSON_FILE'] ) ? $env['UPDATE_WP_JSON_FILE'] : null;

if ( is_null( $update_wp_json ) || $update_wp_json == false ) {
	$wp_json = new UPDATE_WP_JSON();
	$wp_json->revert();
} else {
	$wp_json = new UPDATE_WP_JSON();
	$wp_json->update();
}
