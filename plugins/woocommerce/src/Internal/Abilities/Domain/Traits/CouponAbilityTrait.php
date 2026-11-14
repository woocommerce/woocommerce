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
				'id'                          => array( 'type' => 'integer' ),
				'code'                        => array( 'type' => 'string' ),
				'status'                      => array( 'type' => 'string' ),
				'discount_type'               => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_coupon_types() ),
				),
				'amount'                      => array( 'type' => 'string' ),
				'description'                 => array( 'type' => 'string' ),
				'date_expires'                => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
				'date_expires_gmt'            => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
				'product_ids'                 => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'excluded_product_ids'        => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'product_categories'          => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'excluded_product_categories' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'exclude_sale_items'          => array( 'type' => 'boolean' ),
				'redeemable'                  => array( 'type' => 'boolean' ),
				'expired'                     => array( 'type' => 'boolean' ),
				'scheduled'                   => array( 'type' => 'boolean' ),
				'not_redeemable_reason'       => array( 'type' => 'string' ),
				'starts_at'                   => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
				'expiry'                      => array(
					'type'                 => 'object',
					'properties'           => array(
						'expires_at'     => array( 'type' => 'string' ),
						'last_valid_day' => array( 'type' => 'string' ),
						'timezone'       => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'usage_count'                 => array( 'type' => 'integer' ),
				'usage_limit'                 => array( 'type' => 'integer' ),
				'usage_limit_per_user'        => array( 'type' => 'integer' ),
				'individual_use'              => array( 'type' => 'boolean' ),
				'free_shipping'               => array( 'type' => 'boolean' ),
				'minimum_amount'              => array( 'type' => 'string' ),
				'maximum_amount'              => array( 'type' => 'string' ),
				'date_created'                => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
				'date_modified'               => array(
					'type'   => 'string',
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
				'type'        => 'string',
				'description' => __( 'Expiry date in the store timezone (Y-m-d or ISO 8601). An empty string removes the expiry date.', 'woocommerce' ),
			),
			'usage_limit'          => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => __( 'How many times the coupon can be used in total. 0 means unlimited.', 'woocommerce' ),
			),
			'usage_limit_per_user' => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => __( 'How many times each customer can use the coupon. 0 means unlimited.', 'woocommerce' ),
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
				'woocommerce_ability_coupon_id_required',
				__( 'Coupon ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( 'shop_coupon' !== get_post_type( $coupon_id ) ) {
			return new \WP_Error(
				'woocommerce_ability_coupon_not_found',
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
		$query = new \WP_Query(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => self::get_coupon_status_slugs(),
				'title'          => $code,
				'post__not_in'   => array( $coupon_id ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return ! empty( $query->posts );
	}

	/**
	 * Apply writable fields from ability input to a coupon.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @param array      $input  Ability input.
	 * @return true|\WP_Error
	 */
	protected static function set_coupon_props_from_input( \WC_Coupon $coupon, array $input ) {
		if ( 'future' === $coupon->get_status() && ( ! $coupon->get_date_created() || $coupon->get_date_created()->getTimestamp() <= time() ) ) {
			return new \WP_Error( 'woocommerce_ability_coupon_invalid_schedule', __( 'Scheduling requires an existing future publication date.', 'woocommerce' ), array( 'status' => 400 ) );
		}
		if ( isset( $input['date_expires'] ) && '' !== $input['date_expires'] ) {
			$date = date_parse( $input['date_expires'] );
			if ( $date['error_count'] || $date['warning_count'] || ! $date['year'] || ! $date['month'] || ! $date['day'] ) {
				return new \WP_Error( 'woocommerce_ability_coupon_invalid_expiry', __( 'Expiry must be a valid date or datetime.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			$input['date_expires'] = ( new \DateTimeImmutable( $input['date_expires'], wp_timezone() ) )->getTimestamp();
		}

		if ( isset( $input['code'] ) ) {
			$code = wc_format_coupon_code( (string) $input['code'] );

			if ( '' === $code ) {
				return new \WP_Error(
					'woocommerce_ability_coupon_code_required',
					__( 'Coupon code is required.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			if ( self::is_coupon_code_taken( $code, $coupon->get_id() ) ) {
				return new \WP_Error(
					'woocommerce_ability_coupon_code_exists',
					__( 'A coupon with this code already exists.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$coupon->set_code( $code );
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
			if ( isset( $input['discount_type'] ) && ! isset( $input['amount'] ) ) {
				$coupon->set_amount( $coupon->get_amount( 'edit' ) );
			}
		} catch ( \WC_Data_Exception $exception ) {
			return new \WP_Error(
				'woocommerce_ability_coupon_invalid_data',
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
	 * Format a coupon for ability output.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return array
	 */
	protected static function format_coupon_for_response( \WC_Coupon $coupon ): array {
		$usage_limit          = $coupon->get_usage_limit();
		$usage_limit_per_user = $coupon->get_usage_limit_per_user();

		$output              = array(
			'id'                          => $coupon->get_id(),
			'code'                        => $coupon->get_code(),
			'status'                      => (string) $coupon->get_status(),
			'discount_type'               => $coupon->get_discount_type(),
			'amount'                      => wc_format_decimal( $coupon->get_amount() ),
			'description'                 => $coupon->get_description(),
			'date_expires'                => self::format_date_for_response( $coupon->get_date_expires(), false ),
			'date_expires_gmt'            => self::format_date_for_response( $coupon->get_date_expires() ),
			'product_ids'                 => array_values( $coupon->get_product_ids() ),
			'excluded_product_ids'        => array_values( $coupon->get_excluded_product_ids() ),
			'product_categories'          => array_values( $coupon->get_product_categories() ),
			'excluded_product_categories' => array_values( $coupon->get_excluded_product_categories() ),
			'exclude_sale_items'          => $coupon->get_exclude_sale_items(),
			'usage_count'                 => $coupon->get_usage_count(),
			'usage_limit'                 => (int) $usage_limit,
			'usage_limit_per_user'        => (int) $usage_limit_per_user,
			'individual_use'              => $coupon->get_individual_use(),
			'free_shipping'               => $coupon->get_free_shipping(),
			'minimum_amount'              => wc_format_decimal( $coupon->get_minimum_amount() ),
			'maximum_amount'              => wc_format_decimal( $coupon->get_maximum_amount() ),
			'date_created'                => self::format_date_for_response( $coupon->get_date_created(), false ),
			'date_modified'               => self::format_date_for_response( $coupon->get_date_modified(), false ),
		);
		$date                = $coupon->get_date_expires();
		$output['expired']   = $date && time() > $date->getTimestamp();
		$output['scheduled'] = 'future' === $coupon->get_status();
		$reason              = '';
		if ( $output['expired'] ) {
			$reason = 'expired';
		} elseif ( $usage_limit && $coupon->get_usage_count() >= $usage_limit ) {
			$reason = 'usage_limit_reached';
		} elseif ( $output['scheduled'] ) {
			$reason = 'scheduled';
		} elseif ( 'publish' !== $coupon->get_status() ) {
			$reason = 'not_published';
		}
		$output['redeemable'] = '' === $reason;
		if ( '' !== $reason ) {
			$output['not_redeemable_reason'] = $reason;
		}
		if ( $output['scheduled'] ) {
			$output['starts_at'] = self::format_date_for_response( $coupon->get_date_created(), false );
		}
		$output['expiry'] = array( 'timezone' => wp_timezone_string() );
		if ( $date ) {
			$local = clone $date;
			$local->setTimezone( wp_timezone() );
			$output['expiry']['expires_at'] = $local->format( DATE_ATOM );
			if ( '00:00:00' === $local->format( 'H:i:s' ) ) {
				$output['expiry']['last_valid_day'] = $local->modify( '-1 day' )->format( 'Y-m-d' );
			}
		}
		return array_filter(
			$output,
			static function ( $value ) {
				return null !== $value;
			}
		);
	}
}
