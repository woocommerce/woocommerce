<?php
/**
 * Forbids reading the Store API cart token from anywhere but CartTokenUtils.
 *
 * @package WooCommerce\Sniffs
 */

declare( strict_types=1 );

namespace WooCommerceStoreApi\Sniffs\StoreApi;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * The cart token selects which customer session a request loads. It must always be read from a unified palce the via CartTokenUtils::get_request_cart_token().
 *
 */
class CartTokenSourceSniff implements Sniff {

	/**
	 * Superglobal key holding the raw cart token.
	 */
	private const SERVER_KEY = 'HTTP_CART_TOKEN';

	/**
	 * Request header holding the raw cart token.
	 */
	private const HEADER_NAME = 'cart-token';

	/**
	 * Returns the token types this sniff is interested in.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_VARIABLE, T_STRING );
	}

	/**
	 * Processes this test when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the stack.
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( T_VARIABLE === $tokens[ $stackPtr ]['code'] ) {
			$this->process_superglobal( $phpcsFile, $stackPtr );
			return;
		}

		$this->process_request_header( $phpcsFile, $stackPtr );
	}

	/**
	 * Flags `$_SERVER['HTTP_CART_TOKEN']`.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  Position of the variable token.
	 * @return void
	 */
	private function process_superglobal( File $phpcsFile, int $stackPtr ): void {
		$tokens = $phpcsFile->getTokens();

		if ( '$_SERVER' !== $tokens[ $stackPtr ]['content'] ) {
			return;
		}

		$open = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		if ( false === $open || T_OPEN_SQUARE_BRACKET !== $tokens[ $open ]['code'] ) {
			return;
		}

		$key = $phpcsFile->findNext( T_WHITESPACE, ( $open + 1 ), null, true );
		if ( false === $key || T_CONSTANT_ENCAPSED_STRING !== $tokens[ $key ]['code'] ) {
			return;
		}

		if ( self::SERVER_KEY !== strtoupper( trim( $tokens[ $key ]['content'], "'\"" ) ) ) {
			return;
		}

		if ( $this->is_assignment_target( $phpcsFile, $open ) ) {
			$phpcsFile->addError(
				'Assigning to $_SERVER[\'%s\'] changes which customer session loads for the rest of the request. Only do this with an already validated token, and justify it with a phpcs:ignore.',
				$stackPtr,
				'ServerSuperglobalWrite',
				array( self::SERVER_KEY )
			);
			return;
		}

		$phpcsFile->addError(
			'Do not read $_SERVER[\'%s\'] directly. Use CartTokenUtils::get_request_cart_token() so the cart token is read from a single place.',
			$stackPtr,
			'ServerSuperglobal',
			array( self::SERVER_KEY )
		);
	}

	/**
	 * Whether the array access starting at $open is being written to.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $open      Position of the opening square bracket.
	 * @return bool
	 */
	private function is_assignment_target( File $phpcsFile, int $open ): bool {
		$tokens = $phpcsFile->getTokens();
		$closer = $tokens[ $open ]['bracket_closer'] ?? null;

		if ( null === $closer ) {
			return false;
		}

		$next = $phpcsFile->findNext( T_WHITESPACE, ( $closer + 1 ), null, true );

		return false !== $next && isset( \PHP_CodeSniffer\Util\Tokens::$assignmentTokens[ $tokens[ $next ]['code'] ] );
	}

	/**
	 * Flags `$request->get_header( 'Cart-Token' )`.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  Position of the string token.
	 * @return void
	 */
	private function process_request_header( File $phpcsFile, int $stackPtr ): void {
		$tokens = $phpcsFile->getTokens();

		if ( 'get_header' !== strtolower( $tokens[ $stackPtr ]['content'] ) ) {
			return;
		}

		$before = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );
		if ( false === $before || T_OBJECT_OPERATOR !== $tokens[ $before ]['code'] ) {
			return;
		}

		$open = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		if ( false === $open || T_OPEN_PARENTHESIS !== $tokens[ $open ]['code'] ) {
			return;
		}

		$argument = $phpcsFile->findNext( T_WHITESPACE, ( $open + 1 ), null, true );
		if ( false === $argument || T_CONSTANT_ENCAPSED_STRING !== $tokens[ $argument ]['code'] ) {
			return;
		}

		if ( self::HEADER_NAME !== strtolower( trim( $tokens[ $argument ]['content'], "'\"" ) ) ) {
			return;
		}

		$phpcsFile->addError(
			'Do not read the Cart-Token header off a WP_REST_Request; under /batch that is the sub-request. Use CartTokenUtils::get_request_cart_token().',
			$stackPtr,
			'RequestHeader'
		);
	}
}
