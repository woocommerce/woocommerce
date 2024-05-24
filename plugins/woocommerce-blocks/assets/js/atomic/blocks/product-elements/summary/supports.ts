/**
 * External dependencies
 */
import { isExperimentalBlockStylingEnabled } from '@woocommerce/block-settings';

export const supports = {
	...( isExperimentalBlockStylingEnabled() && {
		color: {
			background: false,
		},
		typography: {
			fontSize: true,
		},
		__experimentalSelector: '.wc-block-components-product-summary',
	} ),
};
