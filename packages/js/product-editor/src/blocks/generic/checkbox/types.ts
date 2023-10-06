/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface CheckboxBlockAttributes extends BlockAttributes {
	property: string;
	title?: string;
	label?: string;
	tooltip?: string;
	onValue?: string | null;
	offValue?: string | null;
}
