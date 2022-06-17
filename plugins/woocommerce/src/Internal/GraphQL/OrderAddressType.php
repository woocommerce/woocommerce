<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Object type to represent an order address in GraphQL.
 */
class OrderAddressType extends ObjectType {

	public function __construct() {
		 $config = array(
			 'fields' => array(
				 'id'           => Type::nonNull( Type::int() ),
				 'order_id'     => Type::nonNull( Type::int() ),
				 'address_type' => wc_get_container()->get( OrderAddressTypeType::class ),
				 'first_name'   => Type::string(),
				 'last_name'    => Type::string(),
				 'company'      => Type::string(),
				 'address_1'    => Type::string(),
				 'address_2'    => Type::string(),
				 'city'         => Type::string(),
				 'state'        => Type::string(),
				 'postcode'     => Type::string(),
				 'country'      => Type::string(),
				 'email'        => Type::string(),
				 'phone'        => Type::string(),
			 ),
		 );

		 parent::__construct( $config );
	}
}
