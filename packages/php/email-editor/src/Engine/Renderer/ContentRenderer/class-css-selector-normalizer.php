<?php
/**
 * CSS selector normalization for email rendering.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

/**
 * Converts WordPress :where() selectors to selectors supported by the CSS inliner.
 *
 * @internal
 * @since 2.19.0
 */
class Css_Selector_Normalizer {

	/**
	 * Normalize rule selectors without changing declarations or at-rule conditions.
	 *
	 * @since 2.19.0
	 * @param string $css Collected stylesheet.
	 * @return string
	 */
	public static function normalize( string $css ): string {
		if ( false === stripos( $css, ':where(' ) ) {
			return $css;
		}

		$pattern = <<<'REGEX'
~("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|/\*.*?\*/|\\.|[{};])~s
REGEX;
		$tokens  = preg_split( $pattern, $css, -1, PREG_SPLIT_DELIM_CAPTURE );
		$result  = '';
		$prelude = '';
		if ( false === $tokens ) {
			return $css;
		}

		foreach ( $tokens as $token ) {
			if ( '{' === $token ) {
				$prelude = self::normalize_rule( $prelude );
			}
			$prelude .= $token;
			if ( in_array( $token, array( '{', '}', ';' ), true ) ) {
				$result .= $prelude;
				$prelude = '';
			}
		}

		return $result . $prelude;
	}

	/**
	 * Normalize the selector list before a declaration block.
	 *
	 * @param string $prelude Rule selector or at-rule condition.
	 * @return string
	 */
	private static function normalize_rule( string $prelude ): string {
		$selector = ltrim( (string) preg_replace( '~/\*.*?\*/~s', '', $prelude ) );
		if ( 0 === strpos( $selector, '@' ) || false === stripos( $prelude, ':where(' ) ) {
			return $prelude;
		}

		$selectors = array();
		foreach ( self::split_selectors( $prelude ) as $part ) {
			$selectors = array_merge( $selectors, self::expand_where( $part ) );
		}

		return implode( ', ', $selectors );
	}

	/**
	 * Expand selector lists inside :where(), including nested functions.
	 *
	 * @param string $selector Rule selector.
	 * @return string[]
	 */
	private static function expand_where( string $selector ): array {
		$pattern = <<<'REGEX'
~"(?:\\.|[^"\\])*"(*SKIP)(*F)|'(?:\\.|[^'\\])*'(*SKIP)(*F)|/\*.*?\*/(*SKIP)(*F)|\\.(*SKIP)(*F)|:where\((?<inner>(?:[^()"'\\]+|\\.|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|\((?&inner)\))*)\)~is
REGEX;
		if ( ! preg_match( $pattern, $selector, $matches, PREG_OFFSET_CAPTURE ) || '' === trim( $matches['inner'][0] ) ) {
			return array( trim( $selector ) );
		}

		$before    = substr( $selector, 0, $matches[0][1] );
		$after     = substr( $selector, $matches[0][1] + strlen( $matches[0][0] ) );
		$selectors = array();
		$parts     = self::split_selectors( $matches['inner'][0] );
		$quoted    = <<<'REGEX'
~"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|/\*.*?\*/|\\.~s
REGEX;
		$prefix    = (string) preg_replace( $quoted, '', $before );
		if ( count( $parts ) > 1 && substr_count( $prefix, '(' ) > substr_count( $prefix, ')' ) ) {
			// Distributing a list inside another function can change its meaning, especially in :not().
			return array( $selector );
		}

		// Keep the existing treatment of WordPress's :root :where(...) wrapper.
		$before = (string) preg_replace( '/:root\s+$/i', '', $before );
		foreach ( $parts as $part ) {
			$universal = preg_match( '/(?:^|[\s>+~])$/', $before ) && 0 === strpos( $part, ':' ) ? '*' : '';
			$selectors = array_merge( $selectors, self::expand_where( $before . $universal . $part . $after ) );
		}

		return $selectors;
	}

	/**
	 * Split a selector list while keeping commas inside functions and strings.
	 *
	 * @param string $selector Selector list.
	 * @return string[]
	 */
	private static function split_selectors( string $selector ): array {
		$pattern = <<<'REGEX'
~("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|/\*.*?\*/|\\.|[(),])~s
REGEX;
		$tokens  = preg_split( $pattern, $selector, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( false === $tokens ) {
			return array( $selector );
		}

		$selectors = array();
		$current   = '';
		$depth     = 0;

		foreach ( $tokens as $token ) {
			if ( '(' === $token ) {
				++$depth;
			} elseif ( ')' === $token ) {
				--$depth;
			} elseif ( ',' === $token && 0 === $depth ) {
				$selectors[] = trim( $current );
				$current     = '';
				continue;
			}
			$current .= $token;
		}
		$selectors[] = trim( $current );

		return $selectors;
	}
}
