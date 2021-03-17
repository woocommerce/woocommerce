<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use Automattic\WooCommerce\Internal\GraphQL\MutationTypes\AddProductAttributeTerm;

/**
 * Defines the root mutation type for the GraphQL API.
 *
 * All the work is actually done in the "BaseRootType" class,
 * see its code for details.
 */
class RootMutationType extends BaseRootType {

	/**
	 * Gets the classes that define mutation operations.
	 *
	 * @return string[] The classes that define mutation operations.
	 */
	protected function get_object_type_classes() {
		return array(
			AddProductAttributeTerm::class,
		);
	}

	/**
	 * Gets the name of this root query.
	 *
	 * @return string The name of this root query.
	 */
	protected function get_name() {
		return 'Mutation';
	}

	/**
	 * Gets the description of this root query.
	 *
	 * @return string The description of this root query.
	 */
	protected function get_description() {
		return 'The root query for implementing GraphQL mutations.';
	}

	/**
	 * Gets the required permission for the mutations defined in this root query.
	 *
	 * @return string The required permission for the mutations defined in this root query.
	 */
	protected function get_required_permission() {
		return 'write';
	}

	/**
	 * Gets the name of the method to execute in the target mutation type classes.
	 *
	 * @return string The name of the method to execute in the target mutation type classes.
	 */
	protected function get_resolve_method_name() {
		return 'execute';
	}
}
