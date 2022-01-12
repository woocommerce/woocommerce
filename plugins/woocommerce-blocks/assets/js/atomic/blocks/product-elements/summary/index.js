/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import attributes from './attributes';
import edit from './edit';
import { supports } from './supports';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';
import { Save } from './save';

const blockConfig = {
	apiVersion: 2,
	title,
	description,
	icon: { src: icon },
	attributes,
	supports,
	edit,
	save: Save,
};

registerBlockType( 'woocommerce/product-summary', {
	...sharedConfig,
	...blockConfig,
} );
