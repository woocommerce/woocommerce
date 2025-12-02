<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * FraudProtectionOtpSchema class.
 *
 * Provides schema for fraud protection OTP challenge responses.
 *
 * @since 10.4.0
 */
class FraudProtectionOtpSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'fraud_protection_otp';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-otp';

	/**
	 * Get the item's schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'success'             => [
				'description' => __( 'Whether the operation was successful.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'challenge_id'        => [
				'description' => __( 'Unique identifier for the OTP challenge.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'expires_in'          => [
				'description' => __( 'Time in seconds until the OTP expires.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'attempts_remaining'  => [
				'description' => __( 'Number of verification attempts remaining.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'cooldown_seconds'    => [
				'description' => __( 'Seconds to wait before resending OTP.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'session_status'      => [
				'description' => __( 'Current session clearance status.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'enum'        => [ 'pending', 'allowed', 'blocked' ],
				'readonly'    => true,
			],
			'message'             => [
				'description' => __( 'User-friendly message about the operation result.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
		];
	}

	/**
	 * Get the schema for the OTP response.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->title,
			'type'       => 'object',
			'properties' => $this->get_properties(),
		];
	}
}
