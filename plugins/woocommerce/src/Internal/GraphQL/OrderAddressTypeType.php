<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\EnumType;

/**
 * Enumeration type to represent the order address types in GraphQL requests.
 */
class OrderAddressTypeType extends EnumType {

	public function __construct() {
		 $config = array(
			 'name'        => 'OrderAddressType',
			 'description' => 'Order address type',
			 'values'      => array(
				 'BILLING'  => 'billing',
				 'SHIPPING' => 'shipping',
			 ),
		 );
		 parent::__construct( $config );
	}
}
