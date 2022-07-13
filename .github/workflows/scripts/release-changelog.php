<?php
/**
 * Helper script for inserting NEXT_CHANGELOG contents into changelog and readme files.
 */

$now = time();
if ( getenv( 'TIME_OVERRIDE' ) ) {
	$now = strtotime( getenv( 'TIME_OVERRIDE' ) );
}

$base_dir = dirname( dirname( dirname( __DIR__ ) ) );

// The release date is 26 days after the code freeze.
$release_time = strtotime( '+26 days', $now );
$release_date = date( 'Y-m-d', $release_time );

$changelog_file = $base_dir . '/changelog.txt';
$readme_file    = $base_dir . '/plugins/woocommerce/readme.txt';
$next_log_file  = $base_dir . '/plugins/woocommerce/NEXT_CHANGELOG.md';

$changelog = file_get_contents( $changelog_file );
$readme    = file_get_contents( $readme_file );
$next_log  = file_get_contents( $next_log_file );

$next_log  = preg_replace( "/= (\d+\.\d+\.\d+) YYYY-mm-dd =/", "= \\1 {$release_date} =", $next_log );

// Convert PR number to markdown link.
$next_log  = preg_replace( "/\[#(\d+)\]/", '[#$1](https://github.com/woocommerce/woocommerce/pull/$1)', $next_log );
$changelog = preg_replace( "/^== Changelog ==\n\n/", "== Changelog ==\n\n{$next_log}\n", $changelog );

$readme    = preg_replace( "/== Changelog ==\n(.*?)\[See changelog for all versions\]/s", "== Changelog ==\n\n{$next_log}\n\n[See changelog for all versions]", $readme );

file_put_contents( $changelog_file, $changelog );
file_put_contents( $readme_file, $readme );
