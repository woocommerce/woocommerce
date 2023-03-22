/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

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

const { ancestor, ...configuration } = sharedConfig;

const blockConfig: BlockConfiguration = {
	...configuration,
	apiVersion: 2,
	title,
	description,
	icon: { src: icon },
	usesContext: [ 'query', 'queryId', 'postId' ],
	attributes,
	ancestor: [
		'woocommerce/all-products',
		'woocommerce/single-product',
		'core/post-template',
		'woocommerce/product-meta',
	],
	edit,
};

registerBlockType( 'woocommerce/product-sku', { ...blockConfig } );
