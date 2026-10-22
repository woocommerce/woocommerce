<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * Defines the core checkout fields: the address, contact, and order fields every store starts with.
 */
class CoreCheckoutFields {

	/**
	 * Returns the keys of all core fields.
	 *
	 * @return array An array of field keys.
	 */
	public static function get_keys() {
		return [
			'email',
			'country',
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'phone',
		];
	}

	/**
	 * Returns an array of all core fields.
	 *
	 * @return array An array of fields.
	 */
	public static function get_fields() {
		return [
			'email'      => [
				'label'          => __( 'Email address', 'woocommerce' ),
				'optionalLabel'  => __(
					'Email address (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'email',
				'autocapitalize' => 'none',
				'type'           => 'email',
				'index'          => 0,
			],
			'country'    => [
				'label'         => __( 'Country/Region', 'woocommerce' ),
				'optionalLabel' => __(
					'Country/Region (optional)',
					'woocommerce'
				),
				'required'      => true,
				'hidden'        => false,
				'autocomplete'  => 'country',
				'index'         => 1,
			],
			'first_name' => [
				'label'          => __( 'First name', 'woocommerce' ),
				'optionalLabel'  => __(
					'First name (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'given-name',
				'autocapitalize' => 'sentences',
				'index'          => 10,
			],
			'last_name'  => [
				'label'          => __( 'Last name', 'woocommerce' ),
				'optionalLabel'  => __(
					'Last name (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'family-name',
				'autocapitalize' => 'sentences',
				'index'          => 20,
			],
			'company'    => [
				'label'          => __( 'Company', 'woocommerce' ),
				'optionalLabel'  => __(
					'Company (optional)',
					'woocommerce'
				),
				'required'       => 'required' === CartCheckoutUtils::get_company_field_visibility(),
				'hidden'         => 'hidden' === CartCheckoutUtils::get_company_field_visibility(),
				'autocomplete'   => 'organization',
				'autocapitalize' => 'sentences',
				'index'          => 30,
			],
			'address_1'  => [
				'label'          => __( 'Address', 'woocommerce' ),
				'optionalLabel'  => __(
					'Address (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-line1',
				'autocapitalize' => 'sentences',
				'index'          => 40,
			],
			'address_2'  => [
				'label'          => __( 'Apartment, suite, etc.', 'woocommerce' ),
				'optionalLabel'  => __(
					'Apartment, suite, etc. (optional)',
					'woocommerce'
				),
				'required'       => 'required' === CartCheckoutUtils::get_address_2_field_visibility(),
				'hidden'         => 'hidden' === CartCheckoutUtils::get_address_2_field_visibility(),
				'autocomplete'   => 'address-line2',
				'autocapitalize' => 'sentences',
				'index'          => 50,
			],
			'city'       => [
				'label'          => __( 'City', 'woocommerce' ),
				'optionalLabel'  => __(
					'City (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-level2',
				'autocapitalize' => 'sentences',
				'index'          => 70,
			],
			'state'      => [
				'label'          => __( 'State/County', 'woocommerce' ),
				'optionalLabel'  => __(
					'State/County (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-level1',
				'autocapitalize' => 'sentences',
				'index'          => 80,
			],
			'postcode'   => [
				'label'          => __( 'Postal code', 'woocommerce' ),
				'optionalLabel'  => __(
					'Postal code (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'postal-code',
				'autocapitalize' => 'characters',
				'index'          => 90,
			],
			'phone'      => [
				'label'          => __( 'Phone', 'woocommerce' ),
				'optionalLabel'  => __(
					'Phone (optional)',
					'woocommerce'
				),
				'required'       => 'required' === CartCheckoutUtils::get_phone_field_visibility(),
				'hidden'         => 'hidden' === CartCheckoutUtils::get_phone_field_visibility(),
				'type'           => 'tel',
				'autocomplete'   => 'tel',
				'autocapitalize' => 'characters',
				'index'          => 100,
			],
		];
	}
}
