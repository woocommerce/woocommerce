/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/utils/register-product-block-type';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import icon from './icon';

const blockConfig = {
	...metadata,
	icon,
	edit,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
