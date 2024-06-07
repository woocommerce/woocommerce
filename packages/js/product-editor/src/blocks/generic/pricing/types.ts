/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface PricingBlockAttributes extends BlockAttributes {
	property: string;
	label: string;
	help?: string;
	tooltip?: string;
}
