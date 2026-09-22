/**
 * External dependencies
 */
import { box as icon } from '@wordpress/icons';
import { registerProductBlockType } from '@woocommerce/product-element-utils';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';

const blockConfig = {
	...metadata,
	icon: { src: icon },
	edit,
	save,
	isAvailableOnPostEditor: false,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: false,
} );
