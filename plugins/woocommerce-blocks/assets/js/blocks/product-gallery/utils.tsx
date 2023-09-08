/**
 * External dependencies
 */
import { BlockAttributes, BlockInstance } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ThumbnailsPosition } from './inner-blocks/product-gallery-thumbnails/constants';

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

const controlBlocksLockAttribute = ( {
	blocks,
	lockBlocks,
}: {
	blocks: BlockInstance[];
	lockBlocks: boolean;
} ) => {
	for ( const block of blocks ) {
		if ( lockBlocks ) {
			updateBlockAttributes(
				getInnerBlocksLockAttributes( 'lock' ),
				block
			);
		} else {
			updateBlockAttributes(
				getInnerBlocksLockAttributes( 'unlock' ),
				block
			);
		}
	}
};

/**
 * Recursively searches through an array of `BlockInstance` objects and their nested `innerBlocks` arrays to find a block that matches a given condition.
 *
 * @param { { blocks: BlockInstance[], findCondition: Function } } parameters Parameters containing an array of `BlockInstance` objects to search through and a function that takes a `BlockInstance` object as its argument and returns a boolean indicating whether the block matches the desired condition.
 * @return If a matching block is found, the function returns the `BlockInstance` object. If no matching block is found, the function returns `undefined`.
 */
const findBlock = ( {
	blocks,
	findCondition,
}: {
	blocks: BlockInstance[];
	findCondition: ( block: BlockInstance ) => boolean;
} ): BlockInstance | undefined => {
	for ( const block of blocks ) {
		if ( findCondition( block ) ) {
			return block;
		}
		if ( block.innerBlocks ) {
			const largeImageParentBlock = findBlock( {
				blocks: block.innerBlocks,
				findCondition,
			} );
			if ( largeImageParentBlock ) {
				return largeImageParentBlock;
			}
		}
	}

	return undefined;
};

/**
 * Sets the layout of group block based on the thumbnails' position.
 *
 * @param {ThumbnailsPosition} thumbnailsPosition - The position of thumbnails.
 * @param {string}             clientId           - The client ID of the block to update.
 */
const setGroupBlockLayoutByThumbnailsPosition = (
	thumbnailsPosition: ThumbnailsPosition,
	clientId: string
): void => {
	const block = select( 'core/block-editor' ).getBlock( clientId );
	block?.innerBlocks.forEach( ( innerBlock ) => {
		if ( innerBlock.name === 'core/group' ) {
			updateBlockAttributes(
				{
					layout: getGroupLayoutAttributes( thumbnailsPosition ),
				},
				innerBlock
			);
		}
	} );
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
	const { getBlock, getBlockRootClientId, getBlockIndex } =
		select( 'core/block-editor' );
	const { moveBlockToPosition } = dispatch( 'core/block-editor' );
	const productGalleryBlock = getBlock( clientId );

	if ( productGalleryBlock ) {
		const thumbnailsBlock = findBlock( {
			blocks: [ productGalleryBlock ],
			findCondition( block ) {
				return block.name === 'woocommerce/product-gallery-thumbnails';
			},
		} );
		const largeImageParentBlock = findBlock( {
			blocks: [ productGalleryBlock ],
			findCondition( block ) {
				return Boolean(
					block.innerBlocks?.find(
						( innerBlock ) =>
							innerBlock.name ===
							'woocommerce/product-gallery-large-image'
					)
				);
			},
		} );
		const largeImageParentBlockIndex = getBlockIndex(
			largeImageParentBlock?.clientId || ''
		);
		const thumbnailsBlockIndex = getBlockIndex(
			thumbnailsBlock?.clientId || ''
		);

		if (
			largeImageParentBlock &&
			thumbnailsBlock &&
			largeImageParentBlockIndex !== -1 &&
			thumbnailsBlockIndex !== -1
		) {
			controlBlocksLockAttribute( {
				blocks: [ thumbnailsBlock, largeImageParentBlock ],
				lockBlocks: false,
			} );

			const { thumbnailsPosition } = attributes;
			setGroupBlockLayoutByThumbnailsPosition(
				thumbnailsPosition,
				clientId
			);

			if (
				( ( thumbnailsPosition === 'bottom' ||
					thumbnailsPosition === 'right' ) &&
					thumbnailsBlockIndex < largeImageParentBlockIndex ) ||
				( thumbnailsPosition === 'left' &&
					thumbnailsBlockIndex > largeImageParentBlockIndex )
			) {
				moveBlockToPosition(
					thumbnailsBlock.clientId,
					getBlockRootClientId( thumbnailsBlock.clientId ) ||
						undefined,
					getBlockRootClientId( largeImageParentBlock.clientId ) ||
						undefined,
					largeImageParentBlockIndex
				);
			}

			controlBlocksLockAttribute( {
				blocks: [ thumbnailsBlock, largeImageParentBlock ],
				lockBlocks: true,
			} );
		}
	}
};
