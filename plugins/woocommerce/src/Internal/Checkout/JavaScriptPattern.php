<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

/**
 * Finds constructs in a JSON Schema `pattern` that JavaScript rejects.
 *
 * PHP validates patterns with PCRE, while checkout uses JavaScript `u` mode.
 * This conservative check identifies known incompatibilities for registration
 * notices. The frontend falls back to server validation for affected fields.
 *
 * @internal
 */
final class JavaScriptPattern {

	/**
	 * Escapes PCRE reads as an assertion or character class that `u` mode has no equivalent for.
	 *
	 * @var array<string, string>
	 */
	private const PCRE_ONLY_ESCAPES = array(
		'A' => 'the "start of subject" assertion',
		'z' => 'the "end of subject" assertion',
		'Z' => 'the "end of subject" assertion',
		'G' => 'the "first matching position" assertion',
		'K' => 'the "keep out" reset',
		'h' => 'the horizontal whitespace class',
		'H' => 'the horizontal whitespace class',
		'R' => 'the line break sequence',
		'X' => 'the grapheme cluster class',
		'C' => 'the "single byte" class',
		'N' => 'the "non-newline" class',
		'a' => 'the "alarm" character',
		'e' => 'the "escape" character',
	);

	/**
	 * Punctuation a backslash may escape in `u` mode, both inside and outside a character class.
	 */
	private const ESCAPABLE_PUNCTUATION = '^$\\.*+?()[]{}|/';

	/**
	 * Escapes whose meaning changes inside a character class, and the ones that do not survive it.
	 *
	 * `\b` is a backspace inside a class, so it stays valid. `\B` and `\k` are
	 * assertions, which a class cannot contain.
	 */
	private const CLASS_UNSAFE_ESCAPES = 'Bk';

	/**
	 * Letters that introduce an escape requiring more characters after them.
	 */
	private const TAIL_ESCAPES = 'cuxpP';

	/**
	 * Other letters JavaScript accepts after a backslash in Unicode mode.
	 */
	private const JS_ESCAPE_LETTERS = 'bBdDfnrsStvwWk';

	/**
	 * Returns a description of the first construct `u` mode rejects, or null when it finds none.
	 *
	 * @param string $pattern The pattern, as written in a JSON Schema `pattern` keyword.
	 * @return string|null Description of the construct, or null when the pattern is usable.
	 */
	public static function find_unsupported_construct( string $pattern ): ?string {
		$length           = strlen( $pattern );
		$in_class         = false;
		$after_quantifier = false;
		$group_assertions = array();
		$capture_count    = 0;
		$max_reference    = 0;

		for ( $index = 0; $index < $length; $index++ ) {
			$char = $pattern[ $index ];

			if ( '\\' === $char ) {
				$result = self::check_escape( $pattern, $index, $in_class );

				if ( null !== $result ) {
					return $result;
				}

				$escape = $pattern[ $index + 1 ];
				$end    = self::skip_escape_tail( $pattern, $index + 1, $escape );
				if ( ! $in_class && ctype_digit( $escape ) && '0' !== $escape ) {
					$max_reference = max( $max_reference, (int) substr( $pattern, $index + 1, $end - $index ) );
				}

				$index            = $end;
				$after_quantifier = false;
				continue;
			}

			if ( '[' === $char ) {
				// A class opened inside a class starts a POSIX class, as in [[:alpha:]].
				if ( $in_class && $index + 1 < $length && ':' === $pattern[ $index + 1 ] ) {
					return 'a POSIX character class, which JavaScript does not support';
				}

				$in_class         = true;
				$after_quantifier = false;
				continue;
			}

			if ( $in_class ) {
				if ( ']' === $char ) {
					$in_class         = false;
					$after_quantifier = false;
				}
				continue;
			}

			// PCRE reads a stray bracket or brace as a literal, but `u` mode throws.
			if ( ']' === $char || '}' === $char ) {
				return 'a lone "' . $char . '", which JavaScript does not allow';
			}

			if ( '(' === $char ) {
				$result = self::check_group_prefix( $pattern, $index );

				if ( null !== $result ) {
					return $result;
				}

				$prefix       = substr( $pattern, $index + 1, 2 );
				$is_assertion = in_array( $prefix, array( '?=', '?!' ), true ) || in_array( substr( $pattern, $index + 1, 3 ), array( '?<=', '?<!' ), true );
				if ( '?' !== ( $pattern[ $index + 1 ] ?? '' ) || ( '?<' === $prefix && ! $is_assertion ) ) {
					++$capture_count;
				}
				$group_assertions[] = $is_assertion;

				$after_quantifier = false;
				continue;
			}

			if ( ')' === $char ) {
				if ( true === array_pop( $group_assertions ) && $index + 1 < $length && self::quantifier_end( $pattern, $index + 1 ) >= 0 ) {
					return 'a quantified lookaround assertion, which JavaScript does not support';
				}

				$after_quantifier = false;
				continue;
			}

			if ( '+' === $char && $after_quantifier ) {
				return 'a possessive quantifier, which JavaScript does not support';
			}

			$quantifier_end = self::quantifier_end( $pattern, $index );

			if ( $quantifier_end >= 0 ) {
				$after_quantifier = true;
				$index            = $quantifier_end;
				continue;
			}

			if ( '{' === $char ) {
				return 'an incomplete quantifier, which JavaScript does not support';
			}

			$after_quantifier = false;
		}

		if ( $in_class ) {
			return 'an unterminated character class';
		}

		// Forward references are valid, so count all capturing groups before checking them.
		if ( $max_reference > $capture_count ) {
			return 'a numeric escape without a matching capture group, which JavaScript does not support';
		}

		return null;
	}

