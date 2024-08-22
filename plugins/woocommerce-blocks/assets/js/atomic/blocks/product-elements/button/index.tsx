/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';

const featurePluginSupport = {
	...metadata.supports,

	color: {
		text: true,
		background: true,
		link: false,
		__experimentalSkipSerialization: true,
	},
	__experimentalBorder: {
		radius: true,
		__experimentalSkipSerialization: true,
	},
	...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
		spacing: {
			margin: true,
			padding: true,
			__experimentalSkipSerialization: true,
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
			__experimentalDefaultControls: {
				fontSize: true,
			},
		},
		__experimentalSelector:
			'.wp-block-button.wc-block-components-product-button .wc-block-components-product-button__button',
	} ),
	...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
		spacing: {
			margin: true,
		},
	} ),
};
// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
registerBlockType( metadata, {
	apiVersion: 3,
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
	},
	supports: {
		...featurePluginSupport,
	},
	edit,
	save,
} );
