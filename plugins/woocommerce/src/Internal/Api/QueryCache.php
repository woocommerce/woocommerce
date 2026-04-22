<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;

/**
 * Caches parsed GraphQL ASTs in the WP object cache and implements the
 * Apollo Automatic Persisted Queries (APQ) protocol.
 */
class QueryCache {
	/**
	 * WP object-cache group.
	 */
	private const CACHE_GROUP = 'wc-graphql';

	/**
	 * Cache key prefix. Includes the library major version so that upgrading
	 * webonyx/graphql-php naturally invalidates stale entries.
	 *
	 * Update this constant when bumping the major version in composer.json.
	 */
	private const CACHE_KEY_PREFIX = 'graphql_ast_v15_';

	/**
	 * Time-to-live (in seconds) for a cached parsed query.
	 *
	 * See {@see self::get_cache_ttl()} for the accessor.
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * The time-to-live (in seconds) for a cached parsed query.
	 */
	public static function get_cache_ttl(): int {
		return self::CACHE_TTL;
	}

	/**
	 * Resolve a query string (and optional APQ extensions) into a DocumentNode.
	 *
	 * Returns a DocumentNode on success, or a GraphQL-shaped error array on failure.
	 *
	 * @param ?string $query      The GraphQL query string (may be null for APQ hash-only requests).
	 * @param array   $extensions The request extensions (may contain persistedQuery).
	 * @return DocumentNode|array
	 */
	public function resolve( ?string $query, array $extensions ) {
		$apq = $extensions['persistedQuery'] ?? null;

		if ( is_array( $apq ) && 1 === ( $apq['version'] ?? null ) && ! empty( $apq['sha256Hash'] ) ) {
			return $this->resolve_apq( $query, $apq['sha256Hash'] );
		}

		// Standard query — no APQ.
		if ( empty( $query ) ) {
			return $this->error_response( 'No query provided.', 'BAD_REQUEST' );
		}

		$hash = hash( 'sha256', $query );
		$doc  = $this->get_cached_document( $hash );
		if ( false !== $doc ) {
			return $doc;
		}

		return $this->parse_and_cache( $query, $hash );
	}

	/**
	 * Handle an APQ request (hash present in extensions).
	 *
	 * @param ?string $query    The query string, if provided.
	 * @param string  $apq_hash The sha256 hash from the persistedQuery extension.
	 * @return DocumentNode|array
	 */
	private function resolve_apq( ?string $query, string $apq_hash ) {
		if ( ! empty( $query ) ) {
			// Registration: query + hash provided.
			if ( hash( 'sha256', $query ) !== $apq_hash ) {
				return $this->error_response(
					'provided sha does not match query',
					'PERSISTED_QUERY_HASH_MISMATCH'
				);
			}

			$doc = $this->get_cached_document( $apq_hash );
			if ( false !== $doc ) {
				return $doc;
			}

			return $this->parse_and_cache( $query, $apq_hash );
		}

		// Hash-only lookup.
		$doc = $this->get_cached_document( $apq_hash );
		if ( false !== $doc ) {
			return $doc;
		}

		return $this->error_response( 'PersistedQueryNotFound', 'PERSISTED_QUERY_NOT_FOUND' );
	}

	/**
	 * Retrieve a cached DocumentNode by hash.
	 *
	 * @param string $hash The SHA-256 hash.
	 * @return DocumentNode|false
	 */
	private function get_cached_document( string $hash ) {
		$cached = wp_cache_get( $this->build_cache_key( $hash ), self::CACHE_GROUP );
		if ( false === $cached || ! is_array( $cached ) ) {
			return false;
		}

		return AST::fromArray( $cached );
	}

	/**
	 * Parse a query, cache the resulting AST, and return the DocumentNode.
	 *
	 * Returns an error array if the query has a syntax error.
	 *
	 * @param string $query The GraphQL query string.
	 * @param string $hash  The SHA-256 hash to cache under.
	 * @return DocumentNode|array
	 */
	private function parse_and_cache( string $query, string $hash ) {
		try {
			$document = Parser::parse( $query, array( 'noLocation' => true ) );
		} catch ( \Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError $e ) {
			return $this->error_response( 'GraphQL syntax error: ' . $e->getMessage(), 'GRAPHQL_PARSE_ERROR' );
		}

		wp_cache_set( $this->build_cache_key( $hash ), $document->toArray(), self::CACHE_GROUP, self::get_cache_ttl() );

		return $document;
	}

	/**
	 * Build a versioned cache key from a hash.
	 *
	 * @param string $hash The SHA-256 hash.
	 * @return string
	 */
	private function build_cache_key( string $hash ): string {
		return self::CACHE_KEY_PREFIX . $hash;
	}

	/**
	 * Build a GraphQL-shaped error response array.
	 *
	 * @param string $message The error message.
	 * @param string $code    The error code for extensions.
	 * @return array
	 */
	private function error_response( string $message, string $code ): array {
		return array(
			'errors' => array(
				array(
					'message'    => $message,
					'extensions' => array( 'code' => $code ),
				),
			),
		);
	}
}
