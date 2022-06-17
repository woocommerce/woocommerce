<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Object type to represent an order in GraphQL.
 */
class OrderType extends ObjectType {

	public function __construct() {
		 $config = array(
			 'description' => 'An order, no more, no less.',
			 'fields'      => array(
				 'id'               => Type::nonNull( Type::int() ),
				 'status'           => Type::string(),
				 'currency'         => Type::string(),
				 'tax_amount'       => Type::float(),
				 'billing_email'    => Type::string(),
				 'date_created_gmt' => wc_get_container()->get( DateTimeType::class ),
				 'date_updated_gmt' => wc_get_container()->get( DateTimeType::class ),
				 'ip_address'       => Type::string(),
				 'user_agent'       => Type::string(),
				 'billing_address'  => wc_get_container()->get( OrderAddressType::class ),
				 'shipping_address' => wc_get_container()->get( OrderAddressType::class ),
			 ),
		 );

		 parent::__construct( $config );
	}
}
