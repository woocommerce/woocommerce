/**
 * External dependencies
 */
import { useIsEmailEditor } from '@woocommerce/email-editor';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { type ProductCollectionAttributes, LayoutOptions } from '../../types';

interface Block {
	clientId: string;
	name: string;
	innerBlocks: Block[];
}

/**
 * Custom hook to adjust the pagination block when switching between layouts.
 *
 * @param {string}                      clientId   - The client ID of the product collection block.
 * @param {ProductCollectionAttributes} attributes - The attributes of the product collection block.
 */
const useEmailPaginationAdjustments = (
	clientId: string,
	attributes: ProductCollectionAttributes
) => {
	const { displayLayout, collection } = attributes;
	const actions = useDispatch( blockEditorStore );
	const isEmail = useIsEmailEditor();

	const { productCollectionBlock } = useSelect(
		( select ) => ( {
			productCollectionBlock:
				// @ts-expect-error getBlock is not typed.
				select( blockEditorStore ).getBlock( clientId ) as Block | null,
		} ),
		[ clientId ]
	);

	useEffect( () => {
		if ( ! clientId || ! productCollectionBlock || ! isEmail ) {
			return;
		}

		// Ensure productCollectionBlock has innerBlocks before proceeding
		if (
			! productCollectionBlock.innerBlocks ||
			! Array.isArray( productCollectionBlock.innerBlocks )
		) {
			return;
		}

		// Remove pagination blocks when in email editor, but only for grid layout
		// to avoid interfering with carousel layout adjustments
		if ( displayLayout.type === LayoutOptions.GRID ) {
			const paginationBlocks = productCollectionBlock.innerBlocks.filter(
				( block: Block ) =>
					block && block.name === 'core/query-pagination'
			);

			paginationBlocks.forEach( ( paginationBlock: Block ) => {
				if ( paginationBlock && paginationBlock.clientId ) {
					try {
						actions.removeBlock( paginationBlock.clientId );
					} catch ( error ) {
						// Silently handle cases where block might already be removed
						// or in an inconsistent state during block editor operations
					}
				}
			} );
		}
	}, [
		displayLayout.type,
		clientId,
		actions,
		collection,
		productCollectionBlock,
		isEmail,
	] );
};

export default useEmailPaginationAdjustments;
