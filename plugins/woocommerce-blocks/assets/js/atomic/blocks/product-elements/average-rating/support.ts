/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { isExperimentalBlockStylingEnabled } from '@woocommerce/block-settings';

export const supports = {
	...( isExperimentalBlockStylingEnabled() && {
		color: {
			text: true,
			background: true,
			__experimentalSkipSerialization: true,
		},
		spacing: {
			margin: true,
			padding: true,
			__experimentalSkipSerialization: true,
		},
		typography: {
			fontSize: true,
			__experimentalFontWeight: true,
			__experimentalSkipSerialization: true,
		},
		__experimentalSelector: '.wc-block-components-product-average-rating',
	} ),
};
