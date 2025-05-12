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
import { isNumber } from '@woocommerce/types';

/**
 * Settings for template-restricted block registration.
 *
 * @typedef {Object} TemplateRestrictedBlockSettings
 * @property {boolean}  [isVariationBlock]        - Whether this block is a variation
 * @property {string}   [variationName]           - The name of the variation if applicable
 * @property {boolean}  [isAvailableOnPostEditor] - Whether the block should be available in post editor
 * @property {string[]} [templates]               - Array of template IDs where this block is available
 */
type TemplateRestrictedBlockSettings = {
	isVariationBlock?: boolean;
	variationName?: string | undefined;
	isAvailableOnPostEditor?: boolean;
	templates?: string[];
};

/**
 * Internal block config type used by the TemplateRestrictedBlockRegistrationManager
 *
 * @typedef {Object} TemplateRestrictedBlockConfig
 * @template T Extends BlockAttributes to define the block's attribute types
 * @property {string}                          blockName     - The name of the block
 * @property {Partial<BlockConfiguration>}     settings      - Block settings configuration
 * @property {TemplateRestrictedBlockSettings} blockSettings - Block settings
 */
type TemplateRestrictedBlockConfig< T extends BlockAttributes > =
	TemplateRestrictedBlockSettings & {
		blockName: string;
		settings: Partial< BlockConfiguration< T > >;
	};

/**
 * Configuration object for registering a template-restricted block type.
 *
 * @typedef {Object} TemplateRestrictedBlockRegistrationConfig
 * @template T Extends BlockAttributes to define the block's attribute types
 * @property {Partial<BlockConfiguration>} settings                - Block settings configuration
 * @property {boolean}                     [isVariationBlock]      - Whether this block is a variation
 * @property {string}                      [variationName]         - The name of the variation if applicable
 * @property {boolean}                     isAvailableOnPostEditor - Whether the block should be available in post editor
 * @property {string[]}                    [templates]             - Array of template IDs where this block is available
 */
type TemplateRestrictedBlockRegistrationConfig< T extends BlockAttributes > =
	Partial< BlockConfiguration< T > > & TemplateRestrictedBlockSettings;

/**
 * Manages block registration and unregistration for template-restricted blocks in different contexts.
 * Implements the Singleton pattern to ensure consistent block management across the application.
 */
export class TemplateRestrictedBlockRegistrationManager {
	/** Singleton instance of the manager */
	private static instance: TemplateRestrictedBlockRegistrationManager;
	/** Map storing block configurations keyed by block name or variation name */
	private blocks: Map<
		string,
		TemplateRestrictedBlockConfig< BlockAttributes >
	> = new Map();
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
	 * Gets the singleton instance of the TemplateRestrictedBlockRegistrationManager.
	 * Creates the instance if it doesn't exist.
	 *
	 * @return {TemplateRestrictedBlockRegistrationManager} The singleton instance
	 */
	public static getInstance(): TemplateRestrictedBlockRegistrationManager {
		if ( ! TemplateRestrictedBlockRegistrationManager.instance ) {
			TemplateRestrictedBlockRegistrationManager.instance =
				new TemplateRestrictedBlockRegistrationManager();
		}
		return TemplateRestrictedBlockRegistrationManager.instance;
	}

