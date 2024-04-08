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
	SetPreviewState,
	PreviewState,
	ProductCollectionAttributes,
} from '../types';

// Extends BlockVariation to include an optional setPreviewState function for preview management.
export interface ProductCollectionConfig extends BlockVariation {
	preview?: {
		setPreviewState?: SetPreviewState;
		initialPreviewState?: PreviewState;
	};
}

/**
 * Registers a product collection variation, optionally setting up a preview state handler.
 *
 * @param {ProductCollectionConfig} blockVariationArgs - The configuration for the product collection, potentially including a setPreviewState function.
 */
const registerProductCollection = ( {
	preview: { setPreviewState, initialPreviewState } = {},
	...blockVariationArgs
}: ProductCollectionConfig ) => {
	// Don't add filter if setPreviewState is not provided.
	if ( setPreviewState || initialPreviewState ) {
		// This HOC adds a setPreviewState & initialPreviewState to the BlockEdit component's props.
		const withSetPreviewState =
			< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
			( props: BlockEditProps< ProductCollectionAttributes > ) => {
				// If collection name does not match, return the original BlockEdit component.
				if ( props.attributes.collection !== blockVariationArgs.name ) {
					return <BlockEdit { ...props } />;
				}

				// Otherwise, inject the setPreviewState & initialPreviewState props.
				return (
					<BlockEdit
						{ ...props }
						preview={ {
							setPreviewState,
							initialPreviewState,
						} }
					/>
				);
			};
		addFilter(
			'editor.BlockEdit',
			blockVariationArgs.name,
			withSetPreviewState
		);
	}

	registerBlockVariation( blockJson.name, {
		...blockVariationArgs,
		attributes: {
			...blockVariationArgs.attributes,
			previewState: initialPreviewState,
		},
	} );
};

export default registerProductCollection;
