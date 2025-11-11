import { use as useData } from '@wordpress/data';

/**
 * We wrap the core store selectors to return the global styles post id and email base theme from the email editor config.
 * As of Gutenberg 22.0 we can no longer override styles directly via a prop see https://github.com/WordPress/gutenberg/pull/72681/files#diff-da0dfea2139990db95c1ff4cae9f222aef66ae8d3dafb6237953d0c98c63fb64
 * @param config - The configuration object containing the global styles post id and email base theme.
 */
export const initStoreOverrides = ( config ) => {
	useData( ( registry ) => ( {
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
						return config.theme;
					},
				};
			}
			return base;
		},
	} ) );
};
