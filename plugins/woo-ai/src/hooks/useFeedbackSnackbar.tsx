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
		createNotice( 'success', label, {
			type: 'snackbar',
			actions: [
				{
					variant: 'secondary',
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore
					label: createInterpolateElement(
						'<ThumbsUp /> <ThumbsDown />',
						{
							ThumbsUp: (
								<span
									className="woo-ai-feedback-snackbar_positive"
									data-response="positive"
								>
									👍
								</span>
							),
							ThumbsDown: (
								<span
									className="woo-ai-feedback-snackbar_negative"
									data-response="negative"
								>
									👎
								</span>
							),
						}
					),
					onClick: ( e ) => {
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
