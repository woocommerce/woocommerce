<?php

namespace Automattic\WooCommerce\Internal\GraphQL\EnumTypes;

use Automattic\WooCommerce\Internal\GraphQL\BaseEnumType;

/**
 * Class for the ProductAttributeOrderBy enumeration type.
 */
class ProductAttributeOrderBy extends BaseEnumType {

	/**
	 * Get the description of the type.
	 *
	 * @return string The description of the type.
	 */
	public function get_description() {
		return 'Default sort order for product attribute terms.';
	}

	/**
	 * Get the possible values for the enumeration.
	 *
	 * @return array The possible values for the enumeration.
	 */
	public function get_enum_values() {
		return array(
			'menu_order' => array(
				'description' => 'Order by name.',
			),
			'name'       => array(
				'description' => 'Order by name.',
			),
			'name_num'   => array(
				'description' => 'Order by name (numeric).',
			),
			'id'         => array(
				'description' => 'Order by id.',
			),
		);
	}
}
