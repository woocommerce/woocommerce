<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Object to represent the root GraphQL query.
 */
class RootQueryType extends ObjectType {

	public function __construct() {
		 $config = array(
			 'fields' => array(

				 'Order'        => array(
					 'type'    => wc_get_container()->get( OrderType::class ),
					 'args'    => array(
						 'id' => Type::int(),
					 ),
					 'resolve' => function( $objectValue, $args, $context, ResolveInfo $info ) {
						$order_id        = $args['id'];
						$fields          = $info->getFieldSelection( 1 );
						$retriever       = wc_get_container()->get( OrderRetriever::class );
						$billing_address = $shipping_address = null;
						if ( array_key_exists( 'billing_address', $fields ) ) {
							$address_fields  = $fields['billing_address'];
							$billing_address = $retriever->retrieve_address( $order_id, 'billing', array_keys( $address_fields ) );
							unset( $fields['billing_address'] );
						}
						if ( array_key_exists( 'shipping_address', $fields ) ) {
							$address_fields   = $fields['shipping_address'];
							$shipping_address = $retriever->retrieve_address( $order_id, 'shipping', array_keys( $address_fields ) );
							unset( $fields['shipping_address'] );
						}
						$order = empty( $fields ) ? array() : $retriever->retrieve_order( $args['id'], array_keys( $fields ) );
						if ( $billing_address ) {
							$order['billing_address'] = $billing_address;
						}
						if ( $shipping_address ) {
							$order['shipping_address'] = $shipping_address;
						}
						return $order;
					 },
				 ),

				 'OrderAddress' => array(
					 'type'    => wc_get_container()->get( OrderAddressType::class ),
					 'args'    => array(
						 'order_id'     => Type::int(),
						 'address_type' => wc_get_container()->get( OrderAddressTypeType::class ),
					 ),
					 'resolve' => function( $objectValue, $args, $context, ResolveInfo $info ) {
						$fields = array_keys( $info->getFieldSelection() );
						return wc_get_container()->get( OrderRetriever::class )->retrieve_address( $args['order_id'], $args['address_type'], $fields );
					 },
				 ),
			 ),
		 );
		 parent::__construct( $config );
	}
}
