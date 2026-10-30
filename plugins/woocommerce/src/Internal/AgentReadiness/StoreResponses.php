<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the store's own pages and files as a visitor who isn't logged in gets
 * them, for checks that read what the store returns rather than its settings.
 * Built once per scan; each URL is requested once and kept for the rest of it.
 *
 * Only URLs on the store's host are requested, and redirects are followed only
 * on that host. A response is null when the store could not be reached or is in
 * coming soon mode, so a check reports "couldn't check" instead of "missing".
 *
 * @since 11.4.0
 */
class StoreResponses {

	public const TIMEOUT = 5;

	public const MAX_BYTES = 512 * KB_IN_BYTES;

	private const MAX_REDIRECTS = 2;

	/**
	 * Whether visitors get the real store, not a coming soon page.
	 *
	 * @var bool
	 */
	private bool $site_live;

	/**
	 * Responses by URL.
	 *
	 * @var array<string, array{status:int,body:string}|null>
	 */
	private array $responses = array();

	/**
	 * Constructor.
	 *
	 * @param bool $site_live Whether visitors get the real store, not a coming soon page.
	 */
	public function __construct( bool $site_live ) {
		$this->site_live = $site_live;
	}

	/**
	 * The response for a URL on the store's host, or for a path from the home URL such as /llms.txt.
	 *
	 * @param string $url URL or path.
	 * @return array{status:int,body:string}|null Null when the store could not be reached.
	 */
	public function get( string $url ): ?array {
		if ( str_starts_with( $url, '/' ) ) {
			$url = home_url( $url );
		}
		if ( ! array_key_exists( $url, $this->responses ) ) {
			$this->responses[ $url ] = $this->site_live ? $this->fetch( $url ) : null;
		}
		return $this->responses[ $url ];
	}

	/**
	 * Request a URL, following redirects that stay on the store's host.
	 *
	 * @param string $url URL.
	 * @return array{status:int,body:string}|null
	 */
	private function fetch( string $url ): ?array {
		$store = self::origin( home_url() );
		for ( $redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++ ) {
			if ( null === $store || self::origin( $url ) !== $store ) {
				return null;
			}
			$response = wp_remote_get(
				$url,
				array(
					'timeout'             => self::TIMEOUT,
					'redirection'         => 0,
					'limit_response_size' => self::MAX_BYTES,
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's hook for requests a site makes to itself.
					'sslverify'           => (bool) apply_filters( 'https_local_ssl_verify', false ),
				)
			);
			if ( is_wp_error( $response ) ) {
				return null;
			}
			$status   = (int) wp_remote_retrieve_response_code( $response );
			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( $status < 300 || $status >= 400 || ! is_string( $location ) || '' === $location ) {
				return array(
					'status' => $status,
					'body'   => wp_remote_retrieve_body( $response ),
				);
			}
			$url = \WP_Http::make_absolute_url( $location, $url );
		}
		return null;
	}

	/**
	 * Host and port of an http(s) URL, or null for any other scheme. The port is
	 * only the one written in the URL, so moving from http to https on the
	 * default ports stays on the store, while another port on the same host does not.
	 *
	 * @param string $url URL.
	 */
	private static function origin( string $url ): ?string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || empty( $parts['host'] ) ) {
			return null;
		}
		return strtolower( $parts['host'] ) . ':' . ( $parts['port'] ?? '' );
	}
}
