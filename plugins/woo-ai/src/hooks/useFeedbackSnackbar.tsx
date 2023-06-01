/**
 * External dependencies
 */
import React from 'react';
import { useDispatch } from '@wordpress/data';
import { createInterpolateElement, useState } from '@wordpress/element';

type ShowSnackbarProps = {
	label: string;
	onPositiveResponse: () => void;
	onNegativeResponse: () => void;
};

type NoticeItem = {
	notice: { id: string };
};

export const useFeedbackSnackbar = () => {
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );
	const [ noticeId, setNoticeId ] = useState< string | null >( null );

	const showSnackbar = async ( {
		label,
		onPositiveResponse,
		onNegativeResponse,
	}: ShowSnackbarProps ) => {
		const noticePromise: unknown = createNotice( 'info', label, {
			type: 'snackbar',
			explicitDismiss: true,
			actions: [
				{
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore
					label: createInterpolateElement(
						'<ThumbsUp /> <ThumbsDown />',
						{
							ThumbsUp: (
								<span
									className="woo-ai-feedback-snackbar-action"
									data-response="positive"
								>
									👍
								</span>
							),
							ThumbsDown: (
								<span
									className="woo-ai-feedback-snackbar-action"
									data-response="negative"
								>
									👎
								</span>
							),
						}
					),
					onClick: ( e: React.MouseEvent< HTMLButtonElement > ) => {
						const response = (
							e.target as HTMLSpanElement
						 ).getAttribute( 'data-response' );

						if ( response === 'positive' ) {
							onPositiveResponse();
						}

						if ( response === 'negative' ) {
							onNegativeResponse();
						}
					},
				},
			],
		} );

		( noticePromise as Promise< NoticeItem > ).then(
			( item: NoticeItem ) => {
				setNoticeId( item.notice.id );
			}
		);
		return noticePromise as Promise< NoticeItem >;
	};

	return {
		showSnackbar,
		removeSnackbar: () => {
			if ( noticeId ) {
				removeNotice( noticeId );
			}
		},
	};
};
