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
import { Save } from './save';
import { supports } from './support';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	title,
	description,
	icon: { src: icon },
	apiVersion: 2,
	supports,
	attributes,
	edit,
	save: Save,
};

registerBlockType( 'woocommerce/product-sale-badge', { ...blockConfig } );
