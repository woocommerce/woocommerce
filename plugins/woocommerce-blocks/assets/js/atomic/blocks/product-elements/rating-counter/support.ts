/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { isExperimentalBlockStylingEnabled } from '@woocommerce/block-settings';

export const supports = {
	...( isExperimentalBlockStylingEnabled() && {
		color: {
			text: false,
			background: false,
			link: true,
		},
		spacing: {
			margin: true,
			padding: true,
		},
		typography: {
			fontSize: true,
			__experimentalSkipSerialization: true,
		},
		__experimentalSelector: '.wc-block-components-product-rating-counter',
	} ),
};
