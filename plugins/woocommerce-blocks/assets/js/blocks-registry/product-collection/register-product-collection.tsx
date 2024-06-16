/* eslint-disable no-console */
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

const isValidProductCollectionConfig = ( config: ProductCollectionConfig ) => {
	// Basic checks for the top-level argument
	if ( typeof config !== 'object' || config === null ) {
		console.error(
			'Invalid arguments: You must pass an object to __experimentalRegisterProductCollection.'
		);
		return false;
	}

	// BlockVariation properties
	if ( typeof config.name !== 'string' || config.name.length === 0 ) {
		console.error( 'Invalid name: name must be a non-empty string.' );
		return false;
	}
	if (
		config.title !== undefined &&
		( typeof config.title !== 'string' || config.title.length === 0 )
	) {
		console.error( 'Invalid title: title must be a non-empty string.' );
		return false;
	}
	if (
		config.description !== undefined &&
		typeof config.description !== 'string'
	) {
		console.error( 'Invalid description: description must be a string.' );
		return false;
	}
	if (
		config.category !== undefined &&
		typeof config.category !== 'string'
	) {
		console.error( 'Invalid category: category must be a string.' );
		return false;
	}
	if ( config.keywords !== undefined && ! Array.isArray( config.keywords ) ) {
		console.error(
			'Invalid keywords: keywords must be an array of strings.'
		);
		return false;
	}
	if (
		config.icon !== undefined &&
		typeof config.icon !== 'string' &&
		typeof config.icon !== 'object'
	) {
		console.error( 'Invalid icon: icon must be a string or an object.' );
		return false;
	}
	if (
		config.isDefault !== undefined &&
		typeof config.isDefault !== 'boolean'
	) {
		console.error( 'Invalid isDefault: isDefault must be a boolean.' );
		return false;
	}
	if (
		config.innerBlocks !== undefined &&
		! Array.isArray( config.innerBlocks )
	) {
		console.error( 'Invalid innerBlocks: innerBlocks must be an array.' );
		return false;
	}
	if ( config.example !== undefined && typeof config.example !== 'object' ) {
		console.error( 'Invalid example: example must be an object.' );
		return false;
	}
	if ( config.scope !== undefined && ! Array.isArray( config.scope ) ) {
		console.error(
			'Invalid scope: scope must be an array of type WPBlockVariationScope.'
		);
		return false;
	}
	if (
		config.isActive !== undefined &&
		typeof config.isActive !== 'function'
	) {
		console.error( 'Invalid isActive: isActive must be a function.' );
		return false;
	}

	/**
	 * Attributes validation
	 */
	if (
		config.attributes !== undefined &&
		typeof config.attributes !== 'object'
	) {
		console.error( 'Invalid attributes: attributes must be an object.' );
		return false;
	}
	if (
		config.attributes?.query !== undefined &&
		typeof config.attributes.query !== 'object'
	) {
		console.error( 'Invalid query: query must be an object.' );
		return false;
	}
	//attributes. query.exclude
	if (
		config.attributes?.query?.exclude !== undefined &&
		! Array.isArray( config.attributes.query.exclude )
	) {
		console.error( 'Invalid exclude: exclude must be an array.' );
		return false;
	}
	// attributes.query.offset
	if (
		config.attributes?.query?.offset !== undefined &&
		typeof config.attributes.query.offset !== 'number'
	) {
		console.error( 'Invalid offset: offset must be a number.' );
		return false;
	}
	// attributes.query.order
	if (
		config.attributes?.query?.order !== undefined &&
		typeof config.attributes.query.order !== 'string'
	) {
		console.error( 'Invalid order: order must be a string.' );
		return false;
	}
	// attributes.query.orderBy
	if (
		config.attributes?.query?.orderBy !== undefined &&
		typeof config.attributes.query.orderBy !== 'string'
	) {
		console.error( 'Invalid orderBy: orderBy must be a string.' );
		return false;
	}
	// attributes.query.pages
	if (
		config.attributes?.query?.pages !== undefined &&
		typeof config.attributes.query.pages !== 'number'
	) {
		console.error( 'Invalid pages: pages must be a number.' );
		return false;
	}
	// attributes.query.perPage
	if (
		config.attributes?.query?.perPage !== undefined &&
		typeof config.attributes.query.perPage !== 'number'
	) {
		console.error( 'Invalid perPage: perPage must be a number.' );
		return false;
	}
	// attributes.query.search
	if (
		config.attributes?.query?.search !== undefined &&
		typeof config.attributes.query.search !== 'string'
	) {
		console.error( 'Invalid search: search must be a string.' );
		return false;
	}
	// attributes.query.taxQuery
	if (
		config.attributes?.query?.taxQuery !== undefined &&
		typeof config.attributes.query.taxQuery !== 'object'
	) {
		console.error( 'Invalid taxQuery: taxQuery must be an object.' );
		return false;
	}
	// attributes.query.featured
	if (
		config.attributes?.query?.featured !== undefined &&
		typeof config.attributes.query.featured !== 'boolean'
	) {
		console.error( 'Invalid featured: featured must be a boolean.' );
		return false;
	}
	// attributes.query.timeFrame
	if (
		config.attributes?.query?.timeFrame !== undefined &&
		typeof config.attributes.query.timeFrame !== 'object'
	) {
		console.error( 'Invalid timeFrame: timeFrame must be an object.' );
		return false;
	}
	// attributes.query.woocommerceOnSale
	if (
		config.attributes?.query?.woocommerceOnSale !== undefined &&
		typeof config.attributes.query.woocommerceOnSale !== 'boolean'
	) {
		console.error(
			'Invalid woocommerceOnSale: woocommerceOnSale must be a boolean.'
		);
		return false;
	}
	// attributes.query.woocommerceStockStatus
	if (
		config.attributes?.query?.woocommerceStockStatus !== undefined &&
		! Array.isArray( config.attributes.query.woocommerceStockStatus )
	) {
		console.error(
			'Invalid woocommerceStockStatus: woocommerceStockStatus must be an array.'
		);
		return false;
	}
	// attributes.query.woocommerceAttributes
	if (
		config.attributes?.query?.woocommerceAttributes !== undefined &&
		! Array.isArray( config.attributes.query.woocommerceAttributes )
	) {
		console.error(
			'Invalid woocommerceAttributes: woocommerceAttributes must be an array.'
		);
		return false;
	}
	// attributes.query.woocommerceHandPickedProducts
	if (
		config.attributes?.query?.woocommerceHandPickedProducts !== undefined &&
		! Array.isArray( config.attributes.query.woocommerceHandPickedProducts )
	) {
		console.error(
			'Invalid woocommerceHandPickedProducts: woocommerceHandPickedProducts must be an array.'
		);
		return false;
	}
	// attributes.query.priceRange
	if (
		config.attributes?.query?.priceRange !== undefined &&
		typeof config.attributes.query.priceRange !== 'object'
	) {
		console.error( 'Invalid priceRange: priceRange must be an object.' );
		return false;
	}
	// attributes.queryId - queryId is generated automatically
	if ( config.attributes?.queryId !== undefined ) {
		console.error(
			'Invalid queryId: queryId must not be set as it will be generated automatically.'
		);
		return false;
	}
	// attributes.displayLayout
	if (
		config.attributes?.displayLayout !== undefined &&
		typeof config.attributes.displayLayout !== 'object'
	) {
		console.error(
			'Invalid displayLayout: displayLayout must be an object.'
		);
		return false;
	}
	// attributes.convertedFromProducts - For internal use only
	if ( config.attributes?.convertedFromProducts !== undefined ) {
		console.error(
			'Invalid convertedFromProducts: convertedFromProducts must not be set.'
		);
		return false;
	}
	// attributes.collection - It will be set to args.name
	if ( config.attributes?.collection !== undefined ) {
		console.error(
			'Invalid collection: collection must not be set as it will be set automatically to config.name.'
		);
		return false;
	}
	// attributes.hideControls
	if (
		config.attributes?.hideControls !== undefined &&
		! Array.isArray( config.attributes.hideControls )
	) {
		console.error(
			'Invalid hideControls: hideControls must be an array of strings.'
		);
		return false;
	}
	// attributes.queryContextIncludes
	if (
		config.attributes?.queryContextIncludes !== undefined &&
		! Array.isArray( config.attributes.queryContextIncludes )
	) {
		console.error(
			'Invalid queryContextIncludes: queryContextIncludes must be an array of strings.'
		);
		return false;
	}
	// attributes.forcePageReload - For internal use only
	if ( config.attributes?.forcePageReload !== undefined ) {
		console.error(
			'Invalid forcePageReload: forcePageReload must not be set.'
		);
		return false;
	}
	// attributes.__privatePreviewState - For internal use only
	if ( config.attributes?.__privatePreviewState !== undefined ) {
		console.error(
			'Invalid __privatePreviewState: __privatePreviewState must not be set.'
		);
		return false;
	}

	// Preview validation
	if ( config.preview !== undefined ) {
		if ( typeof config.preview !== 'object' || config.preview === null ) {
			console.error( 'Invalid preview: preview must be an object.' );
			return false;
		}
		if (
			config.preview.setPreviewState !== undefined &&
			typeof config.preview.setPreviewState !== 'function'
		) {
			console.error(
				'Invalid preview: setPreviewState must be a function.'
			);
			return false;
		}
		if ( config.preview.initialPreviewState !== undefined ) {
			if ( typeof config.preview.initialPreviewState !== 'object' ) {
				console.error(
					'Invalid preview: initialPreviewState must be an object.'
				);
				return false;
			}
			if (
				typeof config.preview.initialPreviewState.isPreview !==
				'boolean'
			) {
				console.error(
					'Invalid preview: preview.isPreview must be a boolean.'
				);
				return false;
			}
			if (
				typeof config.preview.initialPreviewState.previewMessage !==
				'string'
			) {
				console.error(
					'Invalid preview: preview.previewMessage must be a string.'
				);
				return false;
			}
		}
	}

	return true;
};

/**
 * Register a new collection for the Product Collection block.
 *
 * 🚨🚨🚨 WARNING: This is an experimental API and is subject to change without notice.
 *
 * @param {ProductCollectionConfig} config The configuration of new collection.
 */
export const __experimentalRegisterProductCollection = (
	config: ProductCollectionConfig
) => {
	// Validate the configuration.
	if ( ! isValidProductCollectionConfig( config ) ) {
		return;
	}

	const {
		preview: { setPreviewState, initialPreviewState } = {},
		...blockVariationArgs
	} = config;

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
	const hideControls = [
		...new Set( [
			CoreFilterNames.INHERIT,
			...( blockVariationArgs.attributes?.hideControls || [] ),
		] ),
	];

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
