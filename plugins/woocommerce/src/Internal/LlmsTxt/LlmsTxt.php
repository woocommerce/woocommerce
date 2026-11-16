<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\LlmsTxt;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

/**
 * Provides store guidance when no other provider serves llms.txt.
 *
 * @since 11.4.0
 */
final class LlmsTxt implements RegisterHooksInterface {

	/**
	 * The template selected before other template filters run.
	 *
	 * @var string|null
	 */
	private $original_template;

	/**
	 * Register the fallback after other providers have handled the request.
	 *
	 * @since 11.4.0
	 */
	public function register(): void {
		add_filter( 'redirect_canonical', array( $this, 'filter_canonical_redirect' ) );
		add_filter( 'exit_on_http_head', array( $this, 'filter_exit_on_http_head' ) );
		add_filter( 'template_include', array( $this, 'remember_template' ), PHP_INT_MIN );
		add_filter( 'template_include', array( $this, 'maybe_render' ), PHP_INT_MAX );
	}

	/**
	 * Keep WordPress from redirecting the missing file to a trailing-slash URL.
	 *
	 * @internal
	 *
	 * @param string|false $redirect_url Canonical redirect URL.
	 * @return string|false
	 */
	public function filter_canonical_redirect( $redirect_url ) {
		return $this->should_render() ? false : $redirect_url;
	}

	/**
	 * Let HEAD requests reach template selection for the fallback.
	 *
	 * @internal
	 * @param bool $should_exit Whether WordPress should exit early.
	 * @return bool
	 */
	public function filter_exit_on_http_head( $should_exit ) {
		return $this->should_render() ? false : $should_exit;
	}

	/**
	 * Report local file checks for llms.txt after init.
	 *
	 * Status is disabled, physical_file, or unknown. Check the public URL to
	 * determine which provider actually responds.
	 *
	 * @since 11.4.0
	 *
	 * @return array{status:string,section:string}
	 */
	public function get_status(): array {
		if ( ! $this->is_enabled() ) {
			return array(
				'status'  => 'disabled',
				'section' => '',
			);
		}

		$status            = 'unknown';
		$has_physical_file = $this->has_physical_file();
		if ( true === $has_physical_file ) {
			$status = 'physical_file';
		}

		return array(
			'status'  => $status,
			'section' => $this->get_guidance(),
		);
	}

	/**
	 * Remember the template selected before other filters run.
	 *
	 * @internal
	 * @param string $template Selected template.
	 * @return string
	 */
	public function remember_template( $template ) {
		$this->original_template = $template;
		return $template;
	}

