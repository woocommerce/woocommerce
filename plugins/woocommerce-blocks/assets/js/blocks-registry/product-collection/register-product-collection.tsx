/* eslint-disable no-console */
/**
 * External dependencies
 */
import { BlockVariation } from '@wordpress/blocks';
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
	usesReference?: string[];
}

/**
 * Validates the configuration object of new collection. This function checks
 * whether the provided config object adheres to the required schema and conditions necessary
 * for a valid collection.
 *
 * Each validation step may log errors or warnings to the console if the corresponding property
 * does not meet the expected criteria. It will bail early and return false, if any of the
 * required properties are missing or invalid.
 */
const isValidCollectionConfig = ( config: ProductCollectionConfig ) => {
	// Basic checks for the top-level argument
	if ( typeof config !== 'object' || config === null ) {
		console.error(
			'Invalid arguments: You must pass an object to __experimentalRegisterProductCollection.'
		);
		return false;
	}

	/**
	 * BlockVariation properties validation
	 */
	// name
	if ( typeof config.name !== 'string' || config.name.length === 0 ) {
		console.error( 'Invalid name: name must be a non-empty string.' );
		return false;
	} else if (
		! config.name.match(
			/^[a-zA-Z0-9-]+\/product-collection\/[a-zA-Z0-9-]+$/
		)
	) {
		console.warn(
			`To prevent conflicts with other collections, please use a unique name following the pattern: "<plugin-name>/product-collection/<collection-name>". Ensure "<plugin-name>" is your plugin name and "<collection-name>" is your collection name. Both should consist only of alphanumeric characters and hyphens (e.g., "my-plugin/product-collection/my-collection").`
		);
	}
	// title
	if ( typeof config.title !== 'string' || config.title.length === 0 ) {
		console.error( 'Invalid title: title must be a non-empty string.' );
		return false;
	}
	// description
	if (
		config.description !== undefined &&
		typeof config.description !== 'string'
	) {
		console.warn( 'Invalid description: description must be a string.' );
	}
	// category
	if (
		config.category !== undefined &&
		typeof config.category !== 'string'
	) {
		console.warn( 'Invalid category: category must be a string.' );
	}
	// keywords
	if ( config.keywords !== undefined && ! Array.isArray( config.keywords ) ) {
		console.warn(
			'Invalid keywords: keywords must be an array of strings.'
		);
	}
	// icon
	if (
		config.icon !== undefined &&
		typeof config.icon !== 'string' &&
		typeof config.icon !== 'object'
	) {
		console.warn( 'Invalid icon: icon must be a string or an object.' );
	}
	// example
	if ( config.example !== undefined && typeof config.example !== 'object' ) {
		console.warn( 'Invalid example: example must be an object.' );
	}
	// scope
	if ( config.scope !== undefined && ! Array.isArray( config.scope ) ) {
		console.warn(
			'Invalid scope: scope must be an array of type WPBlockVariationScope.'
		);
	}

	/**
	 * Attributes validation
	 */
	// attributes
	if (
		config.attributes !== undefined &&
		typeof config.attributes !== 'object'
	) {
		console.warn( 'Invalid attributes: attributes must be an object.' );
	}
	// attributes.query
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
	// attributes.displayLayout
	if (
		config.attributes?.displayLayout !== undefined &&
		typeof config.attributes.displayLayout !== 'object'
	) {
		console.warn(
			'Invalid displayLayout: displayLayout must be an object.'
		);
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

	/**
	 * Preview validation
	 */
	if ( config.preview !== undefined ) {
		// preview
		if ( typeof config.preview !== 'object' || config.preview === null ) {
			console.warn( 'Invalid preview: preview must be an object.' );
		}
		// preview.setPreviewState
		if (
			config.preview.setPreviewState !== undefined &&
			typeof config.preview.setPreviewState !== 'function'
		) {
			console.warn(
				'Invalid preview: setPreviewState must be a function.'
			);
		}

		if ( config.preview.initialPreviewState !== undefined ) {
			// preview.initialPreviewState
			if ( typeof config.preview.initialPreviewState !== 'object' ) {
				console.warn(
					'Invalid preview: initialPreviewState must be an object.'
				);
			}
			// preview.initialPreviewState.isPreview
			if (
				typeof config.preview.initialPreviewState.isPreview !==
				'boolean'
			) {
				console.warn(
					'Invalid preview: preview.isPreview must be a boolean.'
				);
			}
			// preview.initialPreviewState.previewMessage
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

	// usesReference
	if (
		config.usesReference !== undefined &&
		! Array.isArray( config.usesReference )
	) {
		console.error(
			'Invalid usesReference: usesReference must be an array of strings.'
		);
		return false;
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
	// If the config is invalid, return early.
	if ( ! isValidCollectionConfig( config ) ) {
		console.error(
			'Collection could not be registered due to invalid configuration.'
		);
		return;
	}

	const {
		preview: { setPreviewState, initialPreviewState } = {},
		usesReference,
	} = config;

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
				...DEFAULT_QUERY,
				...( query.offset !== undefined && { offset: query.offset } ),
				...( query.order !== undefined && { order: query.order } ),
				...( query.orderBy !== undefined && {
					orderBy: query.orderBy,
				} ),
				...( query.pages !== undefined && { pages: query.pages } ),
				...( query.perPage !== undefined && {
					perPage: query.perPage,
				} ),
				...( query.search !== undefined && { search: query.search } ),
				...( query.taxQuery !== undefined && {
					taxQuery: query.taxQuery,
				} ),
				...( query.featured !== undefined && {
					featured: query.featured,
				} ),
				...( query.timeFrame !== undefined && {
					timeFrame: query.timeFrame,
				} ),
				...( query.woocommerceOnSale !== undefined && {
					woocommerceOnSale: query.woocommerceOnSale,
				} ),
				...( query.woocommerceStockStatus !== undefined && {
					woocommerceStockStatus: query.woocommerceStockStatus,
				} ),
				...( query.woocommerceAttributes !== undefined && {
					woocommerceAttributes: query.woocommerceAttributes,
				} ),
				...( query.woocommerceHandPickedProducts !== undefined && {
					woocommerceHandPickedProducts:
						query.woocommerceHandPickedProducts,
				} ),
				...( query.priceRange !== undefined && {
					priceRange: query.priceRange,
				} ),
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
	if (
		setPreviewState ||
		initialPreviewState ||
		( Array.isArray( usesReference ) && usesReference.length > 0 )
	) {
		/**
		 * This function is used to inject following props to the BlockEdit component:
		 * 1. preview: { setPreviewState, initialPreviewState }
		 * 2. usesReference
		 */
		const withAdditionalProps =
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
						// Inject preview prop only if setPreviewState or initialPreviewState is provided.
						{ ...( initialPreviewState || setPreviewState
							? {
									preview: {
										setPreviewState,
										initialPreviewState,
									},
							  }
							: {} ) }
						usesReference={ usesReference }
					/>
				);
			};
		addFilter(
			'editor.BlockEdit',
			collectionConfigWithoutExtraArgs.name,
			withAdditionalProps
		);
	}

	/**
	 * Temporarily utilizing `wp.blocks.registerBlockVariation` directly instead of importing
	 * from `@wordpress/blocks` to mitigate the increase in the number of JavaScript files
	 * loaded on the frontend, specifically on the /shop page.
	 *
	 * TODO - Future Improvement:
	 * It is recommended to encapsulate the `registerProductCollection` function within a new
	 * package that is exclusively loaded in the editor. This strategy will eliminate
	 * the need to directly use `wp.blocks.registerBlockVariation`.
	 */
	if ( wp?.blocks?.registerBlockVariation ) {
		wp.blocks.registerBlockVariation( BLOCK_NAME, {
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
					...collectionConfigWithoutExtraArgs.attributes
						?.displayLayout,
				},
			},
		} );
	}
};
