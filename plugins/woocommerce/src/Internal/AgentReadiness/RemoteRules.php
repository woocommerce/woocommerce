<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Channel rules fetched from a published rules file, so a channel's spec
 * change can reach stores without a WooCommerce release.
 *
 * The file is the list of checks that run. Each id has to match a check in
 * code; an id the file leaves out does not run, and an id code does not know
 * is ignored. A check whose entry has a malformed channel runs with its
 * bundled channels instead. A check no channel asks for, such as image alt
 * text, is named with an empty channel map ("channels": {}). A file is only
 * stored when at least one check in it has a valid channel.
 *
 * The file is fetched before each background scan: the daily run and any
 * scan that is asked for. Only a GET is made; the request's user agent
 * includes the site URL, as other WooCommerce.com polls do. A failed fetch
 * keeps the last good copy, and with no copy the bundled checks apply.
 *
 * The file is fetched from PUBLISHED_URL, the same host as the payment gateway
 * suggestions; the woocommerce_agent_readiness_rules_url filter changes it.
 *
 * @since 11.4.0
 */
class RemoteRules {

	/**
	 * Option holding the last good rules file.
	 */
	public const OPTION = 'woocommerce_agent_readiness_remote_rules';

	/**
	 * Default rules file URL. While it answers with an error, such as a 404, the bundled checks run.
	 */
	public const PUBLISHED_URL = 'https://woocommerce.com/wp-json/wccom/agent-readiness/1.0/rules.json';

	/**
	 * Fetch the rules file and store it when at least one check entry is valid
	 * (see is_valid_check()) and, when known ids are given, is one of them.
	 * Anything less keeps the last good copy.
	 *
	 * @param array<int, string>|null $known_ids Check ids the code can run; null accepts any id.
	 * @return bool Whether a new copy was stored.
	 */
	public function refresh( ?array $known_ids = null ): bool {
		/**
		 * Filters the URL of the agent readiness rules file. Empty means the bundled checks only.
		 *
		 * @since 11.4.0
		 *
		 * @param string $url Rules file URL.
		 */
		$url = apply_filters( 'woocommerce_agent_readiness_rules_url', self::PUBLISHED_URL );
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

		// A malformed entry is kept with no channels, so its check runs with the bundled ones rather than not at all.
		$checks = array();
		foreach ( $rules['checks'] as $id => $check ) {
			if ( is_string( $id ) ) {
				$checks[ $id ] = is_array( $check ) && isset( $check['channels'] ) && is_array( $check['channels'] ) ? $check['channels'] : array();
			}
		}
		// Nothing this code could run. Do not replace the last good copy with it.
		$usable = array_filter(
			$checks,
			static fn( array $channels, string $id ): bool => ( null === $known_ids || in_array( $id, $known_ids, true ) ) && self::is_valid_check( $channels ),
			ARRAY_FILTER_USE_BOTH
		);
		if ( array() === $usable ) {
			return false;
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
	 * Whether a check's channel map names at least one known channel, and every
	 * known channel has a string field, a valid effect and, when effect_from is
	 * present, only Y-m-d dates with valid effects. Unknown channels are ignored.
	 *
	 * @param array<mixed> $channels Channel map from the file.
	 */
	private static function is_valid_check( array $channels ): bool {
		$known = array_intersect_key( $channels, FeedChannels::all() );
		foreach ( $known as $spec ) {
			if ( ! is_array( $spec ) || ! is_string( $spec['field'] ?? null ) || ! self::is_effect( $spec['effect'] ?? null ) ) {
				return false;
			}
			$dates = array_key_exists( 'effect_from', $spec ) ? $spec['effect_from'] : array();
			if ( ! is_array( $dates ) || count( $dates ) !== count( self::valid_dates( $dates ) ) ) {
				return false;
			}
		}
		return array() !== $known;
	}

	/**
	 * Whether a value is an effect the code applies.
	 *
	 * @param mixed $effect Effect from the file.
	 */
	private static function is_effect( $effect ): bool {
		return in_array( $effect, array( ReadinessRule::EFFECT_NOT_LISTABLE, ReadinessRule::EFFECT_GAPS ), true );
	}

	/**
	 * The effect_from entries keyed by a Y-m-d date with a valid effect, in date order.
	 *
	 * @param array<mixed> $dates effect_from map from the file.
	 * @return array<string, string>
	 */
	private static function valid_dates( array $dates ): array {
		$valid = array();
		foreach ( $dates as $from => $effect ) {
			if ( 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) && self::is_effect( $effect ) ) {
				$valid[ (string) $from ] = $effect;
			}
		}
		ksort( $valid );
		return $valid;
	}

	/**
	 * Whether a rules file is stored. A stored file whose checks the code does
	 * not know is not used: the bundled checks run instead.
	 */
	public function has_stored_file(): bool {
		$stored = get_option( self::OPTION );
		return is_array( $stored ) && isset( $stored['checks'] ) && is_array( $stored['checks'] );
	}

	/**
	 * Channel maps by check id from the stored file, with each effect_from
	 * date already applied for the given day. A check whose entry is not
	 * valid (see is_valid_check()) maps to an empty array, so the bundled
	 * channels run for it. Empty when nothing is stored.
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
			$rules[ $id ] = array();
			if ( ! is_array( $channels ) || ! self::is_valid_check( $channels ) ) {
				continue;
			}
			foreach ( array_intersect_key( $channels, FeedChannels::all() ) as $channel => $spec ) {
				$effect = $spec['effect'];
				foreach ( self::valid_dates( $spec['effect_from'] ?? array() ) as $from => $later ) {
					if ( $today >= $from ) {
						$effect = $later;
					}
				}
				$rules[ $id ][ $channel ] = array(
					'field'  => $spec['field'],
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
