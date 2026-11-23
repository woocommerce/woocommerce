<?php
/**
 * Stubs for WordPress functions introduced after the version shipped with the
 * installed `php-stubs/wordpress-stubs` package.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_scrub_utf8/
 * @see https://developer.wordpress.org/reference/functions/wp_is_valid_utf8/
 */

/**
 * Replaces invalid UTF-8 byte sequences with the Unicode replacement character.
 *
 * @since 6.9.0
 *
 * @param string $text Text to check.
 * @return string
 */
function wp_scrub_utf8( $text ) {
}

/**
 * Checks whether a string is valid UTF-8.
 *
 * @since 6.9.0
 *
 * @param string $bytes String to check.
 * @return bool
 */
function wp_is_valid_utf8( $bytes ) {
}
