<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Channel rules fetched from a published rules file, so a channel's spec
 * change can reach stores without a WooCommerce release.
 *
 * The file is fetched before each background scan, at most once a day. Only
 * a GET of the file is made; no store data is sent. A failed fetch keeps the
 * last good copy, and with no copy the bundled rules apply. No URL is set by
 * default until the file has a home; the woocommerce_agent_readiness_rules_url
 * filter sets one.
 *
 * @since 11.4.0
 */
class RemoteRules {

	/**
	 * Option holding the last good rules file.
	 */
	public const OPTION = 'woocommerce_agent_readiness_remote_rules';

	/**
	 * Fetch the rules file and store it when it is valid.
	 *
	 * @return bool Whether a new copy was stored.
	 */
	public function refresh(): bool {
		/**
		 * Filters the URL of the agent readiness rules file. Empty means the bundled rules only.
		 *
		 * @since 11.4.0
		 *
		 * @param string $url Rules file URL.
		 */
		$url = apply_filters( 'woocommerce_agent_readiness_rules_url', '' );
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'limit_response_size' => 256 * KB_IN_BYTES,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}
		$rules = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $rules ) || ! isset( $rules['checks'] ) || ! is_array( $rules['checks'] ) ) {
			return false;
		}

		$checks = array();
		foreach ( $rules['checks'] as $id => $check ) {
			if ( is_string( $id ) && is_array( $check ) && isset( $check['channels'] ) && is_array( $check['channels'] ) ) {
				$checks[ $id ] = $check['channels'];
			}
		}
		update_option(
			self::OPTION,
			array(
				'version' => (string) ( $rules['version'] ?? '' ),
				'checks'  => $checks,
			),
			false
		);
		return true;
	}

	/**
	 * Channel maps by check id from the stored file, with each effect_from
	 * date already applied for the given day. Empty when nothing is stored.
	 *
	 * @param int|null $now Unix time to resolve dates for; defaults to now.
	 * @return array<string, array<string, array{field:string,effect:string}>>
	 */
	public function channel_rules( ?int $now = null ): array {
		$stored = get_option( self::OPTION );
		if ( ! is_array( $stored ) || ! isset( $stored['checks'] ) || ! is_array( $stored['checks'] ) ) {
			return array();
		}

		$today = gmdate( 'Y-m-d', $now ?? time() );
		$rules = array();
		foreach ( $stored['checks'] as $id => $channels ) {
			if ( ! is_array( $channels ) ) {
				continue;
			}
			foreach ( $channels as $channel => $spec ) {
				if ( ! is_array( $spec ) ) {
					continue;
				}
				$effect = $spec['effect'] ?? null;
				$dates  = isset( $spec['effect_from'] ) && is_array( $spec['effect_from'] ) ? $spec['effect_from'] : array();
				ksort( $dates );
				foreach ( $dates as $from => $later ) {
					if ( $today >= (string) $from ) {
						$effect = $later;
					}
				}
				$rules[ $id ][ $channel ] = array(
					'field'  => (string) ( $spec['field'] ?? '' ),
					'effect' => $effect,
				);
			}
		}
		return $rules;
	}

	/**
	 * Version of the stored rules file, or '' when none is stored.
	 */
	public function version(): string {
		$stored = get_option( self::OPTION );
		return is_array( $stored ) ? (string) ( $stored['version'] ?? '' ) : '';
	}
}
