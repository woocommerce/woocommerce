<?php

namespace Automattic\WooCommerce\Internal\GraphQL\InputTypes;

use Automattic\WooCommerce\Internal\GraphQL\BaseInputType;
use GraphQL\Type\Definition\Type;

/**
 * Class for the AddProductAttributeTerm input type.
 */
class AddProductAttributeTermInputType extends BaseInputType {

	/**
	 * Get the fields of the type.
	 *
	 * @return array Fields of the type.
	 */
	public function get_fields() {
		return array(
			'attribute_id' => array(
				'type'        => Type::nonNull( Type::id() ),
				'description' => 'Unique identifier of the product attribute the term will be added to.',
			),
			'name'         => array(
				'type'        => Type::nonNull( Type::string() ),
				'description' => 'Term name.',
			),
			'slug'         => array(
				'type'        => Type::string(),
				'description' => 'An alphanumeric identifier for the resource unique to its type.',
			),
			'description'  => array(
				'type'        => Type::string(),
				'description' => 'HTML description of the resource.',
			),

			// TODO: Add 'menu order' when implementing insertion in AddProductAttributeTerm.

			/*
			'menu_order' => [
				'type' => Type::int(),
				'description' => 'Menu order, used to custom sort the resource.'
			]
			*/
		);
	}
}
