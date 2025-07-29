/**
 * External dependencies
 */
import { ProductResponseItem } from '@woocommerce/types';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

export const useProduct = ( postId: number | string | undefined ) => {
	return useSelect(
		( select ) => {
			if ( ! postId ) {
				return {
					product: null,
					isResolving: false,
				};
			}

			const parsedPostId =
				typeof postId === 'string' ? parseInt( postId, 10 ) : postId;

			const product = select( coreStore ).getEditedEntityRecord(
				'root',
				'product',
				parsedPostId
			) as unknown as ProductResponseItem | undefined;

			const isResolving = select( coreStore ).isResolving(
				'root',
				'product',
				// @ts-expect-error - @woocommerce/data types are not compatible with @wordpress/data types.
				parsedPostId
			);

			return {
				product,
				isResolving,
			};
		},
		[ postId ]
	);
};
