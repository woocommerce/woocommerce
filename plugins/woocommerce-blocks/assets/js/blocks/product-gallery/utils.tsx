/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { BlockAttributes } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';

/**
 * Generates layout attributes based on the position of thumbnails.
 *
 * @param {string} thumbnailsPosition - The position of thumbnails ('bottom' or other values).
 * @return {{type: string, orientation?: string, flexWrap?: string}} - An object representing layout attributes.
 */
export const getGroupLayoutAttributes = (
	thumbnailsPosition: string
): { type: string; orientation?: string; flexWrap?: string } => {
	switch ( thumbnailsPosition ) {
		case 'bottom':
			// Stack
			return { type: 'flex', orientation: 'vertical' };
		default:
			// Row
			return { type: 'flex', flexWrap: 'nowrap' };
	}
};

/**
 * Returns inner block lock attributes based on provided action.
 *
 * @param {string} action - The action to take on the inner blocks ('lock' or 'unlock').
 * @return {{lock: {move?: boolean, remove?: boolean}}} - An object representing lock attributes for inner blocks.
 */
export const getInnerBlocksLockAttributes = (
	action: string
): { lock: { move?: boolean; remove?: boolean } } => {
	switch ( action ) {
		case 'lock':
			return { lock: { move: true, remove: true } };
		case 'unlock':
			return { lock: {} };
		default:
			return { lock: {} };
	}
};

/**
 * Updates block attributes based on provided attributes.
 *
 * @param {BlockAttributes}             attributesToUpdate - The new attributes to set on the block.
 * @param {BlockAttributes | undefined} block              - The block object to update.
 */
export const updateBlockAttributes = (
	attributesToUpdate: BlockAttributes,
	block: BlockAttributes | undefined
): void => {
	if ( block !== undefined ) {
		const updatedBlock = {
			...block,
			attributes: {
				...block.attributes,
				...attributesToUpdate,
			},
		};

		dispatch( 'core/block-editor' ).updateBlock(
			block.clientId,
			updatedBlock
		);
	}
};

/**
 * Moves inner blocks to a position based on provided attributes.
 *
 * @param {BlockAttributes} attributes - The attributes of the parent block.
 * @param {string}          clientId   - The clientId of the parent block.
 */
export const moveInnerBlocksToPosition = (
	attributes: BlockAttributes,
	clientId: string
): void => {
	const parentBlock = select( 'core/block-editor' ).getBlock( clientId );

	if ( parentBlock?.name === 'woocommerce/product-gallery' ) {
		const groupBlock = parentBlock.innerBlocks.find(
			( innerBlock ) => innerBlock.name === 'core/group'
		);

		if ( groupBlock ) {
			const largeImageBlock = groupBlock.innerBlocks.find(
				( innerBlock ) =>
					innerBlock.name ===
					'woocommerce/product-gallery-large-image'
			);

			const thumbnailsBlock = groupBlock.innerBlocks.find(
				( innerBlock ) =>
					innerBlock.name === 'woocommerce/product-gallery-thumbnails'
			);

			const thumbnailsIndex = groupBlock.innerBlocks.findIndex(
				( innerBlock ) =>
					innerBlock.name === 'woocommerce/product-gallery-thumbnails'
			);

			const largeImageIndex = groupBlock.innerBlocks.findIndex(
				( innerBlock ) =>
					innerBlock.name ===
					'woocommerce/product-gallery-large-image'
			);

			if ( thumbnailsIndex !== -1 && largeImageIndex !== -1 ) {
				updateBlockAttributes(
					getInnerBlocksLockAttributes( 'unlock' ),
					thumbnailsBlock
				);
				updateBlockAttributes(
					getInnerBlocksLockAttributes( 'unlock' ),
					largeImageBlock
				);

				const { thumbnailsPosition } = attributes;
				const clientIdToMove =
					groupBlock.innerBlocks[ thumbnailsIndex ].clientId;

				if (
					thumbnailsPosition === 'bottom' ||
					thumbnailsPosition === 'right'
				) {
					// @ts-expect-error - Ignoring because `moveBlocksDown` is not yet in the type definitions.
					dispatch( blockEditorStore ).moveBlocksDown(
						[ clientIdToMove ],
						groupBlock.clientId
					);
				} else {
					// @ts-expect-error - Ignoring because `moveBlocksUp` is not yet in the type definitions.
					dispatch( blockEditorStore ).moveBlocksUp(
						[ clientIdToMove ],
						groupBlock.clientId
					);
				}

				updateBlockAttributes(
					getInnerBlocksLockAttributes( 'lock' ),
					thumbnailsBlock
				);
				updateBlockAttributes(
					getInnerBlocksLockAttributes( 'lock' ),
					largeImageBlock
				);
			}
		}
	}
};

/**
 * Updates the type of group block based on provided attributes.
 *
 * @param {BlockAttributes} attributes - The attributes of the parent block.
 * @param {string}          clientId   - The clientId of the parent block.
 */
export const updateGroupBlockType = (
	attributes: BlockAttributes,
	clientId: string
): void => {
	const block = select( 'core/block-editor' ).getBlock( clientId );
	block?.innerBlocks.forEach( ( innerBlock ) => {
		if ( innerBlock.name === 'core/group' ) {
			updateBlockAttributes(
				{
					layout: getGroupLayoutAttributes(
						attributes.thumbnailsPosition
					),
				},
				innerBlock
			);
		}
	} );
};