	/**
	 * Serve an unclaimed request after other providers have had a chance to respond.
	 *
	 * @internal
	 * @param string $template Selected template.
	 * @return string
	 */
	public function maybe_render( $template ) {
		if ( '' === $template || $template !== $this->original_template || ! $this->should_render() ) {
			return $template;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$method = $_SERVER['REQUEST_METHOD'] ?? '';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			if ( ! headers_sent() ) {
				header( 'Allow: GET, HEAD' );
			}
			status_header( 405 );
			exit;
		}

		status_header( 200 );
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'X-WooCommerce-LLMs-Txt: 1' );
			if ( ! get_option( 'blog_public' ) ) {
				header( 'X-Robots-Tag: noindex, nofollow', false );
			}
		}
		if ( 'GET' === $method ) {
			echo $this->get_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML.
		}
		exit;
	}

	/**
	 * Check whether WooCommerce may serve this request as a fallback.
	 */
	private function should_render(): bool {
		return $this->is_llms_request()
			&& $this->is_enabled()
			&& ! wc_get_container()->get( ComingSoonHelper::class )->is_store_coming_soon()
			&& $this->is_unclaimed_request()
			&& true !== $this->has_physical_file()
			&& ! $this->has_route();
	}

	/**
	 * Check the request path against this site's llms.txt URL.
	 */
	private function is_llms_request(): bool {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		return wp_parse_url( home_url( '/llms.txt' ), PHP_URL_PATH ) === $request_path;
	}

	/**
	 * Plain permalinks or a stale rewrite rule can resolve the path as the front page.
	 */
	private function is_unclaimed_request(): bool {
		global $wp;

		return is_404() || ( isset( $wp ) && is_front_page() && empty( $wp->query_vars ) );
	}

	/**
	 * Check custom rewrites and existing content without treating catch-all permalinks as routes.
	 */
	private function has_route(): bool {
		global $wp_rewrite;

		if ( isset( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) {
			$rules = $wp_rewrite->extra_rules_top + $wp_rewrite->extra_rules + $wp_rewrite->non_wp_rules;
			foreach ( array_keys( $rules ) as $rule ) {
				if ( preg_match( '#^' . $rule . '#', 'llms.txt' ) ) {
					return true;
				}
			}
		}

		return 0 !== url_to_postid( home_url( '/llms.txt' ) );
	}

	/**
	 * Whether WooCommerce may provide guidance and a fallback response.
	 */
	private function is_enabled(): bool {
		/**
		 * Controls WooCommerce's llms.txt guidance and fallback response.
		 *
		 * @since 11.4.0
		 *
		 * @param bool $enabled Whether the feature is enabled.
		 */
		return (bool) apply_filters( 'woocommerce_enable_llms_txt', true );
	}

	/**
	 * Check the site root for a physical file without changing it.
	 *
	 * @return bool|null Null when the site root cannot be determined reliably.
	 */
	private function has_physical_file(): ?bool {
		if ( is_multisite() && trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' ) !== '' ) {
			return null;
		}
		if ( untrailingslashit( home_url() ) !== untrailingslashit( site_url() ) ) {
			return null;
		}
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return is_file( trailingslashit( get_home_path() ) . 'llms.txt' );
	}

	/**
	 * Build the complete fallback document.
	 */
	private function get_content(): string {
		$name    = $this->plain_text( get_bloginfo( 'name' ) );
		$tagline = $this->plain_text( get_bloginfo( 'description' ) );
		$content = '# ' . ( '' !== $name ? $name : home_url() ) . "\n\n";

		if ( '' !== $tagline ) {
			$content .= '> ' . $tagline . "\n\n";
		}

		return $content . $this->get_guidance();
	}

	/**
	 * Build the guidance used by the fallback and readiness check.
	 */
	private function get_guidance(): string {
		global $wp_rewrite;

		$profile      = array();
		$capabilities = array();

		if ( UcpUtils::is_enabled() && isset( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) {
			$profile      = wc_get_container()->get( UcpDiscovery::class )->get_profile();
			$capabilities = array_keys( $profile['ucp']['capabilities'] ?? array() );
			$labels       = array(
				'dev.ucp.shopping.catalog.search' => 'search products',
				'dev.ucp.shopping.catalog.lookup' => 'look up products',
				'dev.ucp.shopping.cart'           => 'build a cart',
			);
			$capabilities = array_map(
				static function ( $capability ) use ( $labels ) {
					return $labels[ $capability ] ?? $capability;
				},
				$capabilities
			);
		}

		$routes      = rest_get_server()->get_routes();
		$policies    = array(
			'terms'   => (int) get_option( 'woocommerce_terms_page_id' ),
			'returns' => (int) get_option( 'woocommerce_refund_returns_page_id' ),
			'privacy' => (int) get_option( 'wp_page_for_privacy_policy' ),
		);
		$policy_urls = array();
		foreach ( $policies as $key => $page_id ) {
			if ( $page_id <= 0 ) {
				continue;
			}
			$page = get_post( $page_id );
			if ( $page instanceof \WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status && is_post_publicly_viewable( $page ) && ! $page->post_password ) {
				$policy_urls[ $key ] = get_permalink( $page );
			}
		}

		$lines                  = array( '## Agentic capabilities', '' );
		$store_api_capabilities = array();
		if ( isset( $routes['/wc/store/v1/products'] ) ) {
			$store_api_capabilities[] = 'browse products';
		}
		if ( isset( $routes['/wc/store/v1/cart'] ) ) {
			$store_api_capabilities[] = 'use the cart';
		}
		if ( $store_api_capabilities ) {
			$lines[] = '- [WooCommerce Store API](' . esc_url_raw( rest_url( 'wc/store/v1' ) ) . '): ' . ucfirst( implode( ' and ', $store_api_capabilities ) ) . '. See the [Store API documentation](https://developer.woocommerce.com/docs/apis/store-api/).';
		}
		if ( $profile ) {
			$version       = $this->plain_text( $profile['ucp']['version'] ?? '' );
			$checkout_note = isset( $profile['ucp']['capabilities']['dev.ucp.shopping.checkout'] ) ? '' : ' Checkout is completed on the store, not through UCP.';
			$lines[]       = '- [Universal Commerce Protocol (UCP) profile](' . esc_url_raw( home_url( '/.well-known/ucp' ) ) . '): ' . implode( ', ', $capabilities ) . '. Version ' . $version . '.' . $checkout_note;
		}
		if ( isset( $routes['/mcp/mcp-adapter-default-server'] ) ) {
			$lines[] = '- [Merchant MCP](' . esc_url_raw( rest_url( 'mcp/mcp-adapter-default-server' ) ) . '): For authorized store management; authentication is required.';
		}
		if ( $policy_urls ) {
			$policy_labels = array(
				'terms'   => 'Terms and conditions',
				'returns' => 'Refund and returns',
				'privacy' => 'Privacy policy',
			);
			$lines[]       = '';
			$lines[]       = '## Policies';
			$lines[]       = '';
			foreach ( $policy_urls as $key => $url ) {
				$lines[] = '- [' . $policy_labels[ $key ] . '](' . esc_url_raw( $url ) . ')';
			}
		}
		$section = implode( "\n", $lines ) . "\n";
		/**
		 * Filters the Markdown guidance in WooCommerce's llms.txt.
		 *
		 * @since 11.4.0
		 *
		 * @param string $section The Markdown guidance.
		 * @param array  $profile The UCP profile, or an empty array when unavailable.
		 */
		$section = apply_filters( 'woocommerce_llms_txt_agentic_section', $section, $profile );
		return is_string( $section ) ? $section : '';
	}

	/**
	 * Make site text safe to include on one Markdown line.
	 *
	 * @param string $text Site text.
	 */
	private function plain_text( string $text ): string {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) ) ?? '' );
	}
}
