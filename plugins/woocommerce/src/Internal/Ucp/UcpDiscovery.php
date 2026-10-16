<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Ucp;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * Serves the UCP discovery profile at `/.well-known/ucp`.
 *
 * @internal Just for internal use.
 */
final class UcpDiscovery implements RegisterHooksInterface {

	/**
	 * Query var set by the `/.well-known/ucp` rewrite rule.
	 */
	public const QUERY_VAR = 'wc_ucp_discovery';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_flush_rewrite_rules' ), 10, 1 );
		add_action( 'init', array( $this, 'register_feature_hooks' ), 0, 0 );
	}

	/**
	 * Register the rewrite rule and renderer when UCP is enabled and pretty permalinks are on.
	 */
	public function register_feature_hooks(): void {
		global $wp_rewrite;

		if ( ! UcpUtils::is_enabled() || ! $wp_rewrite->using_permalinks() ) {
			return;
		}

		add_rewrite_rule( '^\.well-known/ucp/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
		add_filter( 'query_vars', array( $this, 'add_query_var' ), 0 );
		add_action( 'parse_request', array( $this, 'maybe_render' ), 0 );
	}

	/**
	 * Register the discovery query var.
	 *
	 * @param array $vars Public query vars.
	 * @return array
	 */
	public function add_query_var( $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Queue a rewrite rules flush when the UCP feature is toggled.
	 *
	 * @param string $feature_id Feature being toggled.
	 */
	public function maybe_flush_rewrite_rules( string $feature_id ): void {
		if ( UcpUtils::FEATURE_ID === $feature_id ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		}
	}

	/**
	 * Send the discovery profile for `/.well-known/ucp` requests.
	 *
	 * @param \WP $wp Current WordPress environment instance.
	 */
	public function maybe_render( \WP $wp ): void {
		if ( '1' !== ( $wp->query_vars[ self::QUERY_VAR ] ?? '' ) ) {
			return;
		}

		// UCP requires a publicly cacheable profile with a max-age of at least 60 seconds.
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=300' );
		}

		wp_send_json( $this->get_profile() );
	}

	/**
	 * Build the discovery profile.
	 *
	 * @return array
	 */
	public function get_profile(): array {
		return array(
			'ucp'   => array(
				'version'      => UcpUtils::VERSION,
				'services'     => array(
					'dev.ucp.shopping' => array(
						array(
							'version'   => UcpUtils::VERSION,
							'transport' => 'rest',
							'endpoint'  => rest_url( 'wc/ucp/v1' ),
							'spec'      => 'https://ucp.dev/' . UcpUtils::VERSION . '/specification/overview',
							'schema'    => 'https://ucp.dev/' . UcpUtils::VERSION . '/services/shopping/rest.openapi.json',
						),
					),
				),
				'capabilities' => array_map(
					static function ( array $declaration ): array {
						return array( $declaration );
					},
					UcpUtils::capability_declarations()
				),
			),
			'links' => array(
				array(
					'rel'  => 'self',
					'href' => home_url( '/.well-known/ucp' ),
				),
			),
		);
	}
}
