/**
 * External dependencies
 */
import type {
	Notice as NoticeType,
	Options as NoticeOptions,
} from '@wordpress/notices';

export interface StoreNoticesContainerProps {
	className?: string | undefined;
	context: string;
	// List of additional notices that were added inline and not stored in the `core/notices` store.
	additionalNotices?: ( NoticeType & NoticeOptions )[];
}

export type StoreNotice = NoticeType & NoticeOptions;
