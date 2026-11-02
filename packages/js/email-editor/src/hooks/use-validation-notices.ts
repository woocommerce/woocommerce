/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import type {
	NoticeAction,
	NoticeListProps,
} from '@wordpress/components/build-types/notice/types';
import type { WPNoticeAction } from '@wordpress/notices/build-types/store/actions';

// Re-export the canonical shapes from `@wordpress/components` so callers of
// `addValidationNotice` use the same discriminated-union `NoticeAction` that
// `<NoticeList>` accepts. `Notice` is the single-element shape that
// `<NoticeList>` renders, which lines up with the narrowed projection of the
// notices store's return.
export type { NoticeAction };
export type Notice = NoticeListProps[ 'notices' ][ number ];

export type ValidationNoticesData = {
	notices: Notice[];
	hasValidationNotice: ( noticeId?: string ) => boolean;
	addValidationNotice: (
		noticeId: string,
		message: string,
		actions?: NoticeAction[]
	) => void;
	removeValidationNotice: ( noticeId: string ) => void;
};

export const useValidationNotices = (): ValidationNoticesData => {
	const context = 'email-validation';
	const storeNotices = useSelect(
		( mapSelect ) => mapSelect( noticesStore ).getNotices( context ),
		[]
	);
	// `WPNotice.status: string` and `WPNoticeAction.onClick: Function` are
	// wider than `<NoticeList>`'s element shape. At runtime the notices store
	// only ever emits values inside the narrower union; cast once here so
	// downstream code stays fully typed.
	const notices = storeNotices as unknown as Notice[];

	return {
		notices,
		hasValidationNotice: useCallback(
			( noticeId?: string ): boolean => {
				if ( ! noticeId ) {
					return notices?.length > 0;
				}

				return (
					notices.find( ( notice ) => notice.id === noticeId ) !==
					undefined
				);
			},
			[ notices ]
		),
		addValidationNotice: useCallback(
			(
				noticeId: string,
				message: string,
				actions: NoticeAction[] = []
			): void => {
				// The notices store's `WPNoticeAction` is the same shape as
				// `NoticeAction` at runtime, but its JSDoc-derived type widens
				// `onClick` to `Function` and makes `url` required (allowing
				// `null`). Cast at the boundary so the store sees its expected
				// shape.
				const storeActions = actions as unknown as WPNoticeAction[];
				void dispatch( noticesStore ).createNotice( 'error', message, {
					id: noticeId,
					isDismissible: false,
					actions: storeActions,
					context,
				} );
			},
			[ context ]
		),
		removeValidationNotice: useCallback(
			( noticeId: string ): void => {
				void dispatch( noticesStore ).removeNotice( noticeId, context );
			},
			[ context ]
		),
	};
};
