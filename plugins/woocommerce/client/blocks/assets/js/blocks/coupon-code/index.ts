/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import { Save } from './save';
import type { CouponCodeAttributes } from './types';
import metadata from './block.json';

registerBlockType(
	metadata as unknown as BlockConfiguration< CouponCodeAttributes >,
	{
		edit: Edit,
		save: Save,
	}
);
