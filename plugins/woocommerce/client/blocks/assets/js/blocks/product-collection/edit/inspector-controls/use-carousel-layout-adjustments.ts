/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { getInnerBlockBy, getInnerBlockByName } from '@woocommerce/utils';

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

const handleTransitionToCarouselLayout = (
	productCollectionBlock: BlockInstance,
	productTemplateBlock: BlockInstance,
	productTemplateIndex: number,
	actions: ReturnType< typeof useDispatch >
) => {
	const { removeBlock, insertBlock } = actions;

	const paginationBlock = getInnerBlockByName(
		productCollectionBlock,
		coreQueryPaginationBlockName
	);

	const paginationBlockClientId = paginationBlock?.clientId;
	const productTemplateBlockClientId = productTemplateBlock?.clientId;
	const productCollectionBlockClientId = productCollectionBlock?.clientId;

	const nextPrevArrowsBlock = createBlock( nextPreviousArrowsBlockName );
	const productTemplateUpdatedBlock = createBlock(
		productTemplateBlockName,
		{
			...productTemplateBlock.attributes,
			layout: {
				type: 'flex',
				justifyContent: 'left',
				verticalAlignment: 'top',
				flexWrap: 'nowrap',
				orientation: 'horizontal',
			},
		},
		productTemplateBlock.innerBlocks
	);
	const groupBlock = createBlock( 'core/group', {}, [
		nextPrevArrowsBlock,
		productTemplateUpdatedBlock,
	] );

	// We cannot use replaceBlock directly because it crashes the editor
	// when replacing the product template block with the group block that
	// contains the same product template block.
	removeBlock( productTemplateBlockClientId, false );
	insertBlock(
		groupBlock,
		productTemplateIndex,
		productCollectionBlockClientId,
		false
	);

	if ( paginationBlockClientId ) {
		removeBlock( paginationBlockClientId, false );
	}
};

const handleTransitionFromCarouselLayout = (
	productCollectionBlock: BlockInstance,
	actions: ReturnType< typeof useDispatch >,
	collection?: string
) => {
	const { removeBlock, insertBlock, replaceBlock } = actions;

	// Remove the next/previous arrows block if it exists.
	const nextPrevArrowsBlock = getInnerBlockByName(
		productCollectionBlock,
		nextPreviousArrowsBlockName
	);

	if ( nextPrevArrowsBlock ) {
		removeBlock( nextPrevArrowsBlock?.clientId, false );
	}

	// Find the group block containing the product template
	const groupBlock = getInnerBlockBy( productCollectionBlock, ( block ) => {
		return (
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock: BlockInstance ) =>
					innerBlock.name === productTemplateBlockName
			)
		);
	} );

	// Extract the product template block.
	const productTemplate = getInnerBlockByName(
		productCollectionBlock,
		productTemplateBlockName
	);

	const productTemplateUpdatedBlock = productTemplate
		? createBlock(
				productTemplateBlockName,
				{
					...productTemplate.attributes,
					// Grid and List layouts are handled manually for now so
					// we need to reset it to an empty object.
					layout: {},
				},
				productTemplate.innerBlocks
		  )
		: null;

	if ( productTemplateUpdatedBlock ) {
		// Replace the group block if it exists (user might have manipulated it).
		// Otherwise, replace the product template block if it exists.
		if ( groupBlock ) {
			replaceBlock( groupBlock.clientId, productTemplateUpdatedBlock );
		} else if ( productTemplate ) {
			replaceBlock(
				productTemplate?.clientId,
				productTemplateUpdatedBlock
			);
		}
	}

	// Add the pagination block for default collection (it has collection attribute undefined).
	if ( ! collection ) {
		insertBlock(
			createBlock(
				coreQueryPaginationBlockName,
				paginationDefaultAttributes
			),
			productCollectionBlock.innerBlocks.length,
			productCollectionBlock.clientId,
			false
		);
	}
};

/**
 * Custom hook to adjust the pagination block when switching between layouts.
 *
 * @param {string}                      clientId   - The client ID of the product collection block.
 * @param {ProductCollectionAttributes} attributes - The attributes of the product collection block.
 */
const useLayoutAdjustments = (
	clientId: string,
	attributes: ProductCollectionAttributes
) => {
	const { displayLayout, collection } = attributes;
	const previousLayoutType = useRef< LayoutOptions >( displayLayout.type );
	const actions = useDispatch( blockEditorStore );

	const {
		productCollectionBlock,
		productTemplateBlock,
		productTemplateIndex,
	} = useSelect(
		( select ) => {
			const selectProductCollectionBlock =
				select( blockEditorStore ).getBlock( clientId );

			const selectProductTemplateBlock = getInnerBlockByName(
				selectProductCollectionBlock,
				productTemplateBlockName
			);

			const selectProductTemplateBlockClientId =
				selectProductTemplateBlock?.clientId;

			return {
				productCollectionBlock: selectProductCollectionBlock,
				productTemplateBlock: selectProductTemplateBlock,
				productTemplateIndex: selectProductTemplateBlock?.clientId
					? select( blockEditorStore ).getBlockIndex(
							selectProductTemplateBlockClientId
					  )
					: 0,
			};
		},
		[ clientId ]
	);

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
			handleTransitionToCarouselLayout(
				productCollectionBlock,
				productTemplateBlock,
				productTemplateIndex,
				actions
			);
		}

		// When switching FROM carousel layout, remove arrows block and add pagination block (if needed).
		if (
			displayLayout?.type !== LayoutOptions.CAROUSEL &&
			previousLayoutType.current === LayoutOptions.CAROUSEL
		) {
			handleTransitionFromCarouselLayout(
				productCollectionBlock,
				actions,
				collection
			);
		}

		previousLayoutType.current = displayLayout.type;
	}, [
		displayLayout.type,
		clientId,
		actions,
		collection,
		productTemplateBlock,
		productTemplateIndex,
	] );
};

export default useLayoutAdjustments;
