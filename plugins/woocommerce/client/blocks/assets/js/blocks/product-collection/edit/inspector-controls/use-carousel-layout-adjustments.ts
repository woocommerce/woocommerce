/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock, type BlockInstance } from '@wordpress/blocks';
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

const findInnerBlock = ( innerBlocks: BlockInstance[], blockName: string ) =>
	innerBlocks.find( ( block: BlockInstance ) => block.name === blockName );

const handleTransitionToCarouselLayout = (
	innerBlocks: BlockInstance[],
	actions: ReturnType< typeof useDispatch >,
	clientId: string,
	productTemplateBlock: BlockInstance,
	productTemplateClientId: string,
	productTemplateIndex: number
) => {
	const { removeBlock, insertBlock } = actions;

	const paginationBlock = findInnerBlock(
		innerBlocks,
		coreQueryPaginationBlockName
	);
	const paginationBlockClientId = paginationBlock?.clientId;

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

	const nextPrevArrowsBlock = createBlock( nextPreviousArrowsBlockName );
	const groupBlock = createBlock( 'core/group', {}, [
		nextPrevArrowsBlock,
		productTemplateUpdatedBlock,
	] );

	// We cannot use replaceBlock directly because it crashes the editor
	// when replacing the product template block with the group block that
	// contains the same product template block.
	removeBlock( productTemplateClientId, false );
	insertBlock( groupBlock, productTemplateIndex, clientId, false );

	if ( paginationBlockClientId ) {
		removeBlock( paginationBlockClientId, false );
	}
};

const handleTransitionFromCarouselLayout = (
	innerBlocks: BlockInstance[],
	actions: ReturnType< typeof useDispatch >,
	clientId: string,
	collection?: string
) => {
	const { removeBlock, insertBlock, replaceBlock } = actions;

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
		// Extract the product template block from the group.
		const productTemplate = findInnerBlock(
			groupBlock.innerBlocks,
			productTemplateBlockName
		);

		if ( productTemplate ) {
			const productTemplateUpdatedBlock = createBlock(
				productTemplateBlockName,
				{
					...productTemplate.attributes,
					// Grid and List layouts are handled manually for now so
					// we need to reset it to an empty object.
					layout: {},
				},
				productTemplate.innerBlocks
			);

			// Replace the group block with the product template block.
			replaceBlock( groupBlock.clientId, productTemplateUpdatedBlock );
		}
	}

	const nextPrevArrowsBlock = findInnerBlock(
		innerBlocks,
		nextPreviousArrowsBlockName
	);
	const nextPrevArrowsBlockClientId = nextPrevArrowsBlock?.clientId;

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
		innerBlocks,
		productTemplateBlock,
		productTemplateClientId,
		productTemplateIndex,
	} = useSelect(
		( select ) => {
			const selectInnerBlocks = clientId
				? select( blockEditorStore ).getBlocks( clientId )
				: [];

			const selectProductTemplateBlock = findInnerBlock(
				innerBlocks,
				productTemplateBlockName
			);

			const selectProductTemplateBlockClientId =
				selectProductTemplateBlock?.clientId;

			return {
				innerBlocks: selectInnerBlocks,
				productTemplateBlock: selectProductTemplateBlock,
				productTemplateClientId: selectProductTemplateBlockClientId,
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
			productTemplateBlock &&
			productTemplateClientId
		) {
			handleTransitionToCarouselLayout(
				innerBlocks,
				actions,
				clientId,
				productTemplateBlock,
				productTemplateClientId,
				productTemplateIndex
			);
		}

		// When switching FROM carousel layout, remove arrows block and add pagination block (if needed).
		if (
			displayLayout?.type !== LayoutOptions.CAROUSEL &&
			previousLayoutType.current === LayoutOptions.CAROUSEL
		) {
			handleTransitionFromCarouselLayout(
				innerBlocks,
				actions,
				clientId,
				collection
			);
		}

		previousLayoutType.current = displayLayout.type;
	}, [
		displayLayout.type,
		innerBlocks,
		clientId,
		actions,
		collection,
		productTemplateBlock,
		productTemplateClientId,
		productTemplateIndex,
	] );
};

export default useLayoutAdjustments;
