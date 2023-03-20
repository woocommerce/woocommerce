/**
 * External dependencies
 */
import { BlockAlignment } from '@wordpress/blocks';

export type SummaryAttributes = {
	align: BlockAlignment;
	direction: 'ltr' | 'rtl';
	label: string;
};
