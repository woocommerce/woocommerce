/**
 * External dependencies
 */
import type { WPNotice } from '@wordpress/notices/build-types/store/selectors';

export type NoticeStatus = 'success' | 'error' | 'info' | 'warning' | 'default';
export interface NoticeType extends Partial< Omit< WPNotice, 'status' > > {
	id: string;
	content: string;
	status: NoticeStatus;
	isDismissible: boolean;
	context?: string | undefined;
}
