/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';

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

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	// Product Title is not expected to be available in Product Collection
	// nor Products (Beta). They use core/post-title variation.
	ancestor: [ 'woocommerce/all-products', 'woocommerce/single-product' ],
	title,
	description,
	icon: { src: icon },
	attributes,
	edit,
	save: Save,
	supports: {
		...sharedConfig.supports,
		typography: {
			fontSize: true,
			lineHeight: true,
			__experimentalFontWeight: true,
			__experimentalTextTransform: true,
			__experimentalFontFamily: true,
		},
		color: {
			text: true,
			background: true,
			link: false,
			gradients: true,
			__experimentalSkipSerialization: true,
		},
		...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
			spacing: {
				margin: true,
				__experimentalSkipSerialization: true,
			},
		} ),
		__experimentalSelector: '.wc-block-components-product-title',
	},
};

registerBlockType( 'woocommerce/product-title', blockConfig );
