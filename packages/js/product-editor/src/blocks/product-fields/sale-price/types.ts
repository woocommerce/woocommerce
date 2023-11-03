/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface SalePriceBlockAttributes extends BlockAttributes {
	label: string;
	help?: string;
}
