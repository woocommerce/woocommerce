/**
 * External dependencies
 */
import { use, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EmailEditorConfig, EmailTheme, storeName } from './index';
import { unwrapCompressedPresetStyleVariable } from '../style-variables';

/**
 * Function to generate the root container styles based on the config.
 * As of Gutenberg 22.0 we can no longer override styles directly so we are sending additional dynamic CSS via theme's css property
 */
const generateRootContainerStyles = ( config ) => {
	const layout = config.editorSettings?.__experimentalFeatures?.layout;
	const baseTheme = config.theme;
	const userTheme = select(
		storeName
	).getGlobalEmailStylesPost() as unknown as EmailTheme;
	const maxWidth = layout?.contentSize || '100%';
	let rootContainerStyles = `display:flow-root; max-width: ${ maxWidth }; margin: 0 auto;box-sizing: border-box;`;
	const padding =
		userTheme?.styles?.spacing?.padding ||
		baseTheme.styles?.spacing?.padding;
	if ( padding ) {
		rootContainerStyles += `padding-left:${ unwrapCompressedPresetStyleVariable(
			padding.left
		) };`;
		rootContainerStyles += `padding-right:${ unwrapCompressedPresetStyleVariable(
			padding.right
		) };`;
	}
	return `.is-root-container{ ${ rootContainerStyles } }`;
};

// Global variables to track the active config, if the overrides were initialized and if the overrides are active
let activeConfig: EmailEditorConfig | null = null;
let overridesInitialized = false;
let overridesActive = false;

/**
 * We wrap the core store selectors to return the global styles post id and email base theme from the email editor config.
 * As of Gutenberg 22.0 we can no longer override styles directly via a prop see https://github.com/WordPress/gutenberg/pull/72681/files#diff-da0dfea2139990db95c1ff4cae9f222aef66ae8d3dafb6237953d0c98c63fb64
 *
 * @param config - The configuration object containing the global styles post id and email base theme.
 */
export const initStoreOverrides = ( config ) => {
	// Set the active config and mark the overrides as initialized and active
	activeConfig = config;
	overridesActive = true;
	// Activate overrides only once
	if ( overridesInitialized ) {
		return;
	}

	overridesInitialized = true;
	// Initialize the overrides by wrapping the core store selectors
	use( ( registry ) => ( {
		select( store ) {
			const base = registry.select( store );

			// When the overrides were deactivated we return the base selectors to avoid our effects
			if ( ! overridesActive ) {
				return base;
			}

			if ( store.name === 'core' ) {
				return {
					...base,
					// Override the base function to return the global styles post id from the config
					__experimentalGetCurrentGlobalStylesId() {
						// Run the original function to run resolver and return overridden value after resolution is done
						const baseGlobalStylesId =
							base.__experimentalGetCurrentGlobalStylesId();
						if ( ! baseGlobalStylesId ) {
							return null;
						}
						return activeConfig.globalStylesPostId;
					},
					// Override the base function to return email base theme
					__experimentalGetCurrentThemeBaseGlobalStyles() {
						// Run the original function to run resolver and return overridden value after resolution is done
						const baseTheme =
							base.__experimentalGetCurrentThemeBaseGlobalStyles();
						if ( ! baseTheme ) {
							return null;
						}
						const theme = {
							...activeConfig.theme,
							styles: {
								...activeConfig.theme.styles,
								css: generateRootContainerStyles(
									activeConfig
								),
							},
						};
						return theme;
					},
				};
			}
			return base;
		},
	} ) );
};

/**
 * We cannot fully remove the overrides as they are used by the email editor to generate the CSS for the email editor content.
 * However, we can deactivate them by setting the overridesActive flag to false.
 */
export const deactivateStoreOverrides = () => {
	overridesActive = false;
};
