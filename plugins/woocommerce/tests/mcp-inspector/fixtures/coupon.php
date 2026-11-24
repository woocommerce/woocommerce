<?php
/**
 * A published 10% coupon with the given code, reset on every seed.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{code: string} $args
 * @return array{id: int, code: string}
 */
return static function ( array $args ): array {
	$coupon = new WC_Coupon( wc_get_coupon_id_by_code( $args['code'] ) );

	$coupon->set_props(
		array(
			'code'                 => $args['code'],
			'status'               => 'publish',
			'discount_type'        => 'percent',
			'amount'               => '10',
			'description'          => '',
			'date_expires'         => null,
			'usage_limit'          => 0,
			'usage_limit_per_user' => 0,
			'individual_use'       => false,
			'free_shipping'        => false,
			'minimum_amount'       => '',
			'maximum_amount'       => '',
			'email_restrictions'   => array(),
		)
	);
	$coupon->save();

	return array(
		'id'   => $coupon->get_id(),
		'code' => $coupon->get_code(),
	);
};
