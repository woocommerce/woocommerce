<?php
/**
 * Convert a customer object to the product schema format.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Responses;

defined( 'ABSPATH' ) || exit;

/**
 * CustomerResponse class.
 */
class CustomerResponse extends AbstractObjectResponse {

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param \WC_Customer $object Product data.
	 * @param string       $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	public function prepare_response( $object, $context ) {
		$data        = $object->get_data();
		$format_date = array( 'date_created', 'date_modified' );

		// Format date values.
		foreach ( $format_date as $key ) {
			// Date created is stored UTC, date modified is stored WP local time.
			$datetime              = 'date_created' === $key ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $data[ $key ]->getTimestamp() ) ) : $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		return array(
			'id'                 => $object->get_id(),
			'date_created'       => $data['date_created'],
			'date_created_gmt'   => $data['date_created_gmt'],
			'date_modified'      => $data['date_modified'],
			'date_modified_gmt'  => $data['date_modified_gmt'],
			'email'              => $data['email'],
			'first_name'         => $data['first_name'],
			'last_name'          => $data['last_name'],
			'role'               => $data['role'],
			'username'           => $data['username'],
			'billing'            => $data['billing'],
			'shipping'           => $data['shipping'],
			'is_paying_customer' => $data['is_paying_customer'],
			'avatar_url'         => $object->get_avatar_url(),
			'meta_data'          => $data['meta_data'],
		);
	}
}
