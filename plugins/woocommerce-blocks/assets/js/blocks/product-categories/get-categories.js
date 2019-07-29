/**
 * Internal dependencies
 */
import { buildTermsTree } from './hierarchy';

/**
 * Returns categories in tree form.
 */
export default function( { hasEmpty, isHierarchical } ) {
	const categories = wc_product_block_data.productCategories.filter(
		( cat ) => hasEmpty || !! cat.count
	);
	return isHierarchical ?
		buildTermsTree( categories ) :
		categories;
}
