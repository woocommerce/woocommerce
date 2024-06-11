/**
 * External dependencies
 */
import { BlockVariation, registerBlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { EditorBlock } from '@woocommerce/types';
import type { ElementType } from '@wordpress/element';
import type { BlockEditProps, BlockAttributes } from '@wordpress/blocks';
import {
	SetPreviewState,
	PreviewState,
	ProductCollectionAttributes,
	CoreFilterNames,
} from '@woocommerce/blocks/product-collection/types';
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_TEMPLATE,
	PRODUCT_COLLECTION_BLOCK_NAME as BLOCK_NAME,
	DEFAULT_QUERY,
} from '@woocommerce/blocks/product-collection/constants';

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

	/**
	 * As we don't allow collections to change "inherit" attribute,
	 * We always need to hide the inherit control.
	 */
	const hideControls = new Set( [
		CoreFilterNames.INHERIT,
		...( blockVariationArgs.attributes?.hideControls || [] ),
	] );

	registerBlockVariation( BLOCK_NAME, {
		...blockVariationArgs,
		attributes: {
			...DEFAULT_ATTRIBUTES,
			query: {
				...DEFAULT_QUERY,
				...blockVariationArgs.attributes?.query,
				/**
				 * Ensure that the postType and isProductCollectionBlock are set to the default values.
				 */
				postType: DEFAULT_QUERY.postType,
				isProductCollectionBlock:
					DEFAULT_QUERY.isProductCollectionBlock,
			},
			displayLayout: {
				...DEFAULT_ATTRIBUTES.displayLayout,
				...blockVariationArgs.attributes?.displayLayout,
			},
			hideControls,
			queryContextIncludes:
				blockVariationArgs.attributes?.queryContextIncludes,
			collection: blockVariationArgs.name,
			inherit: false,
		},
		innerBlocks: blockVariationArgs.innerBlocks || INNER_BLOCKS_TEMPLATE,
		isActive,
		isDefault: false,
	} );
};
