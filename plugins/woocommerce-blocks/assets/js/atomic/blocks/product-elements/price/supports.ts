/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';

export const supports = {
	...sharedConfig.supports,
	...( isFeaturePluginBuild() && {
		color: {
			text: true,
			background: false,
			link: false,
			__experimentalSkipSerialization: true,
		},
		typography: {
			fontSize: true,
			__experimentalFontWeight: true,
			__experimentalFontStyle: true,
			__experimentalSkipSerialization: true,
		},
		__experimentalSelector: '.wc-block-components-product-price',
	} ),
};
