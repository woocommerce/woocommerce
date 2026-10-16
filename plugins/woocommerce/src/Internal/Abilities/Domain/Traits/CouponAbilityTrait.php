<?php
/**
 * Coupon ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Shared coupon helpers for WooCommerce domain ability definitions.
 */
trait CouponAbilityTrait {

	/**
	 * Coupon post statuses supported by the coupon abilities.
	 *
	 * @return array<int, string>
	 */
	protected static function get_coupon_status_slugs(): array {
		return array( 'publish', 'draft', 'pending', 'private', 'future' );
	}

	/**
	 * Get the schema for a single coupon in a response.
	 *
	 * @return array
	 */
	protected static function get_coupon_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                   => array( 'type' => 'integer' ),
				'code'                 => array( 'type' => 'string' ),
				'status'               => array( 'type' => 'string' ),
				'discount_type'        => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_coupon_types() ),
				),
				'amount'               => array( 'type' => 'string' ),
				'description'          => array( 'type' => 'string' ),
				'date_expires'         => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_expires_gmt'     => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'usage_count'          => array( 'type' => 'integer' ),
				'usage_limit'          => array( 'type' => array( 'integer', 'null' ) ),
				'usage_limit_per_user' => array( 'type' => array( 'integer', 'null' ) ),
				'individual_use'       => array( 'type' => 'boolean' ),
				'free_shipping'        => array( 'type' => 'boolean' ),
				'minimum_amount'       => array( 'type' => 'string' ),
				'maximum_amount'       => array( 'type' => 'string' ),
				'email_restrictions'   => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'date_created'         => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_modified'        => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the writable coupon field schemas shared by create and update.
	 *
	 * @return array
	 */
	protected static function get_coupon_writable_properties_schema(): array {
		return array(
			'code'                 => array(
				'type'        => 'string',
				'minLength'   => 1,
				'description' => __( 'Coupon code. Must be unique.', 'woocommerce' ),
			),
			'status'               => array(
				'type' => 'string',
				'enum' => self::get_coupon_status_slugs(),
			),
			'discount_type'        => array(
				'type' => 'string',
				'enum' => array_keys( wc_get_coupon_types() ),
			),
			'amount'               => array(
				'type'        => 'string',
				'description' => __( 'Discount amount as a decimal string, without a currency symbol.', 'woocommerce' ),
				'pattern'     => '^[0-9]+(\.[0-9]+)?$',
			),
			'description'          => array( 'type' => 'string' ),
			'date_expires'         => array(
				'type'        => array( 'string', 'null' ),
				'description' => __( 'Expiry date in the store timezone (Y-m-d or ISO 8601). Null removes the expiry date.', 'woocommerce' ),
			),
			'usage_limit'          => array(
				'type'        => array( 'integer', 'null' ),
				'minimum'     => 0,
				'description' => __( 'How many times the coupon can be used in total. Null or 0 means unlimited.', 'woocommerce' ),
			),
			'usage_limit_per_user' => array(
				'type'        => array( 'integer', 'null' ),
				'minimum'     => 0,
				'description' => __( 'How many times each customer can use the coupon. Null or 0 means unlimited.', 'woocommerce' ),
			),
			'individual_use'       => array( 'type' => 'boolean' ),
			'free_shipping'        => array( 'type' => 'boolean' ),
			'minimum_amount'       => array(
				'type'    => 'string',
				'pattern' => '^([0-9]+(\.[0-9]+)?)?$',
			),
			'maximum_amount'       => array(
				'type'    => 'string',
				'pattern' => '^([0-9]+(\.[0-9]+)?)?$',
			),
			'email_restrictions'   => array(
				'type'        => 'array',
				'description' => __( 'Billing emails allowed to use the coupon. An empty array removes the restriction.', 'woocommerce' ),
				'items'       => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Get a coupon from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Coupon|\WP_Error
	 */
	protected static function get_coupon_from_input( array $input ) {
		$coupon_id = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $coupon_id < 1 ) {
			return new \WP_Error(
				'woocommerce_coupon_id_required',
				__( 'Coupon ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( 'shop_coupon' !== get_post_type( $coupon_id ) ) {
			return new \WP_Error(
				'woocommerce_coupon_not_found',
				__( 'Coupon not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return new \WC_Coupon( $coupon_id );
	}

	/**
	 * Check whether a coupon code is already used by another coupon.
	 *
	 * @param string $code      Coupon code.
	 * @param int    $coupon_id Coupon being saved, 0 for a new coupon.
	 * @return bool
	 */
	protected static function is_coupon_code_taken( string $code, int $coupon_id = 0 ): bool {
		$existing_id = wc_get_coupon_id_by_code( $code, $coupon_id );

		return $existing_id > 0 && $existing_id !== $coupon_id;
	}

	/**
	 * Apply writable fields from ability input to a coupon.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @param array      $input  Ability input.
	 * @return true|\WP_Error
	 */
	protected static function set_coupon_props_from_input( \WC_Coupon $coupon, array $input ) {
		if ( isset( $input['code'] ) ) {
			$code = wc_format_coupon_code( (string) $input['code'] );

			if ( '' === $code ) {
				return new \WP_Error(
					'woocommerce_coupon_code_required',
					__( 'Coupon code is required.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			if ( self::is_coupon_code_taken( $code, $coupon->get_id() ) ) {
				return new \WP_Error(
					'woocommerce_coupon_code_exists',
					__( 'A coupon with this code already exists.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$coupon->set_code( $code );
		}

		if ( isset( $input['email_restrictions'] ) ) {
			$emails = array_filter( array_map( 'sanitize_email', array_map( 'strval', (array) $input['email_restrictions'] ) ) );

			if ( count( $emails ) !== count( array_filter( (array) $input['email_restrictions'] ) ) ) {
				return new \WP_Error(
					'woocommerce_coupon_email_invalid',
					__( 'Email restrictions must be valid email addresses.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$coupon->set_email_restrictions( array_values( $emails ) );
		}

		$setters = array(
			'discount_type'        => 'set_discount_type',
			'amount'               => 'set_amount',
			'date_expires'         => 'set_date_expires',
			'usage_limit'          => 'set_usage_limit',
			'usage_limit_per_user' => 'set_usage_limit_per_user',
			'individual_use'       => 'set_individual_use',
			'free_shipping'        => 'set_free_shipping',
			'minimum_amount'       => 'set_minimum_amount',
			'maximum_amount'       => 'set_maximum_amount',
		);

		try {
			foreach ( $setters as $field => $setter ) {
				if ( array_key_exists( $field, $input ) ) {
					$coupon->$setter( $input[ $field ] ?? null );
				}
			}
		} catch ( \WC_Data_Exception $exception ) {
			return new \WP_Error(
				$exception->getErrorCode(),
				$exception->getMessage(),
				array( 'status' => 400 )
			);
		}

		if ( isset( $input['description'] ) ) {
			$coupon->set_description( wp_kses_post( (string) $input['description'] ) );
		}

		return true;
	}

	/**
	 * Save a coupon and set its post status.
	 *
	 * @param \WC_Coupon  $coupon     Coupon object.
	 * @param string|null $status     Post status to apply, null to keep the current one.
	 * @param string      $error_code Error code to return when saving fails.
	 * @return true|\WP_Error
	 */
	protected static function save_coupon( \WC_Coupon $coupon, ?string $status, string $error_code ) {
		if ( null !== $status ) {
			$coupon->set_status( $status );
		}

		try {
			$coupon_id = $coupon->save();
		} catch ( \Exception $exception ) {
			$coupon_id = 0;
		}

		if ( ! $coupon_id ) {
			return new \WP_Error(
				$error_code,
				__( 'Failed to save coupon.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Format a coupon for ability output.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return array
	 */
	protected static function format_coupon_for_response( \WC_Coupon $coupon ): array {
		$usage_limit          = $coupon->get_usage_limit();
		$usage_limit_per_user = $coupon->get_usage_limit_per_user();

		return array(
			'id'                   => $coupon->get_id(),
			'code'                 => $coupon->get_code(),
			'status'               => (string) $coupon->get_status(),
			'discount_type'        => $coupon->get_discount_type(),
			'amount'               => wc_format_decimal( $coupon->get_amount() ),
			'description'          => $coupon->get_description(),
			'date_expires'         => wc_rest_prepare_date_response( $coupon->get_date_expires(), false ),
			'date_expires_gmt'     => wc_rest_prepare_date_response( $coupon->get_date_expires() ),
			'usage_count'          => $coupon->get_usage_count(),
			'usage_limit'          => $usage_limit ? (int) $usage_limit : null,
			'usage_limit_per_user' => $usage_limit_per_user ? (int) $usage_limit_per_user : null,
			'individual_use'       => $coupon->get_individual_use(),
			'free_shipping'        => $coupon->get_free_shipping(),
			'minimum_amount'       => wc_format_decimal( $coupon->get_minimum_amount() ),
			'maximum_amount'       => wc_format_decimal( $coupon->get_maximum_amount() ),
			'email_restrictions'   => array_values( $coupon->get_email_restrictions() ),
			'date_created'         => wc_rest_prepare_date_response( $coupon->get_date_created(), false ),
			'date_modified'        => wc_rest_prepare_date_response( $coupon->get_date_modified(), false ),
		);
	}
}
