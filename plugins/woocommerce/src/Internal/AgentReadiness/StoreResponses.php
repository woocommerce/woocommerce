<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\Enums\ProductStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the store's own pages and files as a visitor who isn't logged in gets
 * them, for checks that read what the store returns rather than its settings.
 * Built once per scan; each URL is requested once and kept for the rest of it.
 *
 * Only URLs on the store's host are requested, and redirects are followed only
 * on that host. A response is null when the store could not be reached or is in
 * coming soon mode, so a check reports "couldn't check" instead of "missing".
 * When the home page can't be reached, the rest of the scan makes no more requests.
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
	 * False after the home page failed to connect or timed out.
	 *
	 * @var bool
	 */
	private bool $reachable = true;

	/**
	 * Responses by request.
	 *
	 * @var array<string, array{status:int,body:string,headers:array<string,string>}|null>
	 */
	private array $responses = array();

	/**
	 * The product whose page checks read, false when there is none, null until looked up.
	 *
	 * @var \WC_Product|false|null
	 */
	private $product = null;

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
	 * @return array{status:int,body:string,headers:array<string,string>}|null Null when the store could not be reached.
	 */
	public function get( string $url ): ?array {
		$url = self::absolute( $url );
		if ( ! array_key_exists( $url, $this->responses ) ) {
			$this->responses[ $url ] = $this->site_live ? $this->fetch( $url ) : null;
		}
		return $this->responses[ $url ];
	}

	/**
	 * The response to a JSON POST to a URL on the store's host, such as a UCP endpoint. Redirects are not followed.
	 *
	 * @param string               $url  URL or path.
	 * @param array<string, mixed> $body Request body.
	 * @return array{status:int,body:string,headers:array<string,string>}|null Null when the store could not be reached.
	 */
	public function post_json( string $url, array $body ): ?array {
		$url  = self::absolute( $url );
		$json = (string) wp_json_encode( $body );
		$key  = 'POST ' . $url . ' ' . $json;
		if ( ! array_key_exists( $key, $this->responses ) ) {
			$this->responses[ $key ] = $this->site_live && $this->is_store_url( $url ) ? $this->request(
				$url,
				array(
					'method'  => 'POST',
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => $json,
				)
			) : null;
		}
		return $this->responses[ $key ];
	}

	/**
	 * The newest published product shoppers can find, or null when there is none. Password protected
	 * products are skipped, since their page shows a form instead of the product; only the five newest are looked at.
	 */
	public function product(): ?\WC_Product {
		if ( null === $this->product ) {
			$this->product = false;
			$products      = wc_get_products(
				array(
					'status'     => ProductStatus::PUBLISH,
					'visibility' => CatalogVisibility::VISIBLE,
					'limit'      => 5,
					'orderby'    => 'date',
					'order'      => 'DESC',
				)
			);
			foreach ( is_array( $products ) ? $products : array() as $product ) {
				if ( $product instanceof \WC_Product && '' === $product->get_post_password() ) {
					$this->product = $product;
					break;
				}
			}
		}
		return $this->product ? $this->product : null;
	}

	/**
	 * The page of `product()`, or null when there is no product or its page didn't answer 200.
	 *
	 * @return array{status:int,body:string,headers:array<string,string>}|null
	 */
	public function product_page(): ?array {
		$product = $this->product();
		return null === $product ? null : self::ok( $this->get( (string) $product->get_permalink() ) );
	}

	/**
	 * The home page, or null when it didn't answer 200.
	 *
	 * @return array{status:int,body:string,headers:array<string,string>}|null
	 */
	public function home_page(): ?array {
		return self::ok( $this->get( '/' ) );
	}

	/**
	 * Whether a URL is on the store's host, so `get()` would request it.
	 *
	 * @param string $url URL.
	 */
	public function is_store_url( string $url ): bool {
		$store = self::origin( home_url() );
		return null !== $store && self::origin( $url ) === $store;
	}

	/**
	 * Request a URL, following redirects that stay on the store's host.
	 *
	 * @param string $url URL.
	 * @return array{status:int,body:string,headers:array<string,string>}|null
	 */
	private function fetch( string $url ): ?array {
		for ( $redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++ ) {
			if ( ! $this->is_store_url( $url ) ) {
				return null;
			}
			$response = $this->request( $url );
			if ( null === $response ) {
				return null;
			}
			$location = $response['headers']['location'] ?? '';
			if ( $response['status'] < 300 || $response['status'] >= 400 || '' === $location ) {
				return $response;
			}
			$url = \WP_Http::make_absolute_url( $location, $url );
		}
		return null;
	}

	/**
	 * One request, without following redirects. Only the headers checks read are kept.
	 *
	 * @param string               $url  URL.
	 * @param array<string, mixed> $args Extra request arguments.
	 * @return array{status:int,body:string,headers:array<string,string>}|null
	 */
	private function request( string $url, array $args = array() ): ?array {
		if ( ! $this->reachable ) {
			return null;
		}
		$response = wp_remote_request(
			$url,
			$args + array(
				'method'              => 'GET',
				'timeout'             => self::TIMEOUT,
				'redirection'         => 0,
				'limit_response_size' => self::MAX_BYTES,
				// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's hook for requests a site makes to itself.
				'sslverify'           => (bool) apply_filters( 'https_local_ssl_verify', false ),
			)
		);
		if ( is_wp_error( $response ) ) {
			// When the home page can't be reached, other URLs would fail the same way, so a scan
			// of a store that can't reach itself waits for one timeout, not one per URL.
			if ( untrailingslashit( $url ) === untrailingslashit( home_url( '/' ) ) ) {
				$this->reachable = false;
			}
			return null;
		}
		$headers = array();
		foreach ( array( 'location', 'x-robots-tag' ) as $name ) {
			$value = wp_remote_retrieve_header( $response, $name );
			$value = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
			if ( '' !== $value ) {
				$headers[ $name ] = $value;
			}
		}
		return array(
			'status'  => (int) wp_remote_retrieve_response_code( $response ),
			'body'    => wp_remote_retrieve_body( $response ),
			'headers' => $headers,
		);
	}

	/**
	 * A response that answered 200, else null.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $response Response.
	 * @return array{status:int,body:string,headers:array<string,string>}|null
	 */
	private static function ok( ?array $response ): ?array {
		return null !== $response && 200 === $response['status'] ? $response : null;
	}

	/**
	 * A path from the home URL as a URL; a URL as it is.
	 *
	 * @param string $url URL or path.
	 */
	private static function absolute( string $url ): string {
		return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
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
