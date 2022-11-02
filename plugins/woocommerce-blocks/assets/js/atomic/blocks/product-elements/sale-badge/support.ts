/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { hasSpacingStyleSupport } from '../../../../utils/global-style';

export const supports = {
	html: false,
	...( isFeaturePluginBuild() && {
		color: {
			gradients: true,
			background: true,
			link: false,
			__experimentalSkipSerialization: true,
		},
		typography: {
			fontSize: true,
			__experimentalSkipSerialization: true,
		},
		__experimentalBorder: {
			color: true,
			radius: true,
			width: true,
			__experimentalSkipSerialization: true,
		},
		...( hasSpacingStyleSupport() && {
			spacing: {
				padding: true,
				__experimentalSkipSerialization: true,
			},
		} ),
		__experimentalSelector: '.wc-block-components-product-sale-badge',
	} ),
};
