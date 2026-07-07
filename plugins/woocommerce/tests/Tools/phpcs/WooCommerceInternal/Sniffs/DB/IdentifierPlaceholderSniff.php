<?php
/**
 * IdentifierPlaceholderSniff.
 *
 * @package WooCommerce\Tests\Tools\PHPCS
 */

declare( strict_types=1 );

namespace WooCommerceInternal\Sniffs\DB;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Flags use of the `%i` SQL identifier placeholder inside a `wpdb::prepare()` call
 * that is not guarded by `wpdb::has_cap( 'identifier_placeholders' )`.
 *
 * WordPress 6.2 added `%i` to `wpdb::prepare()` for quoting table/column names, but a
 * `$wpdb` drop-in can run on a supported WordPress version without implementing it (its
 * `has_cap( 'identifier_placeholders' )` returns `false`). On such a layer `prepare()`
 * treats `%i` as a literal and shifts the remaining positional arguments, silently
 * producing malformed queries.
 *
 * Trusted identifiers (table/column names derived from `$wpdb->prefix`, `$wpdb->posts`,
 * or a data store's `get_*_table_name()`) should be interpolated directly into the query
 * string instead. When `%i` is genuinely required, guard it with a
 * `wpdb::has_cap( 'identifier_placeholders' )` check in the same function so a fallback
 * path can run on layers that lack `%i`.
 */
class IdentifierPlaceholderSniff implements Sniff {

	/**
	 * Capability string that unlocks the `%i` placeholder.
	 *
	 * @var string
	 */
	private const GUARD_CAPABILITY = 'identifier_placeholders';

	/**
	 * Registers the tokens this sniff wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register(): array {
		return array(
			T_CONSTANT_ENCAPSED_STRING,
			T_DOUBLE_QUOTED_STRING,
		);
	}

	/**
	 * Processes a string token, flagging an unguarded `%i` placeholder in a prepare() call.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ): void {
		$tokens  = $phpcs_file->getTokens();
		$content = $tokens[ $stack_ptr ]['content'];

		// Flag a `%i` identifier placeholder appearing in the string.
		// - `(?<![:%])` excludes STR_TO_DATE minute specifiers (`%H:%i:%s`) and escaped `%%i`.
		// - `(?![a-zA-Z0-9_])` excludes longer tokens such as `%input`.
		if ( ! preg_match( '/(?<![:%])%i(?![a-zA-Z0-9_])/', $content ) ) {
			return;
		}

		if ( ! $this->is_within_prepare_call( $phpcs_file, $stack_ptr ) ) {
			return;
		}

		if ( $this->has_identifier_placeholder_guard( $phpcs_file, $stack_ptr ) ) {
			return;
		}

		$phpcs_file->addError(
			'The %%i identifier placeholder is not implemented by every $wpdb drop-in on supported WordPress versions and can silently produce malformed queries. Interpolate a trusted identifier directly into the query string, or guard the %%i usage with wpdb::has_cap( \'identifier_placeholders\' ).',
			$stack_ptr,
			'Unguarded'
		);
	}

	/**
	 * Determines whether the given string token is an argument to a `prepare()` call.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the string token.
	 * @return bool
	 */
	private function is_within_prepare_call( File $phpcs_file, int $stack_ptr ): bool {
		$tokens = $phpcs_file->getTokens();

		if ( empty( $tokens[ $stack_ptr ]['nested_parenthesis'] ) ) {
			return false;
		}

		foreach ( array_keys( $tokens[ $stack_ptr ]['nested_parenthesis'] ) as $open_paren ) {
			$before = $phpcs_file->findPrevious( Tokens::$emptyTokens, $open_paren - 1, null, true );

			if ( false !== $before
				&& T_STRING === $tokens[ $before ]['code']
				&& 'prepare' === strtolower( $tokens[ $before ]['content'] )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines whether the enclosing function guards `%i` usage with a
	 * `has_cap( 'identifier_placeholders' )` check.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the string token.
	 * @return bool
	 */
	private function has_identifier_placeholder_guard( File $phpcs_file, int $stack_ptr ): bool {
		$tokens = $phpcs_file->getTokens();

		$start = 0;
		$end   = $phpcs_file->numTokens - 1;

		// Narrow the search to the innermost enclosing function/closure when there is one.
		if ( ! empty( $tokens[ $stack_ptr ]['conditions'] ) ) {
			foreach ( array_reverse( $tokens[ $stack_ptr ]['conditions'], true ) as $ptr => $code ) {
				if ( ( T_FUNCTION === $code || T_CLOSURE === $code )
					&& isset( $tokens[ $ptr ]['scope_opener'], $tokens[ $ptr ]['scope_closer'] )
				) {
					$start = $tokens[ $ptr ]['scope_opener'];
					$end   = $tokens[ $ptr ]['scope_closer'];
					break;
				}
			}
		}

		for ( $i = $start; $i <= $end; $i++ ) {
			if ( T_STRING !== $tokens[ $i ]['code'] || 'has_cap' !== strtolower( $tokens[ $i ]['content'] ) ) {
				continue;
			}

			$open_paren = $phpcs_file->findNext( Tokens::$emptyTokens, $i + 1, null, true );
			if ( false === $open_paren
				|| T_OPEN_PARENTHESIS !== $tokens[ $open_paren ]['code']
				|| empty( $tokens[ $open_paren ]['parenthesis_closer'] )
			) {
				continue;
			}

			$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];
			for ( $j = $open_paren + 1; $j < $close_paren; $j++ ) {
				if ( ( T_CONSTANT_ENCAPSED_STRING === $tokens[ $j ]['code'] || T_DOUBLE_QUOTED_STRING === $tokens[ $j ]['code'] )
					&& false !== strpos( $tokens[ $j ]['content'], self::GUARD_CAPABILITY )
				) {
					return true;
				}
			}
		}

		return false;
	}
}
