/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface NoticeBlockAttributes extends BlockAttributes {
	id: string;
	content: string;
	title: string;
	type: 'error-type' | 'success' | 'warning' | 'info';
}
