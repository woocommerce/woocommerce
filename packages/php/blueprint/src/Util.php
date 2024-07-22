<?php

namespace Automattic\WooCommerce\Blueprint;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Util {
	public static function snake_to_camel( $string ) {
		// Split the string by underscores
		$words = explode( '_', $string );

		// Capitalize the first letter of each word
		$words = array_map( 'ucfirst', $words );

		// Join the words back together
		return implode( '', $words );
	}

	public static function array_flatten($array) {
		return new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
	}

	public static function camel_to_snake( $input ) {
		// Replace all uppercase letters with an underscore followed by the lowercase version of the letter
		$pattern     = '/([a-z])([A-Z])/';
		$replacement = '$1_$2';
		$snake       = preg_replace( $pattern, $replacement, $input );

		// Replace spaces with underscores
		$snake = str_replace( ' ', '_', $snake );

		// Convert the entire string to lowercase
		return strtolower( $snake );
	}

	public static function deactivate_plugin_by_slug( $slug ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get all installed plugins
		$all_plugins = get_plugins();

		// Loop through all plugins to find the one with the specified slug
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin
				deactivate_plugins( $plugin_path );
			}
		}
		return false;
	}

	public static function activate_plugin_by_slug( $slug ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get all installed plugins
		$all_plugins = get_plugins();

		// Loop through all plugins to find the one with the specified slug
		foreach ( $all_plugins as $plugin_path => $plugin_info ) {
			// Check if the plugin path contains the slug
			if ( strpos( $plugin_path, $slug . '/' ) === 0 ) {
				// Deactivate the plugin
				return activate_plugin( $plugin_path );
			}
		}
		return false;
	}

	public static function index_array( $array, $callback ) {
		$result = array();
		foreach ( $array as $key => $value ) {
			$new_key = $callback( $key, $value );
			$result[ $new_key ] = $value;
		}
		return $result;
	}

	public static function array_filter_by_field( $array, $field_name, $force_convert = false ) {
		if ( ! is_array( $array ) && $force_convert ) {
			$array = json_decode( json_encode( $array ), true );
		}
		$result = array();
		foreach ( $array as $item ) {
			if ( is_array( $item ) ) {
				if ( isset( $item[ $field_name ] ) ) {
					$result[] = $item;
				}
				// Recursively search in nested arrays
				$nestedResult = static::array_filter_by_field( $item, $field_name );
				if ( ! empty( $nestedResult ) ) {
					$result = array_merge( $result, $nestedResult );
				}
			}
		}
		return $result;
	}

	public static function delete_dir( $dirPath ) {
		if ( ! is_dir( $dirPath ) ) {
			throw new \InvalidArgumentException( "$dirPath must be a directory" );
		}
		if ( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) != '/' ) {
			$dirPath .= '/';
		}
		$files = glob( $dirPath . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				static::delete_dir( $file );
			} else {
				unlink( $file );
			}
		}
		rmdir( $dirPath );
	}
}
