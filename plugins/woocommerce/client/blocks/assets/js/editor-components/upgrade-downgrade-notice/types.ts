/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';

export type UpgradeDowngradeNoticeProps = Omit< Notice.Props, 'actions' > & {
	actionLabel: string;
	onActionClick(): void;
};
