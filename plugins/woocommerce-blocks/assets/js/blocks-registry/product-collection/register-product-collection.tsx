/**
 * External dependencies
 */
import { BlockVariation, registerBlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { EditorBlock } from '@woocommerce/types';
import type { ElementType } from '@wordpress/element';
import type { BlockEditProps, BlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	SetPreviewState,
	PreviewState,
	ProductCollectionAttributes,
} from '../../blocks/product-collection/types';
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_TEMPLATE,
	PRODUCT_COLLECTION_BLOCK_NAME as BLOCK_NAME,
} from '../../blocks/product-collection/constants';

export interface ProductCollectionConfig extends BlockVariation {
	preview?: {
		setPreviewState?: SetPreviewState;
		initialPreviewState?: PreviewState;
	};
}

/**
 * Register a new collection for the Product Collection block.
 *
 * 🚨🚨🚨 WARNING: This is an experimental API and is subject to change without notice.
 *
 * @param {ProductCollectionConfig} blockVariationArgs The configuration of new collection.
 */
export const __experimentalRegisterProductCollection = ( {
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

	const isActive = (
		blockAttrs: BlockAttributes,
		variationAttributes: BlockAttributes
	) => {
		return blockAttrs.collection === variationAttributes.collection;
	};

	registerBlockVariation( BLOCK_NAME, {
		...blockVariationArgs,
		attributes: {
			...DEFAULT_ATTRIBUTES,
			...blockVariationArgs.attributes,
			query: {
				...DEFAULT_ATTRIBUTES.query,
				...blockVariationArgs.attributes?.query,
			},
			collection: blockVariationArgs.name,
			inherit: blockVariationArgs.attributes?.inherit || false,
		},
		innerBlocks: blockVariationArgs.innerBlocks || INNER_BLOCKS_TEMPLATE,
		isActive,
		isDefault: false,
	} );
};
