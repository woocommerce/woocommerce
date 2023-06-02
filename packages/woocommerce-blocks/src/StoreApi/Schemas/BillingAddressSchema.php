<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Blocks\RestApi\Routes;


/**
 * BillingAddressSchema class.
 *
 * Provides a generic billing address schema for composition in other schemas.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class BillingAddressSchema extends AbstractAddressSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'billing_address';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'billing-address';

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		$properties = parent::get_properties();
		return array_merge(
			$properties,
			[
				'email' => [
					'description' => __( 'Email', 'woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'required'    => true,
				],
				'phone' => [
					'description' => __( 'Phone', 'woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'required'    => true,
				],
			]
		);
	}

	/**
	 * Sanitize and format the given address object.
	 *
	 * @param array            $address Value being sanitized.
	 * @param \WP_REST_Request $request The Request.
	 * @param string           $param The param being sanitized.
	 * @return array
	 */
	public function sanitize_callback( $address, $request, $param ) {
		$address          = parent::sanitize_callback( $address, $request, $param );
		$address['email'] = wc_clean( wp_unslash( $address['email'] ) );
		$address['phone'] = wc_clean( wp_unslash( $address['phone'] ) );
		return $address;
	}

	/**
	 * Validate the given address object.
	 *
	 * @param array            $address Value being sanitized.
	 * @param \WP_REST_Request $request The Request.
	 * @param string           $param The param being sanitized.
	 * @return true|\WP_Error
	 */
	public function validate_callback( $address, $request, $param ) {
		$errors  = parent::validate_callback( $address, $request, $param );
		$address = $this->sanitize_callback( $address, $request, $param );
		$errors  = is_wp_error( $errors ) ? $errors : new \WP_Error();

		if ( ! empty( $address['email'] ) && ! is_email( $address['email'] ) ) {
			$errors->add(
				'invalid_email',
				__( 'The provided email address is not valid', 'woocommerce' )
			);
		}

		if ( ! empty( $address['phone'] ) && ! \WC_Validation::is_phone( $address['phone'] ) ) {
			$errors->add(
				'invalid_phone',
				__( 'The provided phone number is not valid', 'woocommerce' )
			);
		}

		return $errors->has_errors( $errors ) ? $errors : true;
	}

	/**
	 * Convert a term object into an object suitable for the response.
	 *
	 * @param \WC_Order|\WC_Customer $address An object with billing address.
	 *
	 * @throws RouteException When the invalid object types are provided.
	 * @return stdClass
	 */
	public function get_item_response( $address ) {
		if ( ( $address instanceof \WC_Customer || $address instanceof \WC_Order ) ) {
			return (object) $this->prepare_html_response(
				[
					'first_name' => $address->get_billing_first_name(),
					'last_name'  => $address->get_billing_last_name(),
					'company'    => $address->get_billing_company(),
					'address_1'  => $address->get_billing_address_1(),
					'address_2'  => $address->get_billing_address_2(),
					'city'       => $address->get_billing_city(),
					'state'      => $address->get_billing_state(),
					'postcode'   => $address->get_billing_postcode(),
					'country'    => $address->get_billing_country(),
					'email'      => $address->get_billing_email(),
					'phone'      => $address->get_billing_phone(),
				]
			);
		}
		throw new RouteException(
			'invalid_object_type',
			sprintf(
				/* translators: Placeholders are class and method names */
				__( '%1$s requires an instance of %2$s or %3$s for the address', 'woocommerce' ),
				'BillingAddressSchema::get_item_response',
				'WC_Customer',
				'WC_Order'
			),
			500
		);
	}
}
