<?php

namespace Automattic\WooCommerce\Blueprint;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Utility functions.
 */
class Util {
	/**
	 * Ensure that the given path is a valid path within the WP_CONTENT_DIR.
	 *
	 * @param string $path The path to be validated.
	 *
	 * @return string
	 * @throws \InvalidArgumentException If the path is invalid.
	 */
	public static function ensure_wp_content_path( $path ) {
		$path = realpath( $path );
		if ( false === $path || strpos( $path, WP_CONTENT_DIR ) !== 0 ) {
			throw new \InvalidArgumentException( "Invalid path: $path" );
		}

		return $path;
	}

	/**
	 * @param array $row array row with key and value
	 * @param string $table name
	 * @param string $type one of insert, insert ignore, replace into
	 *
	 * @return false|string
	 */
	public static function array_to_insert_sql ($row, $table, $type = 'insert ignore') {
		if (empty($row) || !is_array($row)) {
			return false; // Return false if input data is empty or not an array
		}

		$allowed_types = ['insert', 'insert ignore', 'replace into'];
		if (!in_array($type, $allowed_types)) {
			return false; // Return false if input type is not valid
		}

		// Get column names and values
		$columns = '`' . implode('`, `', array_keys($row)) . '`';
		$escapedValues = array_map(fn($value) => "'" . addslashes($value) . "'", $row);
		$values = implode(', ', $escapedValues);
		// Construct final SQL query
		return "{$type} `$table` ($columns) VALUES ($values);";
	}

	/**
	 * Convert a string from snake_case to camelCase.
	 *
	 * @param string $string The string to be converted.
	 *
	 * @return string
	 */
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

	/**
	 * Convert a string from camelCase to snake_case.
	 * 
	 * @param string $input The string to be converted.
	 *
	 * @return string
	 */
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


	/**
	 * Index an array using a callback function.
	 *
	 * @param array    $array The array to be indexed.
	 * @param callable $callback The callback function to be called for each element.
	 *
	 * @return array
	 */
	// phpcs:ignore
	public static function index_array( $array, $callback ) {
		$result = array();
		foreach ( $array as $key => $value ) {
			$new_key            = $callback( $key, $value );
			$result[ $new_key ] = $value;
		}
		return $result;
	}

	/**
	 * Check to see if given string is a valid WordPress plugin slug.
	 *
	 * @param string $slug The slug to be validated.
	 *
	 * @return bool
	 */
	public static function is_valid_wp_plugin_slug( $slug ) {
		// Check if the slug only contains allowed characters.
		if ( preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir_path The path to the directory.
	 *
	 * @return void
	 * @throws \InvalidArgumentException If $dir_path is not a directory.
	 */
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
				// phpcs:ignore
				unlink( $file );
			}
		}
		// phpcs:ignore
		rmdir( $dir_path );
	}
}
