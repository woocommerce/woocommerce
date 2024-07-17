/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';

export const supports = {
	color: {
		text: true,
		background: false,
		link: false,
		__experimentalSkipSerialization: true,
	},
	spacing: {
		margin: true,
		padding: true,
	},
	typography: {
		fontSize: true,
		__experimentalSkipSerialization: true,
	},
	__experimentalSelector: '.wc-block-components-product-rating',
	...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
		spacing: {
			margin: true,
		},
	} ),
};
