/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

export const supports = {
	...( isFeaturePluginBuild() && {
		__experimentalBorder: {
			radius: true,
		},
		typography: {
			fontSize: true,
		},
		__experimentalSelector: '.wc-block-components-product-image',
	} ),
};
