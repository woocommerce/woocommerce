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
import { Edit } from './edit';
import { Save } from './save';
import icon from './icon';
import type { ProductGalleryBlockAttributes } from './types';

const blockConfig = {
	...metadata,
	icon,
	edit: Edit,
	save: Save,
};

registerProductBlockType(
	blockConfig as BlockConfiguration< ProductGalleryBlockAttributes >,
	{
		isAvailableOnPostEditor: true,
	}
);
