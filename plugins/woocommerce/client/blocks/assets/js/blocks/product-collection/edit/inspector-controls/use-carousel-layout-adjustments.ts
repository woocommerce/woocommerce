/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createBlock,
	createBlocksFromInnerBlocksTemplate,
	type BlockInstance,
} from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import {
	coreQueryPaginationBlockName,
	productTemplateBlockName,
	nextPreviousArrowsBlockName,
	paginationDefaultAttributes,
} from '../../constants';
import { LayoutOptions, type ProductCollectionAttributes } from '../../types';

const findInnerBlock = ( innerBlocks: BlockInstance[], blockName: string ) => {
	const blocks = innerBlocks.find(
		( block: BlockInstance ) => block.name === blockName
	);

	return blocks;
};

/**
 * Custom hook to adjust the pagination block when switching between layouts.
 *
 * @param {string} clientId - The client ID of the product collection block.
 * @param {ProductCollectionAttributes} attributes - The attributes of the product collection block.
 * @return {void}
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
	const productTemplateBlock = findInnerBlock(
		innerBlocks,
		productTemplateBlockName
	);
	const productTemplateBlockClientId = productTemplateBlock?.clientId;
	const productTemplateBlockIndex = useSelect(
		( select ) =>
			productTemplateBlockClientId
				? select( blockEditorStore ).getBlockIndex(
						productTemplateBlockClientId
				  )
				: 0,
		[ productTemplateBlockClientId ]
	);

	const { insertBlock, removeBlock, replaceBlock } =
		useDispatch( blockEditorStore );

	useEffect( () => {
		if ( ! clientId ) {
			return;
		}

		// When switching TO carousel layout, add arrows block and remove pagination block (if exists).
		if (
			displayLayout?.type === LayoutOptions.CAROUSEL &&
			previousLayoutType.current !== LayoutOptions.CAROUSEL &&
			productTemplateBlock
		) {
			const paginationBlock = findInnerBlock(
				innerBlocks,
				coreQueryPaginationBlockName
			);
			const paginationBlockClientId = paginationBlock?.clientId;

			const nextPrevArrowsBlock = createBlock(
				nextPreviousArrowsBlockName
			);
			const groupBlock = createBlock( 'core/group', {}, [
				nextPrevArrowsBlock,
				productTemplateBlock,
			] );

			// We cannot use replaceBlock directly because it crashes the editor
			// when replacing the product template block with the group block that
			// contains the same product template block.
			removeBlock( productTemplateBlockClientId, false );
			insertBlock(
				groupBlock,
				productTemplateBlockIndex,
				clientId,
				false
			);

			if ( paginationBlockClientId ) {
				removeBlock( paginationBlockClientId, false );
			}
		}

		// When switching FROM carousel layout, remove arrows block and add pagination block (if needed).
		if (
			displayLayout?.type !== LayoutOptions.CAROUSEL &&
			previousLayoutType.current === LayoutOptions.CAROUSEL
		) {
			const nextPrevArrowsBlock = findInnerBlock(
				innerBlocks,
				nextPreviousArrowsBlockName
			);
			const nextPrevArrowsBlockClientId = nextPrevArrowsBlock?.clientId;

			// Find the group block containing the product template
			const groupBlock = innerBlocks.find(
				( block: BlockInstance ) =>
					block.name === 'core/group' &&
					block.innerBlocks.some(
						( innerBlock: BlockInstance ) =>
							innerBlock.name === productTemplateBlockName
					)
			);

			if ( groupBlock ) {
				// Extract the product template block from the group
				const productTemplate = groupBlock.innerBlocks.find(
					( block: BlockInstance ) =>
						block.name === productTemplateBlockName
				);

				// Replace the group block with just the product template
				replaceBlock( groupBlock.clientId, productTemplate );
			}

			if ( nextPrevArrowsBlockClientId ) {
				removeBlock( nextPrevArrowsBlockClientId, false );
			}

			if ( ! collection ) {
				insertBlock(
					createBlock(
						coreQueryPaginationBlockName,
						paginationDefaultAttributes
					),
					innerBlocks.length,
					clientId,
					false
				);
			}
		}

		previousLayoutType.current = displayLayout.type;
	}, [
		displayLayout.type,
		innerBlocks,
		clientId,
		insertBlock,
		removeBlock,
		replaceBlock,
		collection,
		productTemplateBlockIndex,
		productTemplateBlock,
		productTemplateBlockClientId,
	] );
};

export default useLayoutAdjustments;
