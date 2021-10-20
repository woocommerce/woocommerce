/**
 * External dependencies
 */
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

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

registerExperimentalBlockType( 'woocommerce/product-sku', {
	...sharedConfig,
	...blockConfig,
} );
