/**
 * External dependencies
 */
import {
	isFeaturePluginBuild,
	registerExperimentalBlockType,
} from '@woocommerce/block-settings';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from './../shared/config';
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
	icon: { src: icon },
	attributes,
	usesContext: [ 'query', 'queryId', 'postId' ],
	ancestor: [
		'@woocommerce/all-products',
		'@woocommerce/single-product',
		'core/post-template',
	],
	supports: {
		...( isFeaturePluginBuild() && {
			color: {
				text: true,
				link: true,
				background: false,
			},
			typography: {
				fontSize: true,
				lineHeight: true,
				__experimentalFontStyle: true,
				__experimentalFontWeight: true,
				__experimentalSkipSerialization: true,
			},
			__experimentalSelector:
				'.wc-block-components-product-category-list',
		} ),
	},
	edit,
};

registerExperimentalBlockType(
	'woocommerce/product-category-list',
	blockConfig
);
