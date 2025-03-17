<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- We want to ignore this rule in bootstrap file.
require_once __DIR__ . '/../../vendor/autoload.php';

if ( ! function_exists( 'register_block_template' ) ) {
	/**
	 * Mock register_block_template function.
	 *
	 * @param string $name Template name.
	 * @param array  $attr Template attributes.
	 * @return string
	 */
	function register_block_template( $name, $attr ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $name;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock esc_attr function.
	 *
	 * @param string $attr Attribute to escape.
	 * @return string
	 */
	function esc_attr( $attr ) {
		return $attr;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html function.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Mock add_filter function.
	 *
	 * @param string   $tag Tag to add filter for.
	 * @param callable $callback Callback to call.
	 * @param int      $priority Optional. Used to specify the order in which the functions
	 *                                      associated with a particular filter are executed.
	 *                                      Lower numbers correspond with earlier execution,
	 *                                      and functions with the same priority are executed
	 *                                      in the order in which they were added to the filter. Default 10.
	 * @param int      $accepted_args Optional. The number of arguments the function accepts. Default 1.
	 * @return bool Always returns true.
	 */
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wp_filters;
		if ( ! isset( $wp_filters ) ) {
			$wp_filters = array();
		}
		$wp_filters[ $tag ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Mock apply_filters function.
	 *
	 * @param string $tag Tag to apply filters for.
	 * @param mixed  $value Value to filter.
	 * @param mixed  ...$args   Optional. Additional parameters to pass to the callback functions.
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	function apply_filters( $tag, $value, ...$args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wp_filters;
		if ( isset( $wp_filters[ $tag ] ) ) {
			foreach ( $wp_filters[ $tag ] as $callback ) {
				$value = call_user_func( $callback, $value );
			}
		}
		return $value;
	}
}

/**
 * Base class for unit tests.
 */
abstract class Email_Editor_Unit_Test extends \PHPUnit\Framework\TestCase {
}

require 'stubs.php';
