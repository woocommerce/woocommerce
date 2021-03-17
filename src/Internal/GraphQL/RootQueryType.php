<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use Automattic\WooCommerce\Internal\GraphQL\QueryTypes\ProductAttribute;
use Automattic\WooCommerce\Internal\GraphQL\QueryTypes\ProductAttributes;
use Automattic\WooCommerce\Internal\GraphQL\QueryTypes\ProductAttributeTerm;
use Automattic\WooCommerce\Internal\GraphQL\QueryTypes\ProductAttributeTerms;

/**
 * Defines the root query type for the GraphQL API.
 *
 * All the work is actually done in the "BaseRootType" class,
 * see its code for details.
 */
class RootQueryType extends BaseRootType {

	/**
	 * Gets the classes that define query operations.
	 *
	 * @return string[] The classes that define query operations.
	 */
	protected function get_object_type_classes() {
		return array(
			ProductAttribute::class,
			ProductAttributes::class,
			ProductAttributeTerm::class,
			ProductAttributeTerms::class,
		);
	}

	/**
	 * Gets the name of this root query.
	 *
	 * @return string The name of this root query.
	 */
	protected function get_name() {
		return 'Query';
	}

	/**
	 * Gets the description of this root query.
	 *
	 * @return string The description of this root query.
	 */
	protected function get_description() {
		return 'The query root of WooCommerce GraphQL API.';
	}

	/**
	 * Gets the required permission for the queries defined in this root query.
	 *
	 * @return string The required permission for the queries defined in this root query.
	 */
	protected function get_required_permission() {
		return 'read';
	}

	/**
	 * Gets the name of the method to execute in the target query type classes.
	 *
	 * @return string The name of the method to execute in the target query type classes.
	 */
	protected function get_resolve_method_name() {
		return 'resolve';
	}
}
