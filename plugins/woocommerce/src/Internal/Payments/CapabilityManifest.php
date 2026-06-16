<?php
/**
 * CapabilityManifest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

/**
 * Provider capability manifest for the native payments runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class CapabilityManifest {

	const CAPABILITY_CARDS             = 'cards';
	const CAPABILITY_SAVED_TOKENS      = 'saved_tokens';
	const CAPABILITY_OFF_SESSION       = 'off_session';
	const CAPABILITY_MANDATES          = 'mandates';
	const CAPABILITY_ASYNC_REDIRECT    = 'async_redirect';
	const CAPABILITY_REFUNDS           = 'refunds';
	const CAPABILITY_PARTIAL_REFUNDS   = 'partial_refunds';
	const CAPABILITY_DISPUTES          = 'disputes';
	const CAPABILITY_MANUAL_CAPTURE    = 'manual_capture';
	const CAPABILITY_SUBSCRIPTIONS     = 'subscriptions';
	const CAPABILITY_EXPRESS_CHECKOUT  = 'express_checkout';
	const CAPABILITY_HOSTED_KYC        = 'hosted_kyc';
	const CAPABILITY_HOSTED_SESSION    = 'hosted_session';
	const CAPABILITY_REPORTING         = 'reporting';
	const CAPABILITY_MULTI_CURRENCY    = 'multi_currency';
	const CAPABILITY_IN_PERSON         = 'in_person';
	const CAPABILITY_ZERO_AMOUNT_SETUP = 'zero_amount_setup';

	/**
	 * Capability support map.
	 *
	 * @var array<string,bool>
	 */
	private array $capabilities;

	/**
	 * Constructor.
	 *
	 * @param array<string,bool> $capabilities Capability support map.
	 */
	public function __construct( array $capabilities = array() ) {
		$this->capabilities = array_merge(
			array_fill_keys( self::get_known_capabilities(), false ),
			array_intersect_key( $capabilities, array_fill_keys( self::get_known_capabilities(), true ) )
		);
	}

	/**
	 * Create a manifest from either a list of enabled capabilities or a support map.
	 *
	 * @param array<int|string,bool|string> $capabilities Capabilities.
	 * @return self
	 */
	public static function from_array( array $capabilities ): self {
		$normalized = array();

		foreach ( $capabilities as $key => $value ) {
			if ( is_int( $key ) ) {
				$capability = (string) $value;
				$enabled    = true;
			} else {
				$capability = (string) $key;
				$enabled    = (bool) $value;
			}

			if ( in_array( $capability, self::get_known_capabilities(), true ) ) {
				$normalized[ $capability ] = $enabled;
			}
		}

		return new self( $normalized );
	}

	/**
	 * Get the capabilities known to the internal runtime.
	 *
	 * @return string[]
	 */
	public static function get_known_capabilities(): array {
		return array(
			self::CAPABILITY_CARDS,
			self::CAPABILITY_SAVED_TOKENS,
			self::CAPABILITY_OFF_SESSION,
			self::CAPABILITY_MANDATES,
			self::CAPABILITY_ASYNC_REDIRECT,
			self::CAPABILITY_REFUNDS,
			self::CAPABILITY_PARTIAL_REFUNDS,
			self::CAPABILITY_DISPUTES,
			self::CAPABILITY_MANUAL_CAPTURE,
			self::CAPABILITY_SUBSCRIPTIONS,
			self::CAPABILITY_EXPRESS_CHECKOUT,
			self::CAPABILITY_HOSTED_KYC,
			self::CAPABILITY_HOSTED_SESSION,
			self::CAPABILITY_REPORTING,
			self::CAPABILITY_MULTI_CURRENCY,
			self::CAPABILITY_IN_PERSON,
			self::CAPABILITY_ZERO_AMOUNT_SETUP,
		);
	}

	/**
	 * Tell whether a capability is supported.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public function supports( string $capability ): bool {
		return $this->capabilities[ $capability ] ?? false;
	}

	/**
	 * Get all known capability support flags.
	 *
	 * @return array<string,bool>
	 */
	public function all(): array {
		return $this->capabilities;
	}
}
