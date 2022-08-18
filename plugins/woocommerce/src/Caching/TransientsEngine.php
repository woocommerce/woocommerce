<?php

namespace Automattic\WooCommerce\Caching;

/**
 * A cache engine that uses WordPress transients for storage.
 * This is a temporary class intended to help debugging, don't merge to trunk!
 */
class TransientsEngine implements CacheEngine {

	// phpcs:disable Squiz.Commenting

	private $stdout;

	public function __construct() {
		$this->stdout = fopen( 'php://stdout', 'w' );
	}

	public function get_cached_object( string $key ) {
		$value = get_transient( $key );
		if ( $value ) {
			$this->print( "------- GET: cached object retrieved! key: $key\n" );
			return $value;
		} else {
			$this->print( "------- GET: null\n" );
			return null;
		}
	}

	public function cache_object( string $key, $object, int $expiration ): bool {
		$this->print( "------- SET: key $key\n" );
		return set_transient( $key, $object, $expiration );
	}

	public function delete_cached_object( string $key ): bool {
		$this->print( "------- DELETE: key $key\n" );
		return delete_transient( $key );
	}

	public function is_cached( string $key ): bool {
		$is_cached = ! is_null( $this->get_cached_object( $key ) );
		$this->print( "------- IS CACHED $key ? $is_cached\n" );
		return $is_cached;
	}

	private function print( $string ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fwrite( $this->stdout, $string );
	}

	// phpcs:enable Squiz.Commenting
}
