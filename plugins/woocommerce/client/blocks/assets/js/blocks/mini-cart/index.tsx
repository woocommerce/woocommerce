/**
 * External dependencies
 */
import { miniCartAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

const featurePluginSupport = {
	...metadata.supports,
	typography: {
		...metadata.supports.typography,
		__experimentalFontFamily: true,
		__experimentalFontWeight: true,
	},
};

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ miniCartAlt }
				className="wc-block-editor-components-block-icon wc-block-editor-mini-cart__icon"
			/>
		),
	},
	supports: {
		...featurePluginSupport,
	},
	example: {
		...metadata.example,
	},
	attributes: {
		...metadata.attributes,
	},
	edit,
	save() {
		return null;
	},
} );
