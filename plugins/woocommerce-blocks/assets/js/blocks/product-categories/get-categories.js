/**
 * External dependencies
 */
import { PRODUCT_CATEGORIES } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { buildTermsTree } from './hierarchy';

/**
 * Returns categories in tree form.
 */
export default function( { hasEmpty, isHierarchical } ) {
	const categories = PRODUCT_CATEGORIES.filter(
		( cat ) => hasEmpty || !! cat.count
	);
	return isHierarchical ?
		buildTermsTree( categories ) :
		categories;
}
