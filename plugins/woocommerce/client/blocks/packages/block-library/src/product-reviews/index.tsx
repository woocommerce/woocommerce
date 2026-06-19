/* eslint-disable import/no-extraneous-dependencies */
/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils/register-product-block-type';
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';
import edit from './edit';

const blockConfig = {
	...metadata,
	edit,
	save,
	deprecated: [
		{
			save() {
				return null;
			},
		},
	],
};

registerProductBlockType( blockConfig as BlockConfiguration, {
	isAvailableOnPostEditor: true,
} );
