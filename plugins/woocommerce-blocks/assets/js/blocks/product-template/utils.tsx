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
			const { getBlockParentsByBlockName, getBlockAttributes } =
				select( 'core/block-editor' );

			const parentBlocksClientIds = getBlockParentsByBlockName(
				clientId,
				'woocommerce/product-collection',
				true
			);

			if ( parentBlocksClientIds.length ) {
				const closestParentClientId = parentBlocksClientIds[ 0 ];
				return getBlockAttributes( closestParentClientId );
			}

			return null;
		},
		[ clientId ]
	);
};
