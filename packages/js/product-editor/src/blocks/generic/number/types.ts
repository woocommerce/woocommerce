/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface NumberBlockAttributes extends BlockAttributes {
	label: string;
	property: string;
	help?: string;
	suffix?: string;
	placeholder?: string;
}
