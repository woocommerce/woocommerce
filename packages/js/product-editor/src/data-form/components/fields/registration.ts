/**
 * External dependencies
 */
import type { Product } from '@woocommerce/data';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */

const productFields: Record< string, Field< Product > > = {};

export function registerProductField( id: string, field: Field< Product > ) {
	productFields[ id ] = field;
}

export function getProductField( id: string ) {
	return productFields[ id ];
}
