/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import attributes from './attributes';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';

const blockConfig = {
	title,
	description,
	icon: {
		src: icon,
		foreground: '#7f54b3',
	},
	attributes,
	edit,
};

registerBlockType( 'woocommerce/product-price', {
	...sharedConfig,
	...blockConfig,
} );
