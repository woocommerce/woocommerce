/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * It find the parent block "woocommerce/product-collection" and return its attributes.
 */
export const useProductCollectionBlockAttributes = ( clientId: string ) => {
	return useSelect(
		( select ) => {
			const { getBlockParents, getBlockName, getBlockAttributes } =
				select( 'core/block-editor' );

			let currentBlockId = clientId;
			while ( currentBlockId ) {
				const parentIds = getBlockParents( currentBlockId, true );
				if ( ! parentIds.length ) break;

				currentBlockId = parentIds[ 0 ]; // Get the first parent
				const parentName = getBlockName( currentBlockId );

				if ( parentName === 'woocommerce/product-collection' ) {
					return getBlockAttributes( currentBlockId );
				}
			}

			return null;
		},
		[ clientId ]
	);
};
