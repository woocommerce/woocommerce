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
 * @since 2.20.0
 */
class Css_Selector_Normalizer {
	/**
	 * Selector part that keeps link rules off the buttons the email renderers output.
	 *
	 * WordPress link rules skip buttons with `:not(.wp-element-button)`. Email buttons carry these classes instead.
	 */
	private const EMAIL_BUTTON_EXCLUSIONS = ':not(.button-link):not(.product-button-link)';

	/**
	 * Pattern for a quoted string, without backtracking so long values such as data URLs can't exhaust PCRE.
	 */
	private const STRING_PATTERN = <<<'REGEX'
"[^"\\]*+(?:\\.[^"\\]*+)*+"|'[^'\\]*+(?:\\.[^'\\]*+)*+'
REGEX;

	/**
	 * Pattern for text whose characters are not selector syntax: a quoted string, a comment or an escaped character.
	 */
	private const LITERAL_PATTERN = self::STRING_PATTERN . '|/\*.*?\*/|\\\\.';

	/**
	 * Normalize rule selectors without changing declarations or at-rule conditions.
	 *
	 * @since 2.20.0
	 * @param string $css Collected stylesheet.
	 * @return string
	 */
	public static function normalize( string $css ): string {
		if ( false === stripos( $css, ':where(' ) && false === strpos( $css, ':not(.wp-element-button)' ) ) {
			return $css;
		}

		$tokens  = preg_split( '~(' . self::LITERAL_PATTERN . '|[{};])~s', $css, -1, PREG_SPLIT_DELIM_CAPTURE );
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
		if ( 0 === strpos( $selector, '@' ) ) {
			return $prelude;
		}

		if ( false !== stripos( $prelude, ':where(' ) ) {
			$selectors = array();
			foreach ( self::split_selectors( $prelude ) as $part ) {
				$selectors = array_merge( $selectors, self::expand_where( $part ) );
			}
			$prelude = implode( ', ', $selectors );
		}

		// Without `:where()` a link rule would beat the email button’s own color.
		return (string) preg_replace( '/:not\(\.wp-element-button\)(?!' . preg_quote( self::EMAIL_BUTTON_EXCLUSIONS, '/' ) . ')/', ':not(.wp-element-button)' . self::EMAIL_BUTTON_EXCLUSIONS, $prelude );
	}

	/**
	 * Expand selector lists inside :where(), including nested functions.
	 *
	 * @param string $selector Rule selector.
	 * @return string[]
	 */
	private static function expand_where( string $selector ): array {
		$pattern = '~(?:' . self::LITERAL_PATTERN . ')(*SKIP)(*F)|:where\((?<inner>(?:[^()"\'\\\\]+|\\\\.|' . self::STRING_PATTERN . '|\((?&inner)\))*)\)~is';
		if ( ! preg_match( $pattern, $selector, $matches, PREG_OFFSET_CAPTURE ) || '' === trim( $matches['inner'][0] ) ) {
			return array( trim( $selector ) );
		}

		$before    = substr( $selector, 0, $matches[0][1] );
		$after     = substr( $selector, $matches[0][1] + strlen( $matches[0][0] ) );
		$selectors = array();
		$parts     = self::split_selectors( $matches['inner'][0] );

		// Keep the existing treatment of WordPress’s :root :where(...) wrapper.
		$before = (string) preg_replace( '/:root\s+$/i', '', $before );
		$prefix = (string) preg_replace( '~' . self::LITERAL_PATTERN . '~s', '', $before );
		if ( substr_count( $prefix, '(' ) > substr_count( $prefix, ')' ) ) {
			// Inside another function, only a single `:where()` that is the whole argument keeps its meaning as plain text.
			if ( count( $parts ) > 1 || ! preg_match( '/\(\s*$/', $prefix ) || ! preg_match( '/^\s*\)/', $after ) ) {
				return array( $selector );
			}
		}

		if ( '' !== ltrim( $prefix ) && ! self::can_follow( ltrim( $prefix ), $parts ) ) {
			return array( $selector );
		}
		foreach ( $parts as $part ) {
			$universal = preg_match( '/(?:^|[\s>+~])$/', $before ) && 0 === strpos( $part, ':' ) ? '*' : '';
			$selectors = array_merge( $selectors, self::expand_where( $before . $universal . $part . $after ) );
		}

		return $selectors;
	}

	/**
	 * Check that each `:where()` argument means the same as plain text after the given prefix.
	 *
	 * An argument with a combinator, such as `.a .b`, would match other elements, and so would a type selector joined to the prefix.
	 *
	 * @param string   $prefix Selector text before `:where()`, without comments and strings.
	 * @param string[] $parts  The `:where()` arguments.
	 * @return bool
	 */
	private static function can_follow( string $prefix, array $parts ): bool {
		$joined = ! preg_match( '/[\s>+~(]$/', $prefix );
		foreach ( $parts as $part ) {
			$outer = (string) preg_replace( '/\[[^\]]*\]|\((?:[^()]++|(?R))*\)/', '', $part );
			if ( preg_match( '/[\s>+~]/', trim( $outer ) ) || ( $joined && preg_match( '/^[a-z*|\\\\]/i', $part ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Split a selector list while keeping commas inside functions and strings.
	 *
	 * @param string $selector Selector list.
	 * @return string[]
	 */
	private static function split_selectors( string $selector ): array {
		$tokens = preg_split( '~(' . self::LITERAL_PATTERN . '|[(),])~s', $selector, -1, PREG_SPLIT_DELIM_CAPTURE );
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
