/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';

export const supports = {
	html: false,
	align: true,
	...( isFeaturePluginBuild() && {
		color: {
			gradients: true,
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
			__experimentalTextTransform: true,
			__experimentalTextDecoration: true,
		},
		__experimentalBorder: {
			color: true,
			radius: true,
			width: true,
			__experimentalSkipSerialization: true,
		},
		// @todo: Improve styles support when WordPress 6.4 is released. https://make.wordpress.org/core/2023/07/17/introducing-the-block-selectors-api/
		...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
			spacing: {
				margin: true,
				padding: true,
			},
		} ),
		__experimentalSelector: '.wc-block-components-product-sale-badge',
	} ),
	...( typeof __experimentalGetSpacingClassesAndStyles === 'function' &&
		! isFeaturePluginBuild() && {
			spacing: {
				margin: true,
			},
		} ),
};
