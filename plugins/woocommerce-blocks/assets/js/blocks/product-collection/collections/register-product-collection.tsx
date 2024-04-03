/**
 * External dependencies
 */
import { BlockVariation, registerBlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { EditorBlock } from '@woocommerce/types';
import { type ElementType } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import blockJson from '../block.json';
import {
	HandlePreviewState,
	PreviewState,
	ProductCollectionAttributes,
} from '../types';

// Extends BlockVariation to include an optional handlePreviewState function for preview management.
export interface ProductCollectionConfig extends BlockVariation {
	preview?: {
		handlePreviewState?: HandlePreviewState;
		initialState?: PreviewState;
	};
}

/**
 * Registers a product collection variation, optionally setting up a preview state handler.
 *
 * @param {ProductCollectionConfig} blockVariationArgs - The configuration for the product collection, potentially including a handlePreviewState function.
 */
const registerProductCollection = ( {
	preview: { handlePreviewState, initialState } = {},
	...blockVariationArgs
}: ProductCollectionConfig ) => {
	// Don't add filter if handlePreviewState is not provided.
	if ( handlePreviewState || initialState ) {
		// This HOC adds a handlePreviewState & initialPreviewState to the BlockEdit component's props.
		const withHandlePreviewState =
			< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
			( props: BlockEditProps< ProductCollectionAttributes > ) => {
				// If collection name does not match, return the original BlockEdit component.
				if ( props.attributes.collection !== blockVariationArgs.name ) {
					return <BlockEdit { ...props } />;
				}

				// Otherwise, inject the handlePreviewState function into the BlockEdit component's props.
				return (
					<BlockEdit
						{ ...props }
						preview={ { handlePreviewState, initialState } }
					/>
				);
			};
		addFilter(
			'editor.BlockEdit',
			blockVariationArgs.name,
			withHandlePreviewState
		);
	}

	registerBlockVariation( blockJson.name, {
		...blockVariationArgs,
		attributes: {
			...blockVariationArgs.attributes,
			previewState: initialState,
		},
	} );
};

export default registerProductCollection;
