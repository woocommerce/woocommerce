/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import type {
	WPNotice,
	WPNoticeAction,
} from '@wordpress/notices/build-types/store/selectors';

export type ValidationNoticesData = {
	notices: WPNotice[];
	hasValidationNotice: ( noticeId?: string ) => boolean;
	addValidationNotice: (
		noticeId: string,
		message: string,
		actions?: WPNoticeAction[]
	) => void;
	removeValidationNotice: ( noticeId: string ) => void;
};

export const useValidationNotices = (): ValidationNoticesData => {
	const context = 'email-validation';
	const notices = useSelect( ( mapSelect ) =>
		mapSelect( noticesStore ).getNotices( context )
	);

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
			( noticeId: string, message: string, actions = [] ): void => {
				void dispatch( noticesStore ).createNotice( 'error', message, {
					id: noticeId,
					isDismissible: false,
					actions,
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
