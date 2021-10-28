/**
 * External dependencies
 */
import { registerBlockType, BlockConfiguration } from '@wordpress/blocks';
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

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	apiVersion: 2,
	title,
	description,
	icon: {
		src: icon,
		foreground: '#7f54b3',
	},
	attributes,
	edit,
	supports: isFeaturePluginBuild()
		? {
				html: false,
				color: {
					background: false,
				},
				typography: {
					fontSize: true,
				},
		  }
		: sharedConfig.supports,
};

registerBlockType( 'woocommerce/product-title', blockConfig );
