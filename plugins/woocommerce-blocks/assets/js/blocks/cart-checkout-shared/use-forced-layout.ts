/**
 * External dependencies
 */
import {
	useLayoutEffect,
	useRef,
	useCallback,
	useMemo,
} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createBlock,
	getBlockType,
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';
import type { Block, AttributeSource, TemplateArray } from '@wordpress/blocks';
import { isEqual } from 'lodash';

const isBlockLocked = ( {
	attributes,
}: {
	attributes: Record< string, AttributeSource.Attribute >;
} ) => Boolean( attributes.lock?.remove || attributes.lock?.default?.remove );

/**
 * useForcedLayout hook
 *
 * Responsible for ensuring FORCED blocks exist in the inner block layout. Forced blocks cannot be removed.
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
} ): void => {
	const currentRegisteredBlocks = useRef( registeredBlocks );
	const currentDefaultTemplate = useRef( defaultTemplate );

	const { insertBlock, replaceInnerBlocks } =
		useDispatch( 'core/block-editor' );

	const { innerBlocks, registeredBlockTypes } = useSelect(
		( select ) => {
			return {
				innerBlocks:
					select( 'core/block-editor' ).getBlocks( clientId ),
				registeredBlockTypes: currentRegisteredBlocks.current.map(
					( blockName ) => getBlockType( blockName )
				),
			};
		},
		[ clientId, currentRegisteredBlocks.current ]
	);

	const appendBlock = useCallback(
		( block, position ) => {
			const newBlock = createBlock( block.name );
			insertBlock( newBlock, position, clientId, false );
		},
		[ clientId, insertBlock ]
	);

	const lockedBlockTypes = useMemo(
		() =>
			registeredBlockTypes.filter(
				( block: Block | undefined ) => block && isBlockLocked( block )
			),
		[ registeredBlockTypes ]
	) as Block[];

	/**
	 * If the current inner blocks differ from the registered blocks, push the differences.
	 */
	useLayoutEffect( () => {
		if ( ! clientId ) {
			return;
		}

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

		// Find registered locked blocks missing from Inner Blocks and append them.
		lockedBlockTypes.forEach( ( block ) => {
			// If the locked block type is already in the layout, we can skip this one.
			if (
				innerBlocks.find(
					( { name }: { name: string } ) => name === block.name
				)
			) {
				return;
			}

			// Is the forced block part of the default template, find it's original position.
			const defaultTemplatePosition =
				currentDefaultTemplate.current.findIndex(
					( [ blockName ] ) => blockName === block.name
				);

			switch ( defaultTemplatePosition ) {
				case -1:
					// The block is not part of the default template so we append it to the current layout.
					appendBlock( block, innerBlocks.length );
					break;
				case 0:
					// The block was the first block in the default layout, so prepend it to the current layout.
					appendBlock( block, 0 );
					break;
				default:
					// The new layout may have extra blocks compared to the default template, so rather than insert
					// at the default position, we should append it after another default block.
					const adjacentBlock =
						currentDefaultTemplate.current[
							defaultTemplatePosition - 1
						];
					const position = innerBlocks.findIndex(
						( { name: blockName } ) =>
							blockName === adjacentBlock[ 0 ]
					);
					appendBlock(
						block,
						position === -1 ? defaultTemplatePosition : position + 1
					);
					break;
			}
		} );
	}, [
		clientId,
		innerBlocks,
		lockedBlockTypes,
		replaceInnerBlocks,
		appendBlock,
	] );
};
