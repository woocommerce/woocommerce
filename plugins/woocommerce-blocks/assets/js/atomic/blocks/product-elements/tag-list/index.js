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
import { Save } from './save';
import { supports } from './supports';

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

registerExperimentalBlockType( 'woocommerce/product-tag-list', {
	...sharedConfig,
	...blockConfig,
} );
