/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';

export const supports = {
	html: false,
	color: {
		text: true,
		background: true,
		link: false,
		__experimentalSkipSerialization: true,
	},
	typography: {
		fontSize: true,
		lineHeight: true,
		__experimentalFontFamily: true,
		__experimentalFontWeight: true,
		__experimentalFontStyle: true,
		__experimentalSkipSerialization: true,
		__experimentalLetterSpacing: true,
	},
	__experimentalSelector:
		'.wp-block-woocommerce-product-price .wc-block-components-product-price',
	...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
		spacing: {
			margin: true,
			padding: true,
		},
	} ),
};
