/**
 * External dependencies
 */
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import attributes from './attributes';
import { supports } from './supports';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';

const blockConfig = {
	...sharedConfig,
	title,
	description,
	usesContext: [ 'query', 'queryId', 'postId' ],
	icon: { src: icon },
	attributes,
	supports,
	edit,
};

registerBlockSingleProductTemplate( {
	blockName: 'woocommerce/product-price',
	blockSettings: blockConfig,
	isAvailableOnPostEditor: true,
} );
