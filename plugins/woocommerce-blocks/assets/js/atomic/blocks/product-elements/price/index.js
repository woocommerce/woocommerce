/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { Save } from './save';
import attributes from './attributes';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';

const blockConfig = {
	...sharedConfig,
	apiVersion: 2,
	title,
	description,
	icon: { src: icon },
	attributes,
	edit,
	save: Save,
	supports: {
		...sharedConfig.supports,
		...( isFeaturePluginBuild() && {
			color: {
				text: true,
				background: false,
				link: false,
				__experimentalSkipSerialization: true,
			},
			typography: {
				fontSize: true,
				__experimentalFontWeight: true,
				__experimentalFontStyle: true,
				__experimentalSkipSerialization: true,
			},
			__experimentalSelector: '.wc-block-components-product-price',
		} ),
	},
};

registerBlockType( 'woocommerce/product-price', blockConfig );
