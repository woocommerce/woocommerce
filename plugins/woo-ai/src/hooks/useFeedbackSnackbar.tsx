/**
 * External dependencies
 */
import React from 'react';
import { createInterpolateElement, useState } from '@wordpress/element';
// TODO: Re-add "@types/wordpress__data" package to resolve this, causing other issues until pnpm 8.6.0 is usable
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
// eslint-disable-next-line @woocommerce/dependency-group
import { useDispatch } from '@wordpress/data';

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
