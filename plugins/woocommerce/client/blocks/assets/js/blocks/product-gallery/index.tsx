/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import icon from './icon';
import './inner-blocks/product-gallery-next-previous-buttons';
import './inner-blocks/product-gallery-thumbnails';

const blockConfig = {
	...metadata,
	icon,
	edit: Edit,
	save: Save,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
