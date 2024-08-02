<?php

namespace Automattic\WooCommerce\Blueprint;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Util {
	public static function ensure_wp_content_path( $path ) {
		$path = realpath( $path );
		if ( $path === false || strpos( $path, WP_CONTENT_DIR ) !== 0 ) {
			throw new \InvalidArgumentException( "Invalid path: $path" );
		}

		return $path;
	}

	public static function index_array( $array, $callback ) {
		$result = array();
		foreach ( $array as $key => $value ) {
			$new_key            = $callback( $key, $value );
			$result[ $new_key ] = $value;
		}
		return $result;
	}

	public static function is_valid_wp_plugin_slug( $slug ) {
		// Check if the slug only contains allowed characters
		if ( preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return true;
		}

		return false;
	}

	public static function delete_dir( $dir_path ) {
		if ( ! is_dir( $dir_path ) ) {
			throw new \InvalidArgumentException( "$dir_path must be a directory" );
		}
		if ( substr( $dir_path, strlen( $dir_path ) - 1, 1 ) !== '/' ) {
			$dir_path .= '/';
		}
		$files = glob( $dir_path . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				static::delete_dir( $file );
			} else {
				unlink( $file );
			}
		}
		rmdir( $dir_path );
	}
}
