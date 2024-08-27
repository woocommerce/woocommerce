/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import { Inspector } from './inspector';
import { BlockAttributes } from '../types';

function getInnerBlockByName(
	block: BlockInstance | null,
	name: string
): BlockInstance | null {
	if ( ! block ) return null;

	if ( block.innerBlocks.length === 0 ) return null;

	for ( const innerBlock of block.innerBlocks ) {
		if ( innerBlock.name === name ) return innerBlock;
		const innerInnerBlock = getInnerBlockByName( innerBlock, name );
		if ( innerInnerBlock ) return innerInnerBlock;
	}

	return null;
}

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore ignoring this line because @wordpress/compose does not expose the correct type for createHigherOrderComponent
export const withPriceControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if (
			props.name !== 'woocommerce/product-filter' ||
			! props.attributes?.filterType ||
			props.attributes.filterType !== 'price-filter'
		) {
			return <BlockEdit key="edit" { ...props } />;
		}

		const { updateBlockAttributes } = useDispatch( blockEditorStore );

		// Find the attributes of the inner price filter block.
		const block = useSelect( ( select ) =>
			select( 'core/block-editor' ).getBlock( props.clientId )
		);
		const priceFilterBlock = getInnerBlockByName( block, metadata.name );
		if ( ! priceFilterBlock ) return <BlockEdit key="edit" { ...props } />;

		return (
			<>
				<BlockEdit key="edit" { ...props } />
				<Inspector
					attributes={
						priceFilterBlock.attributes as BlockAttributes
					}
					setAttributes={ ( attributes ) => {
						updateBlockAttributes(
							priceFilterBlock.clientId,
							attributes
						);
					} }
				/>
			</>
		);
	};
}, 'withPriceControls' );
