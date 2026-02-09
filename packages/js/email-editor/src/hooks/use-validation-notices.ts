/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

export type NoticeAction = {
	label: string;
	onClick?: React.MouseEventHandler< HTMLButtonElement >;
};

export type Notice = {
	id?: string;
	title?: string;
	content?: string;
	className?: string;
	type?: string;
	children?: React.ReactNode;
	isDismissible?: boolean;
	handleDismiss?: () => void;
	actions?: NoticeAction[];
};

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
