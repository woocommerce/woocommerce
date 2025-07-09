/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createBlock,
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import {
	coreQueryPaginationBlockName,
	nextPreviousArrowsBlockName,
	INNER_BLOCKS_PAGINATION_TEMPLATE,
} from '../../constants';
import { LayoutOptions, type ProductCollectionAttributes } from '../../types';

/**
 * Custom hook to adjust the pagination block when switching between layouts.
 *
 * @param {string} clientId - The client ID of the product collection block.
 * @param {ProductCollectionAttributes} attributes - The attributes of the product collection block.
 * @returns {void}
 */
const useLayoutAdjustments = (
	clientId: string,
	attributes: ProductCollectionAttributes
) => {
	const { displayLayout, collection } = attributes;

	const previousLayoutType = useRef< LayoutOptions >( displayLayout.type );
	const innerBlocks = useSelect(
		( select ) =>
			clientId ? select( blockEditorStore ).getBlocks( clientId ) : [],
		[ clientId ]
	);
	const { insertBlock, replaceBlock, removeBlock } =
		useDispatch( blockEditorStore );

	useEffect( () => {
		if ( ! clientId ) {
			return;
		}

		// When switching TO carousel layout, add arrows block and remove pagination block (if exists).
		if (
			displayLayout?.type === LayoutOptions.CAROUSEL &&
			previousLayoutType.current !== LayoutOptions.CAROUSEL
		) {
			const paginationBlocks = innerBlocks.filter(
				( block: any ) => block.name === coreQueryPaginationBlockName
			);
			const paginationBlockClientId = paginationBlocks[ 0 ]?.clientId;

			const nextPrevArrowsBlock = createBlock(
				nextPreviousArrowsBlockName
			);

			if ( ! paginationBlockClientId ) {
				insertBlock(
					nextPrevArrowsBlock,
					innerBlocks.length,
					clientId,
					false
				);
			} else {
				replaceBlock( paginationBlockClientId, nextPrevArrowsBlock );
			}
		}

		// When switching FROM carousel layout, remove arrows block and add pagination block (if needed).
		if (
			displayLayout?.type !== LayoutOptions.CAROUSEL &&
			previousLayoutType.current === LayoutOptions.CAROUSEL
		) {
			const nextPrevArrowsBlocks = innerBlocks.filter(
				( block: any ) => block.name === nextPreviousArrowsBlockName
			);
			const nextPrevArrowsBlockClientId =
				nextPrevArrowsBlocks[ 0 ]?.clientId;

			if ( nextPrevArrowsBlockClientId ) {
				if ( collection ) {
					removeBlock( nextPrevArrowsBlockClientId, false );
				} else {
					replaceBlock(
						nextPrevArrowsBlockClientId,
						createBlocksFromInnerBlocksTemplate( [
							INNER_BLOCKS_PAGINATION_TEMPLATE,
						] )
					);
				}
			}
		}

		previousLayoutType.current = displayLayout.type;
	}, [
		displayLayout.type,
		innerBlocks,
		clientId,
		insertBlock,
		removeBlock,
		collection,
	] );
};

export default useLayoutAdjustments;
