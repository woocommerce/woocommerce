<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Api\Infrastructure\Main;
use Automattic\WooCommerce\Api\Infrastructure\ResolverHelpers;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;

/**
 * Caches parsed GraphQL ASTs and implements the Apollo Automatic Persisted
 * Queries (APQ) protocol.
 *
 * Two backends are supported. OPcache (filesystem) is preferred: parsed ASTs
 * are written as PHP files so that OPcache serves them from shared memory.
 * The WP object cache is used as a fallback when OPcache isn't available or
 * the cache directory isn't writable.
 */
class QueryCache {
	/**
	 * WP object-cache group.
	 */
	public const CACHE_GROUP = 'wc-graphql';

	/**
	 * Cache key prefix. Includes the library major version so that upgrading
	 * webonyx/graphql-php naturally invalidates stale entries.
	 *
	 * Update this constant when bumping the major version in composer.json.
	 */
	private const CACHE_KEY_PREFIX = 'graphql_ast_v15_';

	/**
	 * Subdirectory (under wp-uploads) for the OPcache-backed file cache.
	 * The version segment matches {@see self::CACHE_KEY_PREFIX} so a major
	 * webonyx upgrade naturally orphans the previous version's files.
	 */
	private const OPCACHE_DIR_RELATIVE = 'wc-graphql-cache/v15';

	/**
	 * Cached result of {@see self::is_opcache_usable()} for the current request.
	 *
	 * @var ?bool
	 */
	private ?bool $opcache_usable = null;

	/**
	 * Default time-to-live (in seconds) applied when the option is unset or non-positive.
	 *
	 * See {@see self::get_cache_ttl()} for the accessor.
	 */
	public const DEFAULT_CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * The time-to-live (in seconds) for a cached parsed query.
	 *
	 * Reads the {@see Main::OPTION_QUERY_CACHE_TTL} store option; falls back
	 * to {@see self::DEFAULT_CACHE_TTL} when the option is unset, empty, or
	 * non-positive.
	 */
	public static function get_cache_ttl(): int {
		$value = (int) get_option( Main::OPTION_QUERY_CACHE_TTL, self::DEFAULT_CACHE_TTL );
		return $value > 0 ? $value : self::DEFAULT_CACHE_TTL;
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
		$apq      = $extensions['persistedQuery'] ?? null;
		$apq_hash = is_array( $apq ) ? ( $apq['sha256Hash'] ?? null ) : null;

		if ( Main::is_apq_enabled()
			&& is_array( $apq )
			&& 1 === ( $apq['version'] ?? null )
			&& is_string( $apq_hash )
			&& 1 === preg_match( '/^[a-f0-9]{64}$/', $apq_hash ) ) {
			return $this->resolve_apq( $query, $apq_hash );
		}

		// Standard query — no APQ.
		if ( empty( $query ) ) {
			return $this->error_response( 'No query provided.', 'BAD_REQUEST' );
		}

		// APQ keeps using the cache; it has its own settings toggle.
		if ( ! $this->is_caching_enabled() ) {
			return $this->parse( $query );
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

			$doc = $this->get_cached_document( $apq_hash, true );
			if ( false !== $doc ) {
				return $doc;
			}

			return $this->parse_and_cache( $query, $apq_hash, true );
		}

		// Hash-only lookup.
		$doc = $this->get_cached_document( $apq_hash, true );
		if ( false !== $doc ) {
			return $doc;
		}

		return $this->error_response( 'PersistedQueryNotFound', 'PERSISTED_QUERY_NOT_FOUND' );
	}

	/**
	 * Whether at least one cache backend is enabled (and, for OPcache, usable).
	 *
	 * Used to short-circuit the standard-query path when neither backend is
	 * available, so the request is parsed once with no cache lookup overhead.
	 */
	private function is_caching_enabled(): bool {
		return ( Main::is_opcache_enabled() && $this->is_opcache_usable() )
			|| Main::is_object_cache_enabled();
	}

