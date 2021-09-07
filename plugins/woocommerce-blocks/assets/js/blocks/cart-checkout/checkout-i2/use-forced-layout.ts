/**
 * External dependencies
 */
import { useLayoutEffect, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createBlock,
	getBlockType,
	Block,
	AttributeSource,
} from '@wordpress/blocks';

const isBlockLocked = ( {
	attributes,
}: {
	attributes: Record< string, AttributeSource.Attribute >;
} ) => Boolean( attributes.lock?.remove || attributes.lock?.default?.remove );

export const useForcedLayout = ( {
	clientId,
	template,
}: {
	clientId: string;
	template: Array< string >;
} ): void => {
	const currentTemplate = useRef( template );
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const { innerBlocks, templateTypes } = useSelect(
		( select ) => {
			return {
				innerBlocks: select( 'core/block-editor' ).getBlocks(
					clientId
				),
				templateTypes: currentTemplate.current.map( ( blockName ) =>
					getBlockType( blockName )
				),
			};
		},
		[ clientId, currentTemplate ]
	);
	/**
	 * If the current inner blocks differ from the registered blocks, push the differences.
	 *
	 */
	useLayoutEffect( () => {
		if ( ! clientId ) {
			return;
		}
		// Missing check to see if registered block is 'forced'
		templateTypes.forEach( ( block: Block | undefined ) => {
			if (
				block &&
				isBlockLocked( block ) &&
				! innerBlocks.find(
					( { name }: { name: string } ) => name === block.name
				)
			) {
				const newBlock = createBlock( block.name );
				insertBlock( newBlock, innerBlocks.length, clientId, false );
			}
		} );
	}, [ clientId, innerBlocks, insertBlock, templateTypes ] );
};