	/**
	 * Parses a template ID from various possible formats.
	 * Handles both string and number inputs due to Gutenberg changes.
	 *
	 * @param {string | number | undefined} templateId - The template ID to parse
	 * @return {string | undefined} The parsed template ID
	 */
	private parseTemplateId(
		templateId: string | number | undefined
	): string | undefined {
		const parsedTemplateId = isNumber( templateId )
			? undefined
			: templateId;
		return parsedTemplateId?.split( '//' )[ 1 ];
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
			const editSiteStore = select( 'core/edit-site' );
			const editPostStore = select( 'core/edit-post' );

			// Return if neither store is available yet
			if ( ! editSiteStore && ! editPostStore ) {
				return;
			}

			// Site Editor Context
			if ( editSiteStore ) {
				const postId = editSiteStore.getEditedPostId();

				// Unsubscribe from the main subscription since we've detected our context
				unsubscribe();

				// Set initial template ID
				this.currentTemplateId =
					typeof postId === 'string'
						? this.parseTemplateId( postId )
						: undefined;

				// Set up the template change listener
				subscribe( () => {
					const previousTemplateId = this.currentTemplateId;
					this.currentTemplateId = this.parseTemplateId(
						editSiteStore.getEditedPostId()
					);

					if ( previousTemplateId !== this.currentTemplateId ) {
						this.handleTemplateChange( previousTemplateId );
					}
				}, 'core/edit-site' );

				this.initialized = true;
			}
			// Post Editor Context
			else if ( editPostStore ) {
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
			}
		} );
	}

	/**
	 * Handles block registration/unregistration when template context changes.
	 * Registers and unregisters blocks based on template restrictions.
	 *
	 * @param {string | undefined} previousTemplateId - The previous template ID
	 */
	private handleTemplateChange(
		previousTemplateId: string | undefined
	): void {
		// Unregister blocks from previous template that aren't allowed in current template
		this.blocks.forEach( ( config ) => {
			const { templates } = config;

			// Skip blocks that don't have template restrictions
			if ( ! templates || templates.length === 0 ) {
				return;
			}

			const wasAllowedInPrevious =
				! previousTemplateId ||
				templates.includes( previousTemplateId );
			const isAllowedInCurrent =
				! this.currentTemplateId ||
				templates.includes( this.currentTemplateId );

			// Template availability changed - unregister and potentially re-register
			if ( wasAllowedInPrevious !== isAllowedInCurrent ) {
				this.unregisterBlock( config );

				if ( isAllowedInCurrent ) {
					this.registerBlock( config );
				}
			}
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
	 * @param {TemplateRestrictedBlockConfig<T>} config - Configuration of the block to unregister
	 */
	private unregisterBlock< T extends BlockAttributes >(
		config: TemplateRestrictedBlockConfig< T >
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
	 * @param {TemplateRestrictedBlockConfig<T>} config - Configuration of the block to register
	 */
	private registerBlock< T extends BlockAttributes >(
		config: TemplateRestrictedBlockConfig< T >
	): void {
		const {
			blockName,
			settings,
			isVariationBlock,
			variationName,
			isAvailableOnPostEditor,
			templates,
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

			// Check if block is restricted to specific templates
			if ( templates && templates.length > 0 && this.currentTemplateId ) {
				// Don't register if current template isn't in allowed templates list
				if ( ! templates.includes( this.currentTemplateId ) ) {
					return;
				}
			}

			if ( isVariationBlock ) {
				registerBlockVariation(
					blockName,
					settings as BlockVariation< BlockAttributes >
				);
			} else {
				// Register block with appropriate settings
				// @ts-expect-error - blockName can be either string or object
				registerBlockType( blockName, {
					...settings,
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
	 * @param {TemplateRestrictedBlockConfig<T>} config - Configuration of the block to register
	 */
	public registerBlockConfig< T extends BlockAttributes >(
		config: TemplateRestrictedBlockConfig< T >
	): void {
		const key = config.variationName || config.blockName;
		this.blocks.set(
			key,
			config as TemplateRestrictedBlockConfig< BlockAttributes >
		);
		this.registerBlock( config );
	}
}

/**
 * Registers a block type that's restricted to specific templates and optionally makes it available
 * in the post editor. This function serves as the main entry point for registering template-specific blocks.
 *
 * The function uses the TemplateRestrictedBlockRegistrationManager singleton to handle the actual registration process,
 * which includes:
 * - Managing block registration across different editor contexts (site editor vs post editor)
 * - Handling template-specific block constraints
 * - Managing block variations if specified
 * - Preventing duplicate registrations
 *
 * By default, blocks registered through this function will only be available in the specified templates.
 * The `isAvailableOnPostEditor` flag can be used to make the block available in regular post editing
 * contexts as well.
 *
 * @template T Extends BlockAttributes to define the block's attribute types
 * @param {string | Partial<BlockConfiguration<T>>}                          blockNameOrMetadata - Either a string block name or block metadata object
 * @param {TemplateRestrictedBlockRegistrationConfig<BlockConfiguration<T>>} [settings]          - Optional settings for block registration
 * @return {void}
 *
 * @example
 * ```typescript
 * registerTemplateRestrictedBlockType({
 *     name: 'my-namespace/my-block',
 *     title: 'My Block',
 *     category: 'widgets',
 *     edit: () => <div>Block Editor</div>,
 *     save: () => <div>Saved Block</div>
 * }, {
 *     isAvailableOnPostEditor: true,
 *     templates: ['single-product', 'archive-product']
 * });
 * ```
 */
export const registerTemplateRestrictedBlockType = <
	T extends BlockAttributes
>(
	blockNameOrMetadata: string | Partial< BlockConfiguration< T > >,
	settings?: TemplateRestrictedBlockRegistrationConfig<
		BlockConfiguration< T >
	>
): void => {
	const blockName =
		typeof blockNameOrMetadata === 'string'
			? blockNameOrMetadata
			: blockNameOrMetadata.name;

	if ( ! blockName ) {
		// eslint-disable-next-line no-console
		console.error(
			'registerTemplateRestrictedBlockType: Block name is required for registration'
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
		templates,
		...settingsWithoutCustomProperties
	} = {
		...metaDataWithoutName,
		...( settings || {} ),
	};

	const internalConfig: TemplateRestrictedBlockConfig< T > = {
		blockName,
		settings: {
			...settingsWithoutCustomProperties,
		} as BlockConfiguration< T >,
		isVariationBlock: isVariationBlock ?? false,
		variationName: variationName ?? undefined,
		isAvailableOnPostEditor: isAvailableOnPostEditor ?? false,
		templates: templates ?? [],
	};

	TemplateRestrictedBlockRegistrationManager.getInstance().registerBlockConfig(
		internalConfig
	);
};
