/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/product-element-utils/register-product-block-type';

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
// @ts-expect-error metadata is not typed.
registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