	/**
	 * Describes what `u` mode rejects about an escape, if anything.
	 *
	 * @param string $pattern  The pattern.
	 * @param int    $index    Position of the backslash.
	 * @param bool   $in_class Whether the escape sits inside a character class.
	 * @return string|null Description of the construct, or null when the escape is usable.
	 */
	private static function check_escape( string $pattern, int $index, bool $in_class ): ?string {
		$length = strlen( $pattern );

		if ( $index + 1 >= $length ) {
			return 'a trailing backslash';
		}

		$escape = $pattern[ $index + 1 ];

		if ( ctype_digit( $escape ) ) {
			if ( $in_class && '0' !== $escape ) {
				return 'a numeric escape inside a character class, which JavaScript does not support';
			}

			// A leading zero starts an octal escape, which `u` mode drops entirely.
			if ( '0' === $escape && $index + 2 < $length && ctype_digit( $pattern[ $index + 2 ] ) ) {
				return 'an octal escape, which JavaScript does not support';
			}

			return null;
		}

		if ( ctype_alpha( $escape ) ) {
			if ( isset( self::PCRE_ONLY_ESCAPES[ $escape ] ) ) {
				return '\\' . $escape . ', which JavaScript does not support as ' . self::PCRE_ONLY_ESCAPES[ $escape ];
			}

			if ( $in_class && false !== strpos( self::CLASS_UNSAFE_ESCAPES, $escape ) ) {
				return '\\' . $escape . ' inside a character class, which JavaScript does not support';
			}

			if ( false !== strpos( self::TAIL_ESCAPES, $escape ) ) {
				return self::check_escape_tail( $pattern, $index, $escape );
			}

			if ( 'k' === $escape && '<' !== ( $pattern[ $index + 2 ] ?? '' ) ) {
				return '\\k without a name in angle brackets, which JavaScript does not support';
			}

			if ( false === strpos( self::JS_ESCAPE_LETTERS, $escape ) ) {
				return '\\' . $escape . ', an escape JavaScript does not support';
			}

			return null;
		}

		if ( false !== strpos( self::ESCAPABLE_PUNCTUATION, $escape ) ) {
			return null;
		}

		if ( $in_class && '-' === $escape ) {
			return null;
		}

		// `u` mode only allows escaping syntax characters, so a backslash before
		// a literal such as `-`, `@`, or a space throws.
		return '\\' . $escape . ', an escape JavaScript does not allow ' . ( $in_class ? 'inside' : 'outside' ) . ' a character class';
	}

	/**
	 * Validates the characters an escape such as `\x`, `\u` or `\p` needs after it.
	 *
	 * @param string $pattern The pattern.
	 * @param int    $index   Position of the backslash.
	 * @param string $escape  The character after the backslash.
	 * @return string|null Description of the construct, or null when the escape is usable.
	 */
	private static function check_escape_tail( string $pattern, int $index, string $escape ): ?string {
		$rest = (string) substr( $pattern, $index + 2 );

		if ( 'c' === $escape ) {
			return '' !== $rest && ctype_alpha( $rest[0] ) ? null : 'an incomplete control escape, which JavaScript does not support';
		}

		if ( 'x' === $escape ) {
			$digits = substr( $rest, 0, 2 );

			return 2 === strlen( $digits ) && ctype_xdigit( $digits ) ? null : 'an incomplete hex escape, which JavaScript does not support';
		}

		if ( 'u' === $escape ) {
			if ( 0 === strpos( $rest, '{' ) ) {
				return self::has_braced_hex( $rest, 0 ) ? null : 'an incomplete unicode escape, which JavaScript does not support';
			}

			$digits = substr( $rest, 0, 4 );

			return 4 === strlen( $digits ) && ctype_xdigit( $digits ) ? null : 'an incomplete unicode escape, which JavaScript does not support';
		}

		// \p{...} and \P{...} need a property name in braces.
		if ( 'p' === $escape || 'P' === $escape ) {
			return self::has_braced_name( $rest ) ? null : 'an incomplete property escape, which JavaScript does not support';
		}

		return null;
	}

	/**
	 * Whether a brace opens at `$offset` and holds one or more hex digits up to `}`.
	 *
	 * @param string $text   The text to inspect.
	 * @param int    $offset Position of the opening brace.
	 * @return bool
	 */
	private static function has_braced_hex( string $text, int $offset ): bool {
		$close = strpos( $text, '}', $offset + 1 );

		if ( false === $close || $close === $offset + 1 ) {
			return false;
		}

		return ctype_xdigit( substr( $text, $offset + 1, $close - $offset - 1 ) );
	}

