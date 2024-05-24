/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { isExperimentalBlockStylingEnabled } from '@woocommerce/block-settings';
import { __experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */

export const supports = {
	html: false,
	...( isExperimentalBlockStylingEnabled() && {
		__experimentalBorder: {
			radius: true,
			__experimentalSkipSerialization: true,
		},
		typography: {
			fontSize: true,
			__experimentalSkipSerialization: true,
		},
		...( typeof getSpacingClassesAndStyles === 'function' && {
			spacing: {
				margin: true,
				padding: true,
			},
		} ),
		__experimentalSelector: '.wc-block-components-product-image',
	} ),
};
