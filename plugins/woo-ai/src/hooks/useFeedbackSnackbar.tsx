/**
 * External dependencies
 */
import React from 'react';
import { useDispatch } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';

type ShowSnackbarProps = {
	label: string;
	onPositiveResponse: () => void;
	onNegativeResponse: () => void;
};

export const useFeedbackSnackbar = () => {
	const { createNotice } = useDispatch( 'core/notices' );

	const showSnackbar = ( {
		label,
		onPositiveResponse,
		onNegativeResponse,
	}: ShowSnackbarProps ) => {
		createNotice( 'info', label, {
			type: 'snackbar',
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
	};

	return { showSnackbar };
};
