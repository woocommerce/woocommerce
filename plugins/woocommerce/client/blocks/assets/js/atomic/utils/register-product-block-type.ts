/**
 * External dependencies
 */
import {
	BlockAttributes,
	BlockVariation,
	registerBlockType,
	registerBlockVariation,
	unregisterBlockType,
	unregisterBlockVariation,
	BlockConfiguration,
} from '@wordpress/blocks';
import { subscribe, select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { isEmpty } from '@woocommerce/types';

/**
 * Settings for product block registration.
 *
 * @typedef {Object} ProductBlockSettings
 * @property {boolean} [isVariationBlock]        - Whether this block is a variation
 * @property {string}  [variationName]           - The name of the variation if applicable
 * @property {boolean} [isAvailableOnPostEditor] - Whether the block should be available in post editor
 */
type ProductBlockSettings = {
	isVariationBlock?: boolean;
	variationName?: string | undefined;
	isAvailableOnPostEditor?: boolean;
};

/**
 * Internal block config type used by the BlockRegistrationManager
 *
 * @typedef {Object} ProductBlockConfig
 * @template T Extends BlockAttributes to define the block's attribute types
 * @property {string}                      blockName            - The name of the block
 * @property {Partial<BlockConfiguration>} settings             - Block settings configuration
 * @property {ProductBlockSettings}        productBlockSettings - Product block settings
 */
type ProductBlockConfig< T extends BlockAttributes > = ProductBlockSettings & {
	blockName: string;
	settings: Partial< BlockConfiguration< T > >;
};

/**
 * Configuration object for registering a product block type.
 *
 * @typedef {Object} ProductBlockRegistrationConfig
 * @template T Extends BlockAttributes to define the block's attribute types
 * @property {Partial<BlockConfiguration>} settings                - Block settings configuration
 * @property {boolean}                     [isVariationBlock]      - Whether this block is a variation
 * @property {string}                      [variationName]         - The name of the variation if applicable
 * @property {boolean}                     isAvailableOnPostEditor - Whether the block should be available in post editor
 */
type ProductBlockRegistrationConfig< T extends BlockAttributes > = Partial<
	BlockConfiguration< T >
> &
	ProductBlockSettings;

/**
 * Manages block registration and unregistration for WooCommerce product blocks in different contexts.
 * Implements the Singleton pattern to ensure consistent block management across the application.
 */
export class BlockRegistrationManager {
	/** Singleton instance of the manager */
	private static instance: BlockRegistrationManager;
	/** Map storing block configurations keyed by block name or variation name */
	private blocks: Map< string, ProductBlockConfig< BlockAttributes > > =
		new Map();
	/** Current template ID being edited */
	private currentTemplateId: string | undefined;
	/** Flag indicating if the manager has been initialized */
	private initialized = false;
	/** Set to track block registration attempts to prevent duplicate registration attempts */
	private attemptedRegisteredBlocks: Set< string > = new Set();

	/**
	 * Private constructor to enforce singleton pattern.
	 * Initializes subscriptions for template changes.
	 */
	private constructor() {
		this.initializeSubscriptions();
	}

	/**
	 * Gets the singleton instance of the BlockRegistrationManager.
	 * Creates the instance if it doesn't exist.
	 *
	 * @return {BlockRegistrationManager} The singleton instance
	 */
	public static getInstance(): BlockRegistrationManager {
		if ( ! BlockRegistrationManager.instance ) {
			BlockRegistrationManager.instance = new BlockRegistrationManager();
		}
		return BlockRegistrationManager.instance;
	}

	/**
	 * Initializes subscriptions for template changes and block registration.
	 * Sets up listeners for both the site editor and post editor contexts.
	 */
	private initializeSubscriptions(): void {
		if ( this.initialized ) {
			return;
		}

		// Main store subscription to detect which editor we're in
		const unsubscribe = subscribe( () => {
			const editorSelectors = select( editorStore );

			// Return if editor store is not available yet
			if ( ! editorSelectors ) {
				return;
			}

			// @ts-expect-error getCurrentPostType is not typed
			const postType = editorSelectors.getCurrentPostType();

			// Return if post type is not available yet
			if ( ! postType ) {
				return;
			}

			// Post Editor Context (Posts, Pages)
			if ( postType === 'post' || postType === 'page' ) {
				// Unsubscribe from the main subscription since we've detected our context
				unsubscribe();

				// Register only blocks available in post editor
				this.blocks.forEach( ( config ) => {
					if ( config.isAvailableOnPostEditor ) {
						const key = config.variationName || config.blockName;
						if ( ! this.hasAttemptedRegistration( key ) ) {
							this.registerBlock( config );
						}
					}
				} );

				this.initialized = true;
				// Site Editor Context (Templates, Patterns, etc.)
			} else {
				// Unsubscribe from the main subscription since we've detected our context
				unsubscribe();

				// getEditedPostSlug may return string or number so we cast it to string.
				// @ts-expect-error getEditedPostSlug is not typed
				const postSlug = String( editorSelectors.getEditedPostSlug() );

				// Set initial template ID
				this.currentTemplateId = postSlug;

				// Handle the initial template change
				this.handleTemplateChange( undefined );

				// Set up the template change listener
				subscribe( () => {
					const previousTemplateId = this.currentTemplateId;
					this.currentTemplateId =
						// getEditedPostSlug may return string or number so we cast it to string.
						// @ts-expect-error getEditedPostSlug is not typed
						String( editorSelectors.getEditedPostSlug() );

					if ( previousTemplateId !== this.currentTemplateId ) {
						this.handleTemplateChange( previousTemplateId );
					}
				}, editorStore );

				this.initialized = true;
			}
		} );
	}

	/**
	 * Handles block registration/unregistration when template context changes.
	 *
	 * Note: This implementation is currently coupled to the 'single-product' template,
	 * as it's our only use case where blocks need different ancestor constraints.
	 * If we need to handle more templates in the future, this should be refactored
	 * to be more generic.
	 *
	 * @param {string | undefined} previousTemplateId - The previous template ID
	 */
	private handleTemplateChange(
		previousTemplateId: string | undefined
	): void {
		const isTransitioningToOrFromSingleProduct =
			this.currentTemplateId?.includes( 'single-product' ) ||
			previousTemplateId?.includes( 'single-product' );

		if ( ! isTransitioningToOrFromSingleProduct ) {
			return;
		}

		this.blocks.forEach( ( config ) => {
			// When the template changes, we need to unregister and register all blocks that are available on the new template
			// Unregistering the block will remove it from the `hasAttemptedRegistration` set, so we can register it again
			this.unregisterBlock( config );
			this.registerBlock( config );
		} );
	}

	/**
	 * Checks if a block has already been attempted to be registered.
	 *
	 * @param {string} blockKey - The key of the block to check
	 * @return {boolean} Whether the block has already been attempted to be registered
	 */
	private hasAttemptedRegistration( blockKey: string ): boolean {
		return this.attemptedRegisteredBlocks.has( blockKey );
	}

	/**
	 * Unregisters a block or block variation.
	 * Handles both regular blocks and variations with error handling.
	 *
	 * @template T The type of block attributes
	 * @param {ProductBlockConfig<T>} config - Configuration of the block to unregister
	 */
	private unregisterBlock< T extends BlockAttributes >(
		config: ProductBlockConfig< T >
	): void {
		const { blockName, isVariationBlock, variationName } = config;

		try {
			if ( isVariationBlock && variationName ) {
				unregisterBlockVariation( blockName, variationName );
				this.attemptedRegisteredBlocks.delete( variationName );
			} else {
				unregisterBlockType( blockName );
				this.attemptedRegisteredBlocks.delete( blockName );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.debug(
				`Failed to unregister block ${ blockName }:`,
				error
			);
		}
	}

	/**
	 * Registers a block or block variation.
	 * Handles different registration requirements for various contexts.
	 * Includes checks to prevent recursive registration.
	 *
	 * @template T The type of block attributes
	 * @param {ProductBlockConfig<T>} config - Configuration of the block to register
	 */
	private registerBlock< T extends BlockAttributes >(
		config: ProductBlockConfig< T >
	): void {
		const {
			blockName,
			settings,
			isVariationBlock,
			variationName,
			isAvailableOnPostEditor,
		} = config;

		try {
			// Check if block is already registered
			const key = variationName || blockName;
			if ( this.hasAttemptedRegistration( key ) ) {
				return;
			}

			const editSiteStore = select( 'core/edit-site' );

			// Don't register if we're in post editor context and block isn't available there
			if ( ! editSiteStore && ! isAvailableOnPostEditor ) {
				return;
			}

			if ( isVariationBlock ) {
				registerBlockVariation(
					blockName,
					settings as BlockVariation< BlockAttributes >
				);
			} else {
				const ancestor = isEmpty( settings?.ancestor )
					? [ 'woocommerce/single-product' ]
					: settings?.ancestor;

				// Only remove ancestor if we're in site editor AND in single-product template
				const shouldRemoveAncestor =
					editSiteStore &&
					this.currentTemplateId?.includes( 'single-product' );

				// @ts-expect-error - blockName can be either string or object
				registerBlockType( blockName, {
					...settings,
					ancestor: shouldRemoveAncestor ? undefined : ancestor,
				} );
			}

			this.attemptedRegisteredBlocks.add( key );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( `Failed to register block ${ blockName }:`, error );
		}
	}

	/**
	 * Registers a new block configuration with the manager.
	 * Main entry point for adding new blocks to be managed.
	 *
	 * @template T The type of block attributes
	 * @param {ProductBlockConfig<T>} config - Configuration of the block to register
	 */
	public registerBlockConfig< T extends BlockAttributes >(
		config: ProductBlockConfig< T >
	): void {
		const key = config.variationName || config.blockName;
		this.blocks.set( key, config as ProductBlockConfig< BlockAttributes > );
		this.registerBlock( config );
	}
}

/**
 * Registers a block type specifically for WooCommerce product templates and optionally makes it available
 * in the post editor. This function serves as the main entry point for registering product-related blocks.
 *
 * This function is specifically designed for blocks that require a product context to function properly.
 * For example, blocks like 'product-price', 'product-title', or 'product-rating' only make sense when they
 * have access to product data. These blocks should not be used in templates or block areas where no product
 * context is defined, as they won't have access to the necessary product information to render meaningful content.
 * The registration system enforces this by default by setting appropriate ancestor constraints.
 *
 * The function uses the BlockRegistrationManager singleton to handle the actual registration process,
 * which includes:
 * - Managing block registration across different editor contexts (site editor vs post editor)
 * - Handling template-specific block constraints
 * - Managing block variations if specified
 * - Preventing duplicate registrations
 *
 * By default, blocks registered through this function will be available in the single product template
 * with no ancestor constraints. The `isAvailableOnPostEditor` flag can be used to make
 * the block available in regular post editing contexts as well where ancestor constraints are enforced.
 *
 * @template T Extends BlockAttributes to define the block's attribute types
 * @param {string | Partial<BlockConfiguration<T>>}               blockNameOrMetadata - Either a string block name or block metadata object
 * @param {ProductBlockRegistrationConfig<BlockConfiguration<T>>} [settings]          - Optional settings for block registration
 * @return {void}
 *
 * @example
 * ```typescript
 * registerProductBlockType({
 *     name: 'woocommerce/product-price',
 *     title: 'Product Price',
 *     category: 'woocommerce',
 *     edit: () => <div>Price Editor</div>,
 *     save: () => <div>Saved Price</div>
 * }, {
 *     isAvailableOnPostEditor: true
 * });
 * ```
 */
export const registerProductBlockType = < T extends BlockAttributes >(
	blockNameOrMetadata: string | Partial< BlockConfiguration< T > >,
	settings?: ProductBlockRegistrationConfig< BlockConfiguration< T > >
): void => {
	const blockName =
		typeof blockNameOrMetadata === 'string'
			? blockNameOrMetadata
			: blockNameOrMetadata.name;

	if ( ! blockName ) {
		// eslint-disable-next-line no-console
		console.error(
			'registerProductBlockType: Block name is required for registration'
		);
		return;
	}

	// If blockNameOrMetadata is an object, use all its properties except 'name' as settings
	const metaDataWithoutName =
		typeof blockNameOrMetadata === 'string'
			? {}
			: // eslint-disable-next-line @typescript-eslint/no-unused-vars
			  ( ( { name, ...metadata } ) => metadata )( blockNameOrMetadata );

	// Extract settings without custom properties
	const {
		isVariationBlock,
		variationName,
		isAvailableOnPostEditor,
		...settingsWithoutCustomProperties
	} = {
		...metaDataWithoutName,
		...( settings || {} ),
	};

	const internalConfig: ProductBlockConfig< T > = {
		blockName,
		settings: {
			...settingsWithoutCustomProperties,
		} as BlockConfiguration< T >,
		isVariationBlock: isVariationBlock ?? false,
		variationName: variationName ?? undefined,
		isAvailableOnPostEditor: isAvailableOnPostEditor ?? false,
	};

	BlockRegistrationManager.getInstance().registerBlockConfig(
		internalConfig
	);
};
