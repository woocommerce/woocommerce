/**
 * External dependencies
 */
import { use, select } from '@wordpress/data';
import deepmerge from 'deepmerge';

/**
 * Internal dependencies
 */
import { EmailStyles, EmailTheme, storeName } from './index';
import { unwrapCompressedPresetStyleVariable } from '../style-variables';
import { areExternalStylesSupported } from '../private-apis';

/**
 * Function to generate the root container styles based on the config.
 * As of Gutenberg 22.0 we can no longer override styles directly so we are sending additional dynamic CSS via theme's css property
 */
const generateRootContainerStyles = ( config ) => {
	const layout = config.editorSettings?.__experimentalFeatures?.layout;
	const baseTheme = config.theme;
	const userTheme = select(
		storeName
	).getGlobalEmailStylesPost() as EmailTheme;
	const userStyles = userTheme?.styles;
	const mergedStyles = deepmerge.all( [
		{},
		baseTheme.styles,
		userStyles,
	] ) as EmailStyles;
	const maxWidth = layout?.contentSize || '100%';
	let rootContainerStyles = `display:flow-root; max-width: ${ maxWidth }; margin: 0 auto;box-sizing: border-box;`;
	const padding = mergedStyles?.spacing?.padding;
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

/**
 * We wrap the core store selectors to return the global styles post id and email base theme from the email editor config.
 * As of Gutenberg 22.0 we can no longer override styles directly via a prop see https://github.com/WordPress/gutenberg/pull/72681/files#diff-da0dfea2139990db95c1ff4cae9f222aef66ae8d3dafb6237953d0c98c63fb64
 *
 * @param config - The configuration object containing the global styles post id and email base theme.
 */
export const initStoreOverrides = ( config ) => {
	// If the active version of Gutenberg supports external styles being passed to Editor component, we don't need to override the store.
	if ( areExternalStylesSupported ) {
		return;
	}
	use( ( registry ) => ( {
		select( store ) {
			const base = registry.select( store );
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
						return config.globalStylesPostId;
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
							...config.theme,
							styles: {
								...config.theme.styles,
								css: generateRootContainerStyles( config ),
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
