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
	// @ts-expect-error: block.json metadata types don't perfectly match BlockConfiguration, but WordPress handles this at runtime
	registerBlockType( metadata, {
		icon,
		edit: Edit,
		save: Save,
	} );
}
