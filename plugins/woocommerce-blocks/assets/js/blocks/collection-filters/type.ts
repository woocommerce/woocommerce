/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import type { ProductCollectionQuery } from '@woocommerce/blocks/product-collection/types';

type BlockAttributes = {
	collectionData: unknown[];
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	context: {
		query: ProductCollectionQuery;
	};
}
