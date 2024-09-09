/**
 * Internal dependencies
 */
import { SEARCH_BLOCK_NAME, SEARCH_VARIATION_NAME } from './constants';
import { ProductSearchBlock } from './types';

/**
 * Identifies if a block is a Search block variation from our conventions
 *
 * We are extending Gutenberg's core Search block with our variations, and
 * also adding extra namespaced attributes. If those namespaced attributes
 * are present, we can be fairly sure it is our own registered variation.
 */
export function isWooSearchBlockVariation( block: ProductSearchBlock ) {
	return (
		block.name === SEARCH_BLOCK_NAME &&
		block.attributes?.namespace === SEARCH_VARIATION_NAME
	);
}