	/**
	 * Whether a `\p{...}` or `\P{...}` escape has a non-empty name in braces.
	 *
	 * @param string $text The text after the escape letter, starting at the brace.
	 * @return bool
	 */
	private static function has_braced_name( string $text ): bool {
		if ( 0 !== strpos( $text, '{' ) ) {
			return false;
		}

		$close = strpos( $text, '}' );

		return false !== $close && $close > 1;
	}

	/**
	 * Skips the fixed-length tail of a multi-character escape.
	 *
	 * @param string $pattern The pattern.
	 * @param int    $start   Position of the character after the backslash.
	 * @param string $escape  The character after the backslash.
	 * @return int Position of the last character the escape consumes.
	 */
	private static function skip_escape_tail( string $pattern, int $start, string $escape ): int {
		$length = strlen( $pattern );

		if ( ( 'p' === $escape || 'P' === $escape ) && $start + 1 < $length && '{' === $pattern[ $start + 1 ] ) {
			$close = strpos( $pattern, '}', $start + 2 );

			return false === $close ? $length - 1 : $close;
		}

		if ( 'k' === $escape && $start + 1 < $length && '<' === $pattern[ $start + 1 ] ) {
			$close = strpos( $pattern, '>', $start + 2 );

			return false === $close ? $length - 1 : $close;
		}

		if ( 'u' === $escape ) {
			if ( $start + 1 < $length && '{' === $pattern[ $start + 1 ] ) {
				$close = strpos( $pattern, '}', $start + 2 );

				return false === $close ? $length - 1 : $close;
			}

			return min( $start + 4, $length - 1 );
		}

		if ( 'x' === $escape ) {
			return min( $start + 2, $length - 1 );
		}

		if ( 'c' === $escape ) {
			return min( $start + 1, $length - 1 );
		}

		if ( ctype_digit( $escape ) ) {
			$end = $start;

			while ( $end + 1 < $length && ctype_digit( $pattern[ $end + 1 ] ) ) {
				++$end;
			}

			return $end;
		}

		return $start;
	}

	/**
	 * Returns the last position of a `{n}`, `{n,}` or `{n,m}` quantifier, or -1 when none starts here.
	 *
	 * @param string $pattern The pattern.
	 * @param int    $index   Position to check.
	 * @return int The last position of the quantifier, or -1.
	 */
	private static function quantifier_end( string $pattern, int $index ): int {
		$char = $pattern[ $index ];

		if ( '*' === $char || '+' === $char || '?' === $char ) {
			return $index;
		}

		if ( '{' !== $char ) {
			return -1;
		}

		if ( 1 === preg_match( '/^\{[0-9]+(?:,[0-9]*)?\}/', (string) substr( $pattern, $index ), $matches ) ) {
			return $index + strlen( $matches[0] ) - 1;
		}

		return -1;
	}

	/**
	 * Describes what `u` mode rejects about a `(?`-style group, if anything.
	 *
	 * @param string $pattern The pattern.
	 * @param int    $index   Position of the `(`.
	 * @return string|null Description of the construct, or null when the group is usable.
	 */
	private static function check_group_prefix( string $pattern, int $index ): ?string {
		$length = strlen( $pattern );

		if ( $index + 1 >= $length || '?' !== $pattern[ $index + 1 ] ) {
			return null;
		}

		$after = $index + 2 < $length ? $pattern[ $index + 2 ] : '';
		$rest  = (string) substr( $pattern, $index + 2 );

		// Non-capturing groups and both lookaheads are supported in `u` mode.
		if ( ':' === $after || '=' === $after || '!' === $after ) {
			return null;
		}

		// (?<= lookbehind, (?<! negative lookbehind, (?<name> named capture.
		if ( '<' === $after ) {
			return null;
		}

		// An inline modifier group only exists with a colon, as in (?i:...), and
		// `u` mode only knows the i, m and s modifiers.
		if ( 1 === preg_match( '/^[ims]*(?:-[ims]+)?:/', $rest ) ) {
			return null;
		}

		if ( 1 === preg_match( '/^[ims]*(?:-[ims]+)?\)/', $rest ) ) {
			return 'an inline flag, which JavaScript does not support';
		}

		if ( 1 === preg_match( '/^[A-Za-z-]+[:)]/', $rest ) ) {
			return 'the "' . $after . '" modifier, which JavaScript does not support';
		}

		if ( 'P' === $after ) {
			return 'a PCRE named group, which JavaScript does not support';
		}

		if ( '#' === $after ) {
			return 'a comment group, which JavaScript does not support';
		}

		if ( '>' === $after ) {
			return 'an atomic group, which JavaScript does not support';
		}

		if ( '|' === $after ) {
			return 'a branch reset group, which JavaScript does not support';
		}

		if ( 'R' === $after ) {
			return 'a recursion group, which JavaScript does not support';
		}

		return 'a group syntax that JavaScript does not support';
	}
}
