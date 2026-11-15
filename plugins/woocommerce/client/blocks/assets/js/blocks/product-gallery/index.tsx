/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/utils/register-product-block-type';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import icon from './icon';

const blockConfig = {
	...metadata,
	icon,
	edit: Edit,
	save: Save,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
