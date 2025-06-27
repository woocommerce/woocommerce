<?php
/**
 * Helper script for extracting changelog items from a version's readme.txt and merging with the global changelog.txt file.
 * The script receives as arguments both the source readme.txt and the existing changelog.txt file and outputs the new
 * file.
 *
 * Usage:
 * `php release-readme-to-changelog.php <path to source readme.txt> <path to source changelog.txt>`
 */

$path_readme    = trim( $argv[1]  ?? '' );
$path_changelog = trim( $argv[2] ?? '' );

if ( ! is_writable( $path_changelog ) ) {
	echo "::error::Changelog file '$path_changelog' is not writable.";
	die( 1 );
}

/*
 * Extract version and changelog from readme.txt.
 */
 if ( ! is_readable( $path_readme ) ) {
	echo "::error::The readme.txt file '$path_readme' is not readable.";
	die( 1 );
}

$readme = file_get_contents( $path_readme );

// Changelog section.
preg_match(
	'/== Changelog ==[\s]+(?<changelog>=.*?)(?=\n==|$)/s',
	file_get_contents( $path_readme ),
	$matches
);

if ( empty( $matches['changelog'] ) ) {
	echo "::error::Could not extract changelog from readme.txt.";
	die( 1 );
}

$version_changelog = $matches['changelog'];
$version_changelog = preg_replace( '/\[See changelog for all versions.*/s', '', $version_changelog );
$version_changelog = trim( $version_changelog );

// Version number.
if ( ! preg_match( '/^\= (\d+\.\d+\.\d+).*=\n/', $version_changelog, $matches ) )  {
	echo "::error::Could not extract version number from readme.txt.";
	die( 1 );
}

$version = $matches[1];

/*
 * Read global changelog and insert new changelog at the right location.
 */
 if ( ! is_readable( $path_changelog ) ) {
	echo "::error::The changelog.txt file '$path_changelog' is not readable.";
	die( 1 );
}

$new_changelog = array();
$version_changelog_inserted = false;

foreach ( file( $path_changelog ) as $line ) {
	if ( ! $version_changelog_inserted && preg_match( '/(?<version>\d+.\d+.\d+) \d{4}-\d{2}-\d{2} =/', $line, $matches ) ) {
		$cmp = version_compare( $matches['version'], $version );

		if ( 0 === $cmp ) {
			echo "::error::Version '$version' already exists in the changelog.";
			die ( 1 );
		}

		if ( -1 === $cmp ) {
			$new_changelog[]            = $version_changelog;
			$new_changelog[]            = "\n\n\n";
			$version_changelog_inserted = true;
		}
	}

	$new_changelog[] = $line;
}

file_put_contents( $path_changelog, implode( '', $new_changelog ) );
