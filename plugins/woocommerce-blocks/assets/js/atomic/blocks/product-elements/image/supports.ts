/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { hasSpacingStyleSupport } from '../../../../utils/global-style';

export const supports = {
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