	/**
	 * Retrieve a cached DocumentNode by hash.
	 *
	 * Tries OPcache first when enabled and usable, then falls back to the
	 * WP object cache. APQ requests pass $for_apq=true so the object cache
	 * is consulted regardless of the standard-query toggle, matching the
	 * pre-OPcache behaviour where APQ always persisted via the object cache.
	 *
	 * @param string $hash    The SHA-256 hash.
	 * @param bool   $for_apq Whether the lookup is for an APQ request.
	 * @return DocumentNode|false
	 */
	private function get_cached_document( string $hash, bool $for_apq = false ) {
		if ( Main::is_opcache_enabled() && $this->is_opcache_usable() ) {
			$doc = $this->read_from_opcache( $hash );
			if ( false !== $doc ) {
				return $doc;
			}
		}

		if ( $for_apq || Main::is_object_cache_enabled() ) {
			$cached = wp_cache_get( $this->build_cache_key( $hash ), self::CACHE_GROUP );
			if ( is_array( $cached ) ) {
				try {
					return AST::fromArray( $cached );
				} catch ( \Throwable $e ) {
					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Parse a query and return the DocumentNode, or a GraphQL-shaped error
	 * array if the query has a syntax error.
	 *
	 * @param string $query The GraphQL query string.
	 * @return DocumentNode|array
	 */
	private function parse( string $query ) {
		try {
			return Parser::parse( $query, array( 'noLocation' => true ) );
		} catch ( \Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError $e ) {
			return $this->error_response( 'GraphQL syntax error: ' . $e->getMessage(), 'GRAPHQL_PARSE_ERROR' );
		}
	}

	/**
	 * Parse a query, cache the resulting AST, and return the DocumentNode.
	 *
	 * Writes to OPcache when enabled and usable. APQ registrations always
	 * also write to the object cache so hash-only lookups still resolve if
	 * OPcache later becomes unavailable (toggle off, dir unwritable, files
	 * cleaned up, or a silent write_to_opcache failure).
	 *
	 * Returns an error array if the query has a syntax error.
	 *
	 * @param string $query   The GraphQL query string.
	 * @param string $hash    The SHA-256 hash to cache under.
	 * @param bool   $for_apq Whether the request is an APQ registration.
	 * @return DocumentNode|array
	 */
	private function parse_and_cache( string $query, string $hash, bool $for_apq = false ) {
		$document = $this->parse( $query );
		if ( ! $document instanceof DocumentNode ) {
			return $document;
		}

		$used_opcache = Main::is_opcache_enabled() && $this->is_opcache_usable();
		if ( $used_opcache ) {
			$this->write_to_opcache( $hash, $document );
		}

		if ( $for_apq || ( Main::is_object_cache_enabled() && ! $used_opcache ) ) {
			wp_cache_set( $this->build_cache_key( $hash ), $document->toArray(), self::CACHE_GROUP, self::get_cache_ttl() );
		}

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
	 * Whether the OPcache file backend can be used for this request.
	 *
	 * Memoised per request: the underlying checks (opcache_get_status,
	 * filesystem writability) don't change mid-request and are wasteful
	 * to repeat across the read and write paths.
	 */
	private function is_opcache_usable(): bool {
		if ( null !== $this->opcache_usable ) {
			return $this->opcache_usable;
		}

		$this->opcache_usable = $this->compute_is_opcache_usable();
		return $this->opcache_usable;
	}

	/**
	 * Underlying capability check for the OPcache file backend.
	 *
	 * Requires the OPcache extension to be loaded and enabled, and the cache
	 * directory to exist (or be creatable) and be writable.
	 */
	private function compute_is_opcache_usable(): bool {
		if ( ! function_exists( 'opcache_get_status' ) || ! ini_get( 'opcache.enable' ) ) {
			return false;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- opcache.restrict_api raises E_WARNING when the calling path is disallowed; the false return is handled below.
		$status = @opcache_get_status( false );
		if ( ! is_array( $status ) || empty( $status['opcache_enabled'] ) ) {
			return false;
		}

		return $this->ensure_opcache_dir_writable();
	}

	/**
	 * Resolve the directory where OPcache cache files are written.
	 *
	 * Defaults to a versioned subdirectory under wp-uploads so it inherits
	 * the writability guarantees WordPress places on uploads. Filterable
	 * for tests and unusual hosting layouts.
	 *
	 * @internal Public for {@see OpcacheFileExpiry}; not part of the plugin's external API.
	 */
	public static function get_opcache_cache_dir(): string {
		$upload_dir = wp_get_upload_dir();
		$default    = trailingslashit( $upload_dir['basedir'] ) . self::OPCACHE_DIR_RELATIVE;

		/**
		 * Filters the directory where parsed GraphQL ASTs are written for OPcache.
		 *
		 * @since 10.9.0
		 *
		 * @param string $dir Default cache directory under wp-uploads.
		 */
		$dir = (string) apply_filters( 'woocommerce_graphql_opcache_cache_dir', $default );

		// Reject stream wrappers (e.g. phar://, http://) to keep file_put_contents,
		// rename, and include constrained to local filesystem paths.
		if ( '' === $dir || wp_is_stream( $dir ) ) {
			return '';
		}

		return $dir;
	}

	/**
	 * Ensure the OPcache cache directory exists and is writable.
	 *
	 * Creates the directory on first use and drops a deny-all .htaccess and
	 * an empty index.html alongside it. Returns false if creation fails or
	 * the directory ends up non-writable.
	 */
	private function ensure_opcache_dir_writable(): bool {
		$dir = self::get_opcache_cache_dir();

		if ( '' === $dir ) {
			return false;
		}

		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$fs = ResolverHelpers::wp_filesystem();
		if ( ! $fs || ! $fs->is_writable( $dir ) ) {
			return false;
		}

		// Best-effort hardening; ignore failures (e.g. read-only permissions).
		$htaccess = $dir . '/.htaccess';
		$index    = $dir . '/index.html';
		if ( ! file_exists( $htaccess ) ) {
			$fs->put_contents( $htaccess, "Deny from all\n" );
		}
		if ( ! file_exists( $index ) ) {
			$fs->put_contents( $index, '' );
		}

		return true;
	}

	/**
	 * Read a cached DocumentNode from the OPcache file backend.
	 *
	 * @param string $hash The SHA-256 hash.
	 * @return DocumentNode|false
	 */
	private function read_from_opcache( string $hash ) {
		$path = self::get_opcache_cache_dir() . '/' . $hash . '.php';

		if ( ! is_file( $path ) ) {
			return false;
		}

		// File contents are produced by self::write_to_opcache() and only
		// ever return a primitive array. The caller falls back to parsing
		// when the include returns a non-array.
		$data = include $path;

		if ( ! is_array( $data ) ) {
			return false;
		}

		try {
			return AST::fromArray( $data );
		} catch ( \Throwable $e ) {
			return false;
		} finally {
			OpcacheFileExpiry::ensure_scheduled();
		}
	}

	/**
	 * Persist a parsed AST to the OPcache file backend.
	 *
	 * Writes atomically (temp file + rename) so concurrent readers never see
	 * a partial file, and explicitly invalidates OPcache for the destination
	 * path so installs running with opcache.validate_timestamps=0 still see
	 * the new version.
	 *
	 * Failures are intentionally silent: the caller already holds a valid
	 * DocumentNode, and a failed cache write only forfeits the optimisation
	 * for one request.
	 *
	 * @param string       $hash     The SHA-256 hash to cache under.
	 * @param DocumentNode $document The parsed AST.
	 */
	private function write_to_opcache( string $hash, DocumentNode $document ): void {
		$dir  = self::get_opcache_cache_dir();
		$path = $dir . '/' . $hash . '.php';
		$tmp  = $path . '.' . bin2hex( random_bytes( 8 ) ) . '.tmp';

		$contents = "<?php\nreturn " . var_export( $document->toArray(), true ) . ";\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $tmp, $contents, LOCK_EX ) ) {
			return;
		}

		$fs = ResolverHelpers::wp_filesystem();
		if ( ! $fs || ! $fs->move( $tmp, $path, true ) ) {
			if ( $fs ) {
				$fs->delete( $tmp );
			}
			return;
		}

		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $path, true );
		}
		if ( function_exists( 'opcache_compile_file' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@opcache_compile_file( $path );
		}

		OpcacheFileExpiry::ensure_scheduled();
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
