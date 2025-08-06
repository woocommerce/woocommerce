/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { select as selectData, useSelect, useDispatch } from '@wordpress/data';
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { getInnerBlockBy, getInnerBlockByName } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import {
	coreQueryPaginationBlockName,
	productTemplateBlockName,
	nextPreviousButtonsBlockName,
	paginationDefaultAttributes,
	headingBlockName,
} from '../../constants';
import { LayoutOptions, type ProductCollectionAttributes } from '../../types';

const productTemplateOtherLayouts = { layout: {} };
const productTemplateCarouselLayout = {
	layout: {
		type: 'flex',
		justifyContent: 'left',
		verticalAlignment: 'top',
		flexWrap: 'nowrap',
		orientation: 'horizontal',
	},
};

const createGroupSpaceBetween = ( innerBlocks: BlockInstance[] ) =>
	createBlock(
		'core/group',
		// Row variation of the group block
		{
			layout: {
				type: 'flex',
				flexWrap: 'nowrap',
				justifyContent: 'space-between',
			},
		},
		innerBlocks
	);

const createGroupRight = ( innerBlocks: BlockInstance[] ) =>
	createBlock(
		'core/group',
		// Row variation of the group block
		{
			layout: {
				type: 'flex',
				flexWrap: 'nowrap',
				justifyContent: 'right',
			},
		},
		innerBlocks
	);

/**
 * Handles the transition to carousel layout:
 * - If there's heading before Product Template block:
 *   - Move heading to the Row block
 *   - Add Next/Previous Buttons block
 * - If there's no heading before Product Template block:
 *   - Add Next/Previous Buttons block
 * - Remove Pagination block (if exists)
 *
 * @param {BlockInstance} productCollectionBlock - The product collection block.
 * @param {ReturnType<typeof useDispatch>} actions - The actions to use.
 */
const handleTransitionToCarouselLayout = (
	productCollectionBlock: BlockInstance,
	actions: ReturnType< typeof useDispatch >
) => {
	const { removeBlock, insertBlock, updateBlockAttributes } = actions;

	const productTemplateBlock = getInnerBlockByName(
		productCollectionBlock,
		productTemplateBlockName
	);
	const paginationBlock = getInnerBlockByName(
		productCollectionBlock,
		coreQueryPaginationBlockName
	);
	const headingBlock = getInnerBlockByName(
		productCollectionBlock,
		headingBlockName
	);

	const productCollectionClientId = productCollectionBlock?.clientId;
	const productTemplateClientId = productTemplateBlock?.clientId;

	// 1. Change the layout of the product template block
	updateBlockAttributes(
		productTemplateClientId,
		productTemplateCarouselLayout
	);

	// 2. Create and insert the next/previous buttons block
	const nextPrevArrowsBlock = createBlock( nextPreviousButtonsBlockName, {
		layout: { type: 'flex', flexWrap: 'nowrap' },
	} );

	if ( headingBlock ) {
		// @ts-expect-error getBlockIndex is not typed.
		const headingBlockIndex = selectData( blockEditorStore ).getBlockIndex(
			headingBlock.clientId
		);
		const groupBlock = createGroupSpaceBetween( [
			headingBlock,
			nextPrevArrowsBlock,
		] );

		// We cannot use replaceBlock directly because it crashes the editor
		// when replacing the product template block with the group block that
		// contains the same product template block.
		removeBlock( headingBlock.clientId, false );
		insertBlock(
			groupBlock,
			headingBlockIndex,
			productCollectionClientId,
			false
		);
	} else {
		const productTemplateIndex = selectData(
			blockEditorStore
			// @ts-expect-error getBlockIndex is not typed.
		).getBlockIndex( productTemplateClientId );

		const groupBlock = createGroupRight( [ nextPrevArrowsBlock ] );

		insertBlock(
			groupBlock,
			productTemplateIndex,
			productCollectionClientId,
			false
		);
	}

	// 3. Remove the pagination block
	if ( paginationBlock ) {
		removeBlock( paginationBlock.clientId, false );
	}
};

/**
 * Handles the transition from carousel layout:
 * - Remove Next/Previous Buttons block (if exists)
 * - Remove Row block (if empty)
 * - Add Pagination block for default collection (if needed)
 *
 * @param {BlockInstance} productCollectionBlock - The product collection block.
 * @param {ReturnType<typeof useDispatch>} actions - The actions to use.
 * @param {string} collection - The collection.
 */
const handleTransitionFromCarouselLayout = (
	productCollectionBlock: BlockInstance,
	actions: ReturnType< typeof useDispatch >,
	collection?: string
) => {
	const { removeBlock, insertBlock, updateBlockAttributes } = actions;

	const productTemplateBlock = getInnerBlockByName(
		productCollectionBlock,
		productTemplateBlockName
	);

	// 1. Grid and List layouts are handled manually for now so we need to reset it to an empty object.
	updateBlockAttributes(
		productTemplateBlock?.clientId,
		productTemplateOtherLayouts
	);

	// 2. Remove the next/previous buttons block or group block
	// Find the group block containing the next/previous buttons block
	const groupBlock = getInnerBlockBy( productCollectionBlock, ( block ) => {
		return (
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock: BlockInstance ) =>
					innerBlock.name === nextPreviousButtonsBlockName
			)
		);
	} );
	if ( groupBlock ) {
		// If next/previous buttons block is the only block in the group block, remove it
		if ( groupBlock.innerBlocks.length === 1 ) {
			removeBlock( groupBlock.clientId, false );
		} else {
			const headingBlock = getInnerBlockByName(
				groupBlock,
				headingBlockName
			);

			// If next/previous buttons and heading are the only blocks in the group block, bring back heading block
			if ( headingBlock && groupBlock.innerBlocks.length === 2 ) {
				const headingBlockIndex = selectData(
					blockEditorStore
					// @ts-expect-error getBlockIndex is not typed.
				).getBlockIndex( headingBlock.clientId );
				removeBlock( groupBlock.clientId, false );
				insertBlock(
					headingBlock,
					headingBlockIndex,
					productCollectionBlock.clientId,
					false
				);
				// Otherwise remove next previous buttons block and keep the content
			} else {
				const nextPrevButtonsBlock = getInnerBlockByName(
					productCollectionBlock,
					nextPreviousButtonsBlockName
				);
				removeBlock( nextPrevButtonsBlock?.clientId, false );
			}
		}
	}

	// 3. Add the pagination block for default collection (it has collection attribute undefined).
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
const useCarouselLayoutAdjustments = (
	clientId: string,
	attributes: ProductCollectionAttributes
) => {
	const { displayLayout, collection } = attributes;
	const previousLayoutType = useRef< LayoutOptions >( displayLayout.type );
	const actions = useDispatch( blockEditorStore );

	const { productCollectionBlock } = useSelect(
		( select ) => ( {
			productCollectionBlock:
				// @ts-expect-error getBlock is not typed.
				select( blockEditorStore ).getBlock( clientId ),
		} ),
		[ clientId ]
	);

	useEffect( () => {
		if ( ! clientId ) {
			return;
		}

		// When switching TO carousel layout, add Next Previous Buttons block and remove pagination block (if exists).
		if (
			displayLayout?.type === LayoutOptions.CAROUSEL &&
			previousLayoutType.current !== LayoutOptions.CAROUSEL
		) {
			handleTransitionToCarouselLayout( productCollectionBlock, actions );
		}

		// When switching FROM carousel layout, remove Next Previous Buttons block and add pagination block (if needed).
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
	}, [ displayLayout.type, clientId, actions, collection ] );
};

export default useCarouselLayoutAdjustments;
