<?php

namespace Automattic\WooCommerce\Internal\GraphQL\MutationTypes;

use Automattic\WooCommerce\Internal\GraphQL\ApiException;
use Automattic\WooCommerce\Internal\GraphQL\BaseMutationType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class for the AddProductAttributeTerm mutation type.
 */
class AddProductAttributeTerm extends BaseMutationType {

	/**
	 * Get the description of the type.
	 *
	 * @return string The description of the type.
	 */
	public function get_description() {
		return 'Creates a new product attribute term.';
	}

	/**
	 * Execute the mutation.
	 *
	 * @param array       $args Arguments for the mutation operation.
	 * @param mixed       $context Context passed from the caller, currently unused.
	 * @param ResolveInfo $info The resolve info passed from the GraphQL engine.
	 * @return array The result of the mutation execution.
	 * @throws ApiException Can't execute the mutation/error when executing the mutation.
	 */
	public function execute( $args, $context, ResolveInfo $info ) {

		// TODO: Implement inserting 'menu order' too.

		$input = $args['input'];

		$attribute = wc_get_attribute( $input['attribute_id'] );
		if ( is_null( $attribute ) ) {
			throw new ApiException( 'Invalid attribute id' );
		}

		$insert_args = array();
		if ( isset( $input['description'] ) ) {
			$insert_args['description'] = $input['description'];
		}
		if ( isset( $input['slug'] ) ) {
			$insert_args['slug'] = $input['slug'];
		}

		$term = wp_insert_term( $input['name'], $attribute->slug, $insert_args );
		if ( is_wp_error( $term ) ) {
			throw new ApiException( "Can't create term: " . $term->get_error_message() );
		}

		return array( 'id' => $term['term_id'] );
	}
}
