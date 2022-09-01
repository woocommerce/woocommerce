/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { hasSpacingStyleSupport } from '@woocommerce/utils';

/**
 * Internal dependencies
 */

export const supports = {
	html: false,
	...( isFeaturePluginBuild() && {
		__experimentalBorder: {
			radius: true,
			__experimentalSkipSerialization: true,
		},
		typography: {
			fontSize: true,
			__experimentalSkipSerialization: true,
		},
		...( hasSpacingStyleSupport() && {
			spacing: {
				margin: true,
				__experimentalSkipSerialization: true,
			},
		} ),
		__experimentalSelector: '.wc-block-components-product-image',
	} ),
};
