<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Coupon_Code;

/**
 * Generates WooCommerce coupons at email send time for the coupon-code block.
 *
 * Hooks into the woocommerce_coupon_code_block_auto_generate filter to create
 * a WC_Coupon from block attributes. This provides baseline auto-generation
 * that works without any additional plugins (e.g. MailPoet).
 *
 * Integrators like MailPoet can hook the same filter at a higher priority
 * to add features like per-subscriber restriction or coupon persistence.
 */
class Coupon_Code_Generator {

	/**
	 * Maximum number of retries for generating a unique coupon code.
	 */
	const MAX_CODE_RETRIES = 5;

	/**
	 * Initialize the generator by registering the filter hook.
	 */
	public function init(): void {
		add_filter( 'woocommerce_coupon_code_block_auto_generate', array( $this, 'generate_coupon' ), 10, 3 );
	}

	/**
	 * Generate a WooCommerce coupon from block attributes.
	 *
	 * @param string            $coupon_code       The coupon code (empty if not yet generated).
	 * @param array             $attrs             Block attributes.
	 * @param Rendering_Context $rendering_context The rendering context.
	 * @return string The generated coupon code, or empty string on failure.
	 */
	public function generate_coupon( string $coupon_code, array $attrs, Rendering_Context $rendering_context ): string {
		if ( ! empty( $coupon_code ) ) {
			return $coupon_code;
		}

		if ( $rendering_context->get( 'is_user_preview' ) ) {
			return Coupon_Code::COUPON_CODE_PLACEHOLDER;
		}

		if ( ! function_exists( 'wc_get_coupon_types' ) || ! class_exists( 'WC_Coupon' ) ) {
			return '';
		}

		try {
			$coupon = new \WC_Coupon();
			$coupon->set_code( $this->generate_unique_code() );

			$discount_type = $this->validate_discount_type( $attrs['discountType'] ?? 'percent' );
			$coupon->set_discount_type( $discount_type );

			if ( isset( $attrs['amount'] ) ) {
				$coupon->set_amount( (float) $attrs['amount'] );
			}

			if ( ! empty( $attrs['expiryDay'] ) ) {
				$expiration = time() + ( (int) $attrs['expiryDay'] * DAY_IN_SECONDS );
				$coupon->set_date_expires( $expiration );
			}

			$coupon->set_free_shipping( ! empty( $attrs['freeShipping'] ) );

			$coupon->set_minimum_amount( (float) ( $attrs['minimumAmount'] ?? 0 ) );
			$coupon->set_maximum_amount( (float) ( $attrs['maximumAmount'] ?? 0 ) );
			$coupon->set_individual_use( ! empty( $attrs['individualUse'] ) );
			$coupon->set_exclude_sale_items( ! empty( $attrs['excludeSaleItems'] ) );

			$coupon->set_product_ids( $this->extract_ids( $attrs['productIds'] ?? array() ) );
			$coupon->set_excluded_product_ids( $this->extract_ids( $attrs['excludedProductIds'] ?? array() ) );
			$coupon->set_product_categories( $this->extract_ids( $attrs['productCategoryIds'] ?? array() ) );
			$coupon->set_excluded_product_categories( $this->extract_ids( $attrs['excludedProductCategoryIds'] ?? array() ) );

			$email_restrictions = $this->parse_email_restrictions( $attrs['emailRestrictions'] ?? '' );

			$recipient = $rendering_context->get_recipient_email();
			if ( $recipient && is_email( $recipient ) ) {
				$email_restrictions[] = $recipient;
			}

			$coupon->set_email_restrictions( array_unique( $email_restrictions ) );

			$usage_limit          = $attrs['usageLimit'] ?? 0;
			$usage_limit_per_user = $attrs['usageLimitPerUser'] ?? 0;
			$coupon->set_usage_limit( is_numeric( $usage_limit ) ? (int) $usage_limit : 0 );
			$coupon->set_usage_limit_per_user( is_numeric( $usage_limit_per_user ) ? (int) $usage_limit_per_user : 0 );

			$coupon->set_description(
				__( 'Auto-generated coupon by WooCommerce Email Editor', 'woocommerce' )
			);

			$coupon->save();

			return $coupon->get_code();
		} catch ( \Exception $e ) {
			wc_get_logger()->error(
				'Coupon auto-generation failed: ' . $e->getMessage(),
				array( 'source' => 'email-editor-coupon-generator' )
			);
			return '';
		}
	}

	/**
	 * Parse and validate email restrictions string.
	 *
	 * @param mixed $raw Raw email restrictions value (comma-separated string).
	 * @return array Array of valid email addresses.
	 */
	private function parse_email_restrictions( $raw ): array {
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}

		$emails = array_map( 'trim', explode( ',', $raw ) );

		return array_values(
			array_filter(
				$emails,
				function ( string $email ): bool {
					return (bool) is_email( $email );
				}
			)
		);
	}

	/**
	 * Validate discount type against WooCommerce's registered types.
	 *
	 * @param string $type The discount type to validate.
	 * @return string A valid discount type.
	 */
	private function validate_discount_type( string $type ): string {
		$valid_types = array_keys( wc_get_coupon_types() );
		return in_array( $type, $valid_types, true ) ? $type : 'percent';
	}

	/**
	 * Generate a unique random coupon code, retrying on collision.
	 *
	 * @return string A unique coupon code.
	 * @throws \RuntimeException When a unique code cannot be generated after max retries.
	 */
	private function generate_unique_code(): string {
		for ( $i = 0; $i < self::MAX_CODE_RETRIES; $i++ ) {
			$code     = $this->generate_random_code();
			$existing = wc_get_coupon_id_by_code( $code );
			if ( ! $existing ) {
				return $code;
			}
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception message, not rendered output.
		throw new \RuntimeException( 'Failed to generate a unique coupon code.' );
	}

	/**
	 * Generate a random coupon code in XXXX-XXXXXX-XXXX format.
	 *
	 * @return string
	 */
	private function generate_random_code(): string {
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$length     = strlen( $characters ) - 1;

		$segment1 = '';
		$segment2 = '';
		$segment3 = '';

		for ( $i = 0; $i < 4; $i++ ) {
			$segment1 .= $characters[ random_int( 0, $length ) ];
		}
		for ( $i = 0; $i < 6; $i++ ) {
			$segment2 .= $characters[ random_int( 0, $length ) ];
		}
		for ( $i = 0; $i < 4; $i++ ) {
			$segment3 .= $characters[ random_int( 0, $length ) ];
		}

		return $segment1 . '-' . $segment2 . '-' . $segment3;
	}

	/**
	 * Extract integer IDs from an array of {id, title} objects.
	 *
	 * @param array $items Array of items with 'id' key.
	 * @return array Array of integer IDs.
	 */
	private function extract_ids( array $items ): array {
		return array_map(
			function ( $item ): int {
				if ( ! is_array( $item ) ) {
					return 0;
				}
				$id = $item['id'] ?? 0;
				return is_numeric( $id ) ? (int) $id : 0;
			},
			$items
		);
	}
}
