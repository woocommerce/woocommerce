/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import attributes from './attributes';
import edit from './edit';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';
import { Save } from './save';
import { hasSpacingStyleSupport } from '../../../../utils/global-style';

const blockConfig = {
	title,
	description,
	icon: { src: icon },
	apiVersion: 2,
	supports: {
		html: false,
		...( isFeaturePluginBuild() && {
			color: {
				gradients: true,
				background: true,
				link: false,
				__experimentalSkipSerialization: true,
			},
			typography: {
				fontSize: true,
				__experimentalSkipSerialization: true,
			},
			__experimentalBorder: {
				color: true,
				radius: true,
				width: true,
				__experimentalSkipSerialization: true,
			},
			...( hasSpacingStyleSupport() && {
				spacing: {
					padding: true,
					__experimentalSkipSerialization: true,
				},
			} ),
			__experimentalSelector: '.wc-block-components-product-sale-badge',
		} ),
	},
	attributes,
	edit,
	save: Save,
};

registerBlockType( 'woocommerce/product-sale-badge', {
	...sharedConfig,
	...blockConfig,
} );
