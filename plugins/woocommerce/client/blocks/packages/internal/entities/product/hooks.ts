/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ProductEntityResponse } from './types';

export const useProduct = ( postId: number | string | undefined ) => {
	return useSelect(
		( select ) => {
			if ( ! postId ) {
				return {
					product: undefined,
					isResolving: false,
				};
			}

			const parsedPostId =
				typeof postId === 'string' ? parseInt( postId, 10 ) : postId;

			const product = select( coreStore ).getEditedEntityRecord(
				'root',
				'product',
				parsedPostId
			) as unknown as ProductEntityResponse | undefined;

			const isResolving = select( coreStore ).isResolving(
				'getEditedEntityRecord',
				[ 'root', 'product', parsedPostId ]
			);
			const isResolutionFinished = select(
				coreStore
			).hasFinishedResolution( 'getEditedEntityRecord', [
				'root',
				'product',
				parsedPostId,
			] );

			return {
				product,
				isResolving,
				isResolutionFinished,
			};
		},
		[ postId ]
	);
};
