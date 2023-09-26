/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface PricingBlockAttributes extends BlockAttributes {
	name: string;
	label: string;
	help?: string;
}
