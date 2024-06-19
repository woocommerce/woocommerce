/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface ShippingClassBlockAttributes extends BlockAttributes {
	title: string;
	disabled?: boolean;
}
