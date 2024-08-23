/**
 * External dependencies
 */
import {
	// @ts-expect-error We check if this exists before using it.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetSpacingClassesAndStyles,
} from '@wordpress/block-editor';

export const supports = {
	html: false,
	color: {
		text: true,
		background: true,
	},
	typography: {
		fontSize: true,
		lineHeight: true,
		__experimentalFontWeight: true,
		__experimentalFontFamily: true,
		__experimentalFontStyle: true,
		__experimentalTextTransform: true,
		__experimentalTextDecoration: true,
		__experimentalLetterSpacing: true,
	},
	...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
		spacing: {
			margin: true,
			padding: true,
		},
	} ),
};
