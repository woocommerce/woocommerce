/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface SelectBlockAttributes extends BlockAttributes {
	property: string;
	options: { label: string; value: string }[];
}
