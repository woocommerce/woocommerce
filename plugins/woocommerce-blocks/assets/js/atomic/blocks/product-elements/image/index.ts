/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';

import { supports } from './supports';
import attributes from './attributes';
import sharedConfig from '../shared/config';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	name: 'woocommerce/product-image',
	title,
	icon: { src: icon },
	keywords: [ 'WooCommerce' ],
	description,
	usesContext: [ 'query', 'queryId', 'postId' ],
	textdomain: 'woocommerce',
	attributes,
	supports,
	edit,
};

registerBlockType( 'woocommerce/product-image', { ...blockConfig } );
