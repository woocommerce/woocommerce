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

const isValidCollectionConfig = ( config: ProductCollectionConfig ) => {
	// Basic checks for the top-level argument
	if ( typeof config !== 'object' || config === null ) {
		console.warn(
			'Invalid arguments: You must pass an object to __experimentalRegisterProductCollection.'
		);
	}

	// BlockVariation properties
	if ( typeof config.name !== 'string' || config.name.length === 0 ) {
		console.warn( 'Invalid name: name must be a non-empty string.' );
	}
	if (
		config.title !== undefined &&
		( typeof config.title !== 'string' || config.title.length === 0 )
	) {
		console.warn( 'Invalid title: title must be a non-empty string.' );
	}
	if (
		config.description !== undefined &&
		typeof config.description !== 'string'
	) {
		console.warn( 'Invalid description: description must be a string.' );
	}
	if (
		config.category !== undefined &&
		typeof config.category !== 'string'
	) {
		console.warn( 'Invalid category: category must be a string.' );
	}
	if ( config.keywords !== undefined && ! Array.isArray( config.keywords ) ) {
		console.warn(
			'Invalid keywords: keywords must be an array of strings.'
		);
	}
	if (
		config.icon !== undefined &&
		typeof config.icon !== 'string' &&
		typeof config.icon !== 'object'
	) {
		console.warn( 'Invalid icon: icon must be a string or an object.' );
	}
	if ( config.example !== undefined && typeof config.example !== 'object' ) {
		console.warn( 'Invalid example: example must be an object.' );
	}
	if ( config.scope !== undefined && ! Array.isArray( config.scope ) ) {
		console.warn(
			'Invalid scope: scope must be an array of type WPBlockVariationScope.'
		);
	}

	/**
	 * Attributes validation
	 */
	if (
		config.attributes !== undefined &&
		typeof config.attributes !== 'object'
	) {
		console.warn( 'Invalid attributes: attributes must be an object.' );
	}
	if (
		config.attributes?.query !== undefined &&
		typeof config.attributes.query !== 'object'
	) {
		console.warn( 'Invalid query: query must be an object.' );
	}
	// attributes.query.offset
	if (
		config.attributes?.query?.offset !== undefined &&
		typeof config.attributes.query.offset !== 'number'
	) {
		console.warn( 'Invalid offset: offset must be a number.' );
	}
	// attributes.query.order
	if (
		config.attributes?.query?.order !== undefined &&
		typeof config.attributes.query.order !== 'string'
	) {
		console.warn( 'Invalid order: order must be a string.' );
	}
	// attributes.query.orderBy
	if (
		config.attributes?.query?.orderBy !== undefined &&
		typeof config.attributes.query.orderBy !== 'string'
	) {
		console.warn( 'Invalid orderBy: orderBy must be a string.' );
	}
	// attributes.query.pages
	if (
		config.attributes?.query?.pages !== undefined &&
		typeof config.attributes.query.pages !== 'number'
	) {
		console.warn( 'Invalid pages: pages must be a number.' );
	}
	// attributes.query.perPage
	if (
		config.attributes?.query?.perPage !== undefined &&
		typeof config.attributes.query.perPage !== 'number'
	) {
		console.warn( 'Invalid perPage: perPage must be a number.' );
	}
	// attributes.query.search
	if (
		config.attributes?.query?.search !== undefined &&
		typeof config.attributes.query.search !== 'string'
	) {
		console.warn( 'Invalid search: search must be a string.' );
	}
	// attributes.query.taxQuery
	if (
		config.attributes?.query?.taxQuery !== undefined &&
		typeof config.attributes.query.taxQuery !== 'object'
	) {
		console.warn( 'Invalid taxQuery: taxQuery must be an object.' );
	}
	// attributes.query.featured
	if (
		config.attributes?.query?.featured !== undefined &&
		typeof config.attributes.query.featured !== 'boolean'
	) {
		console.warn( 'Invalid featured: featured must be a boolean.' );
	}
	// attributes.query.timeFrame
	if (
		config.attributes?.query?.timeFrame !== undefined &&
		typeof config.attributes.query.timeFrame !== 'object'
	) {
		console.warn( 'Invalid timeFrame: timeFrame must be an object.' );
	}
	// attributes.query.woocommerceOnSale
	if (
		config.attributes?.query?.woocommerceOnSale !== undefined &&
		typeof config.attributes.query.woocommerceOnSale !== 'boolean'
	) {
		console.warn(
			'Invalid woocommerceOnSale: woocommerceOnSale must be a boolean.'
		);
	}
	// attributes.query.woocommerceStockStatus
	if (
		config.attributes?.query?.woocommerceStockStatus !== undefined &&
		! Array.isArray( config.attributes.query.woocommerceStockStatus )
	) {
		console.warn(
			'Invalid woocommerceStockStatus: woocommerceStockStatus must be an array.'
		);
	}
	// attributes.query.woocommerceAttributes
	if (
		config.attributes?.query?.woocommerceAttributes !== undefined &&
		! Array.isArray( config.attributes.query.woocommerceAttributes )
	) {
		console.warn(
			'Invalid woocommerceAttributes: woocommerceAttributes must be an array.'
		);
	}
	// attributes.query.woocommerceHandPickedProducts
	if (
		config.attributes?.query?.woocommerceHandPickedProducts !== undefined &&
		! Array.isArray( config.attributes.query.woocommerceHandPickedProducts )
	) {
		console.warn(
			'Invalid woocommerceHandPickedProducts: woocommerceHandPickedProducts must be an array.'
		);
	}
	// attributes.query.priceRange
	if (
		config.attributes?.query?.priceRange !== undefined &&
		typeof config.attributes.query.priceRange !== 'object'
	) {
		console.warn( 'Invalid priceRange: priceRange must be an object.' );
	}
	// attributes.queryId - queryId is generated automatically
	if ( config.attributes?.queryId !== undefined ) {
		console.warn(
			'Invalid queryId: queryId must not be set as it will be generated automatically.'
		);
	}
	// attributes.displayLayout
	if (
		config.attributes?.displayLayout !== undefined &&
		typeof config.attributes.displayLayout !== 'object'
	) {
		console.warn(
			'Invalid displayLayout: displayLayout must be an object.'
		);
	}
	// attributes.collection - It will be derived from args.name
	if ( config.attributes?.collection !== undefined ) {
		console.error(
			'Invalid collection: collection must not be set as it will be automatically derived from config.name.'
		);
		return false;
	}
	// attributes.hideControls
	if (
		config.attributes?.hideControls !== undefined &&
		! Array.isArray( config.attributes.hideControls )
	) {
		console.warn(
			'Invalid hideControls: hideControls must be an array of strings.'
		);
	}
	// attributes.queryContextIncludes
	if (
		config.attributes?.queryContextIncludes !== undefined &&
		! Array.isArray( config.attributes.queryContextIncludes )
	) {
		console.warn(
			'Invalid queryContextIncludes: queryContextIncludes must be an array of strings.'
		);
	}

	// Preview validation
	if ( config.preview !== undefined ) {
		if ( typeof config.preview !== 'object' || config.preview === null ) {
			console.warn( 'Invalid preview: preview must be an object.' );
		}
		if (
			config.preview.setPreviewState !== undefined &&
			typeof config.preview.setPreviewState !== 'function'
		) {
			console.warn(
				'Invalid preview: setPreviewState must be a function.'
			);
		}
		if ( config.preview.initialPreviewState !== undefined ) {
			if ( typeof config.preview.initialPreviewState !== 'object' ) {
				console.warn(
					'Invalid preview: initialPreviewState must be an object.'
				);
			}
			if (
				typeof config.preview.initialPreviewState.isPreview !==
				'boolean'
			) {
				console.warn(
					'Invalid preview: preview.isPreview must be a boolean.'
				);
			}
			if (
				typeof config.preview.initialPreviewState.previewMessage !==
				'string'
			) {
				console.warn(
					'Invalid preview: preview.previewMessage must be a string.'
				);
			}
		}
	}
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
	// Check if the config is valid. It will log warnings in the console if the config is invalid.
	isValidCollectionConfig( config );

	const { preview: { setPreviewState, initialPreviewState } = {} } = config;

	const isActive = (
		blockAttrs: BlockAttributes,
		variationAttributes: BlockAttributes
	) => {
		return blockAttrs.collection === variationAttributes.collection;
	};

	const query = config.attributes?.query || {};
	/**
	 * As we don't allow collections to change "inherit" attribute,
	 * We always need to hide the inherit control.
	 */
	const hideControls = [
		...new Set( [
			CoreFilterNames.INHERIT,
			...( config.attributes?.hideControls || [] ),
		] ),
	];
	const collectionConfigWithoutExtraArgs = {
		name: config.name,
		title: config.title,
		description: config.description,
		category: config.category,
		keywords: config.keywords,
		icon: config.icon,
		example: config.example,
		scope: config.scope,
		attributes: {
			query: {
				offset: query.offset,
				order: query.order,
				orderBy: query.orderBy,
				pages: query.pages,
				perPage: query.perPage,
				search: query.search,
				taxQuery: query.taxQuery,
				featured: query.featured,
				timeFrame: query.timeFrame,
				woocommerceOnSale: query.woocommerceOnSale,
				woocommerceStockStatus: query.woocommerceStockStatus,
				woocommerceAttributes: query.woocommerceAttributes,
				woocommerceHandPickedProducts:
					query.woocommerceHandPickedProducts,
				priceRange: query.priceRange,
			},
			displayLayout: config.attributes?.displayLayout,
			hideControls,
			queryContextIncludes: config.attributes?.queryContextIncludes,
			// collection should be set to the name of the collection i.e. config.name
			collection: config.name,
			// Collections should always have inherit set to false.
			inherit: false,
		},
		/**
		 * We always want following properties to be set to the default values.
		 */
		innerBlocks: config.innerBlocks || INNER_BLOCKS_TEMPLATE,
		isActive,
		isDefault: false,
	} as BlockVariation;

	/**
	 * If setPreviewState or initialPreviewState is provided, inject the setPreviewState & initialPreviewState props.
	 * This is useful for handling preview mode in the editor.
	 */
	if ( setPreviewState || initialPreviewState ) {
		const withSetPreviewState =
			< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
			( props: BlockEditProps< ProductCollectionAttributes > ) => {
				// If collection name does not match, return the original BlockEdit component.
				if (
					props.attributes.collection !==
					collectionConfigWithoutExtraArgs.name
				) {
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
			collectionConfigWithoutExtraArgs.name,
			withSetPreviewState
		);
	}

	registerBlockVariation( BLOCK_NAME, {
		...collectionConfigWithoutExtraArgs,
		attributes: {
			...DEFAULT_ATTRIBUTES,
			...collectionConfigWithoutExtraArgs.attributes,
			query: {
				...DEFAULT_QUERY,
				...collectionConfigWithoutExtraArgs.attributes?.query,
			},
			displayLayout: {
				...DEFAULT_ATTRIBUTES.displayLayout,
				...collectionConfigWithoutExtraArgs.attributes?.displayLayout,
			},
		},
	} );
};
