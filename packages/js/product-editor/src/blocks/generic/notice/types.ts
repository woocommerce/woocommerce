/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface NoticeBlockAttributes extends BlockAttributes {
	message: string;
}
