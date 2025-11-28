/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { tag as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';

/**
 * Register the Coupon Code block.
 */
export function registerCouponCodeBlock() {
	registerBlockType( metadata.name, {
		...metadata,
		icon,
		edit: Edit,
		save: Save,
	} );
}
