/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { ProductTypeProps } from '../shared/stores/product-type-template-state';

const productTypes = getSetting< Record< string, string > >(
	'productTypes',
	{}
);

/**
 * Build product types collection for product types.
 *
 * @return {ProductTypeProps[]} Product types collection.
 */
export function getProductTypeOptions(): ProductTypeProps[] {
	return Object.keys( productTypes ).map( ( key ) => ( {
		slug: key,
		label: productTypes[ key ],
	} ) );
}
