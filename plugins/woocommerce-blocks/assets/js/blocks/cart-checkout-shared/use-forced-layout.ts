/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { useRegistry, dispatch } from '@wordpress/data';
import {
	createBlock,
	getBlockType,
	createBlocksFromInnerBlocksTemplate,
	BlockInstance,
} from '@wordpress/blocks';
import type { Block, TemplateArray } from '@wordpress/blocks';
import { isEqual } from 'lodash';
import { MutableRefObject } from 'react';

interface LockableBlock extends Block {
	attributes: {
		lock?: {
			type: 'object';
			remove?: boolean;
			move: boolean;
			default?: {
				remove?: boolean;
				move?: boolean;
			};
		};
	};
}
const isBlockLocked = ( {
	attributes,
}: {
	attributes: LockableBlock[ 'attributes' ];
} ) => Boolean( attributes.lock?.remove || attributes.lock?.default?.remove );

/**
 * This hook is used to determine which blocks are missing from a block. Given the list of inner blocks of a block, we
 * can check for any registered blocks that:
 * a) Are locked,
 * b) Have the parent set as the current block, and
 * c) Are not present in the list of inner blocks.
 */
const getMissingBlocks = (
	innerBlocks: BlockInstance[],
	registeredBlockTypes: ( LockableBlock | undefined )[]
) => {
	const lockedBlockTypes = registeredBlockTypes.filter(
		( block: LockableBlock | undefined ) => block && isBlockLocked( block )
	);
	const missingBlocks: LockableBlock[] = [];
	lockedBlockTypes.forEach( ( lockedBlock ) => {
		if ( typeof lockedBlock === 'undefined' ) {
			return;
		}
		const existingBlock = innerBlocks.find(
			( block ) => block.name === lockedBlock.name
		);

		if ( ! existingBlock ) {
			missingBlocks.push( lockedBlock );
		}
	} );
	return missingBlocks;
};

/**
 * This hook is used to determine the position that a missing block should be inserted at.
 *
 * @return The index to insert the missing block at.
 */
const findBlockPosition = ( {
	defaultTemplatePosition,
	innerBlocks,
	currentDefaultTemplate,
}: {
	defaultTemplatePosition: number;
	innerBlocks: BlockInstance[];
	currentDefaultTemplate: MutableRefObject< TemplateArray >;
} ) => {
	switch ( defaultTemplatePosition ) {
		case -1:
			// The block is not part of the default template, so we append it to the current layout.
			return innerBlocks.length;
		// defaultTemplatePosition defaults to 0, so if this happens we can just return, this is because the block was
		// the first block in the default layout, so we can prepend it to the current layout.
		case 0:
			return 0;
		default:
			// The new layout may have extra blocks compared to the default template, so rather than insert
			// at the default position, we should append it after another default block.
			const adjacentBlock =
				currentDefaultTemplate.current[ defaultTemplatePosition - 1 ];
			const position = innerBlocks.findIndex(
				( { name: blockName } ) => blockName === adjacentBlock[ 0 ]
			);
			return position === -1 ? defaultTemplatePosition : position + 1;
	}
};

/**
 * Hook to ensure FORCED blocks are rendered in the correct place.
 */
export const useForcedLayout = ( {
	clientId,
	registeredBlocks,
	defaultTemplate = [],
}: {
	// Client ID of the parent block.
	clientId: string;
	// An array of registered blocks that may be forced in this particular layout.
	registeredBlocks: Array< string >;
	// The default template for the inner blocks in this layout.
	defaultTemplate: TemplateArray;
} ) => {
	const currentRegisteredBlocks = useRef( registeredBlocks );
	const currentDefaultTemplate = useRef( defaultTemplate );

	const registry = useRegistry();
	useEffect( () => {
		const { replaceInnerBlocks } = dispatch( 'core/block-editor' );
		return registry.subscribe( () => {
			const innerBlocks = registry
				.select( 'core/block-editor' )
				.getBlocks( clientId );

			// If there are NO inner blocks, sync with the given template.
			if (
				innerBlocks.length === 0 &&
				currentDefaultTemplate.current.length > 0
			) {
				const nextBlocks = createBlocksFromInnerBlocksTemplate(
					currentDefaultTemplate.current
				);
				if ( ! isEqual( nextBlocks, innerBlocks ) ) {
					replaceInnerBlocks( clientId, nextBlocks );
					return;
				}
			}

			const registeredBlockTypes = currentRegisteredBlocks.current.map(
				( blockName: string ) => getBlockType( blockName )
			);

			const missingBlocks = getMissingBlocks(
				innerBlocks,
				registeredBlockTypes
			);

			if ( missingBlocks.length === 0 ) {
				return;
			}

			// Initially set as -1, so we can skip checking the position multiple times. Later on in the map callback,
			// we check where the forced blocks should be inserted. This gets set to >= 0 if we find a missing block,
			// so we know we can skip calculating it.
			let insertAtPosition = -1;
			const blockConfig = missingBlocks.map( ( block ) => {
				const defaultTemplatePosition =
					currentDefaultTemplate.current.findIndex(
						( [ blockName ] ) => blockName === block.name
					);
				const createdBlock = createBlock( block.name );

				// As mentioned above, if this is not -1, this is the first time we're calculating the position, if it's
				// already been calculated we can skip doing so.
				if ( insertAtPosition === -1 ) {
					insertAtPosition = findBlockPosition( {
						defaultTemplatePosition,
						innerBlocks,
						currentDefaultTemplate,
					} );
				}

				return createdBlock;
			} );

			registry.batch( () => {
				registry
					.dispatch( 'core/block-editor' )
					.insertBlocks( blockConfig, insertAtPosition, clientId );
			} );
		} );
	}, [ clientId, registry ] );
};
