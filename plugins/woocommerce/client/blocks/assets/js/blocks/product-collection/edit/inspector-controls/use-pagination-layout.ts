/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { coreQueryPaginationBlockName } from '../../constants';
import { LayoutOptions, type ProductCollectionAttributes } from '../../types';

/**
 * Custom hook to adjust the pagination block when switching between layouts.
 *
 * @param {string} clientId - The client ID of the product collection block.
 * @param {ProductCollectionAttributes} attributes - The attributes of the product collection block.
 * @returns {void}
 */
const usePaginationLayout = ( clientId: string, attributes: ProductCollectionAttributes ) => {
	const { displayLayout, collection } = attributes;

	const previousLayoutType = useRef<LayoutOptions>(displayLayout.type);
	const innerBlocks = useSelect(
		( select ) =>
			clientId ? select( blockEditorStore ).getBlocks( clientId ) : [],
		[clientId]
	);
	const { insertBlock, removeBlock, updateBlockAttributes } = useDispatch( blockEditorStore );
	const paginationBlocks = innerBlocks.filter(
		(block: any) => block.name === coreQueryPaginationBlockName
	);
	const paginationBlockClientId = paginationBlocks[0]?.clientId;

	useEffect( () => {
		if ( ! clientId ) {
			return;
		}

		// When switching TO carousel layout, add pagination block if it doesn't exist.
		// Update attributes otherwise.
		if (
			displayLayout?.type === LayoutOptions.CAROUSEL &&
			previousLayoutType.current !== LayoutOptions.CAROUSEL
		) {
			const newAttributes = {
				paginationArrow: 'chevron',
				showLabel: false,
				layout: { type: 'flex', justifyContent: 'center' },
			};

			if ( ! paginationBlockClientId ) {
				const paginationBlock = createBlock( coreQueryPaginationBlockName, newAttributes );
				insertBlock( paginationBlock, innerBlocks.length, clientId, false );
			} else {
				updateBlockAttributes( paginationBlockClientId, newAttributes );
			}
		}

		// When switching FROM carousel layout, remove the pagination block for custom collections.
		// Update attributes otherwise.
		if (
			displayLayout?.type !== LayoutOptions.CAROUSEL &&
			previousLayoutType.current === LayoutOptions.CAROUSEL
		) {

			if ( paginationBlockClientId ) {
				if ( collection ) {
					removeBlock( paginationBlockClientId, false );
				} else {
					updateBlockAttributes( paginationBlockClientId, {
						paginationArrow: 'none',
						showLabel: true,
						layout: { type: 'flex', justifyContent: 'center' },
					} );
				}
			}
		}

		previousLayoutType.current = displayLayout.type;
	}, [ displayLayout.type, innerBlocks, clientId, insertBlock, removeBlock, updateBlockAttributes, collection ]);
};

export default usePaginationLayout;
