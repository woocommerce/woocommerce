/**
 * External dependencies
 */
import { NoticeProps } from '@wordpress/components/build-types/notice/types';

export type UpgradeDowngradeNoticeProps = Omit< NoticeProps, 'actions' > & {
	actionLabel: string;
	onActionClick(): void;
};
