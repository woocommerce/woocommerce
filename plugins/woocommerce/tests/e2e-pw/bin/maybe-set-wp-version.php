<?php

$env = getenv();

$wp_version  = isset( $env['WP_VERSION'] ) ? $env['WP_VERSION'] : null;
$wp_env_path = __DIR__ . '/../../../.wp-env.json';

/**
 * Adds or removes the "core" and "mappings" keys from the .wp-env.json file.
 */
if ( file_exists( $wp_env_path ) ) {
	$wp_env = json_decode( file_get_contents( $wp_env_path ), true );

	if ( is_null( $wp_version ) ) {
		echo "Removed WP Version\n";
		unset( $wp_env["core"] );
		file_put_contents( $wp_env_path, json_encode( $wp_env, JSON_PRETTY_PRINT ) );
	} else {
		echo "Set WP Version to $wp_version \n";
		$wp_env["core"] = "WordPress/WordPress#tags/$wp_version";
		file_put_contents( $wp_env_path, json_encode( $wp_env, JSON_PRETTY_PRINT ) );
	}
} else {
	throw new Exception( ".wp_env.json doesn't exist!" );
}
