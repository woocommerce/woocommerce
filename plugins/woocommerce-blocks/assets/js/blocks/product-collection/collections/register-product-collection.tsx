/**
 * External dependencies
 */
import { BlockVariation, registerBlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { EditorBlock } from '@woocommerce/types';
import type { ElementType } from '@wordpress/element';
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

export interface ProductCollectionConfig extends BlockVariation {
	preview?: {
		setPreviewState?: SetPreviewState;
		initialPreviewState?: PreviewState;
	};
}

/**
 * Register a new collection for the Product Collection block.
 *
 * @param {ProductCollectionConfig} blockVariationArgs The configuration of new collection.
 */
const registerProductCollection = ( {
	preview: { setPreviewState, initialPreviewState } = {},
	...blockVariationArgs
}: ProductCollectionConfig ) => {
	/**
	 * If setPreviewState or initialPreviewState is provided, inject the setPreviewState & initialPreviewState props.
	 * This is useful for handling preview mode in the editor.
	 */
	if ( setPreviewState || initialPreviewState ) {
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
