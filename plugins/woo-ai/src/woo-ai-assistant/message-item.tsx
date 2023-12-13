/**
 * External dependencies
 */
import React, { useCallback, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { Button } from '@wordpress/components';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Message } from './chat-modal';
import recordWooAIAssistantTracks from './utils';

type MessageItemProps = {
	message: Message;
	index: number;
	onRetry: ( index: number ) => void;
};

type FeedbackButtonProps = {
	type: FeedbackType;
};

type FeedbackType =
	| 'positive'
	| 'negative'
	| 'outdated_information'
	| 'inaccurate_answer'
	| 'did_not_answer_question';

type FeedbackState = {
	[ key: number ]: {
		type?: FeedbackType;
		submitted: boolean;
		showSpecificFeedback?: boolean;
	};
};

const MessageItem: React.FC< MessageItemProps > = ( {
	message,
	index,
	onRetry,
} ) => {
	const [ feedback, setFeedback ] = useState< FeedbackState >( {} );
	const [ feedbackButtonsRef ] = useAutoAnimate< HTMLDivElement >();

	const handlePositiveFeedback = useCallback( (): void => {
		setFeedback( {
			...feedback,
			[ index ]: { type: 'positive', submitted: true },
		} );
		recordWooAIAssistantTracks( 'feedback', {
			response: 'positive',
			message: message.text,
		} );
	}, [ feedback, index, message.text ] );

	const handleNegativeFeedback = useCallback( (): void => {
		setFeedback( {
			...feedback,
			[ index ]: { submitted: false, showSpecificFeedback: true },
		} );
	}, [ feedback, index ] );

	const submitSpecificNegativeFeedback = useCallback(
		( messageIndex: number, type: FeedbackType ): void => {
			setFeedback( {
				...feedback,
				[ messageIndex ]: { type, submitted: true },
			} );
			recordWooAIAssistantTracks( 'feedback', {
				response: type,
				message: message.text,
			} );
			// Automatically retry answering the request.
			onRetry( messageIndex );
		},
		[ feedback, onRetry, message.text ]
	);

	const RenderFeedbackButton = useCallback(
		( { type }: FeedbackButtonProps ) => {
			const isSubmitted = feedback[ index ]?.submitted;
			const feedbackType = feedback[ index ]?.type;

			if (
				isSubmitted &&
				( type === 'positive' ) !== ( feedbackType === 'positive' )
			) {
				// Don't render the button if the opposite feedback has been submitted
				return null;
			}

			const icon = type === 'positive' ? 'thumbs-up' : 'thumbs-down';
			const label =
				type === 'positive'
					? 'This was helpful.'
					: 'This was not helpful.';
			const handleClick =
				type === 'positive'
					? handlePositiveFeedback
					: handleNegativeFeedback;

			return (
				<Button
					showTooltip={ true }
					icon={ icon }
					label={ label }
					className={ `woo-ai-assistant-${ type }-feedback-button woo-ai-assistant-actions-button` }
					onClick={ handleClick }
					disabled={ isSubmitted }
				/>
			);
		},
		[ feedback, index, handleNegativeFeedback, handlePositiveFeedback ]
	);

	const RenderClipboardButton = useCallback( () => {
		return (
			<Button
				className="woo-ai-assistant-clipboard woo-ai-assistant-actions-button"
				showTooltip={ true }
				label={ __( 'Copy to clipboard', 'woocommerce' ) }
				icon="clipboard"
				onClick={ () => {
					navigator.clipboard.writeText( message.text );
				} }
			/>
		);
	}, [ message.text ] );

	const RenderSpecificFeedbackButtons = useCallback( () => {
		return (
			<div className="specific-feedback">
				<p className="feedback-prompt">
					{ __(
						'Please let me know why this was not helpful:',
						'woocommerce'
					) }
				</p>
				<div className="specific-feedback-buttons">
					<Button
						variant="tertiary"
						onClick={ () =>
							submitSpecificNegativeFeedback(
								index,
								'outdated_information'
							)
						}
					>
						{ __( 'Outdated Information', 'woocommerce' ) }
					</Button>
					<Button
						variant="tertiary"
						onClick={ () =>
							submitSpecificNegativeFeedback(
								index,
								'inaccurate_answer'
							)
						}
					>
						{ __( 'Incorrect Information', 'woocommerce' ) }
					</Button>
					<Button
						variant="tertiary"
						onClick={ () =>
							submitSpecificNegativeFeedback(
								index,
								'did_not_answer_question'
							)
						}
					>
						{ __( 'Did Not Answer My Question', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		);
	}, [ index, submitSpecificNegativeFeedback ] );

	const RenderRetryButton = useCallback( () => {
		return (
			<Button
				showTooltip={ true }
				className="woo-ai-assistant-actions-button"
				icon="redo"
				label={ __( 'Retry', 'woocommerce' ) }
				onClick={ () => {
					onRetry( index );
				} }
			/>
		);
	}, [ index ] );

	return (
		<>
			<li
				key={ message.timestamp }
				className={ `message ${ message.sender }` }
			>
				<ReactMarkdown>{ message.text }</ReactMarkdown>
			</li>
			{ message.sender === 'assistant' && (
				<div className="woo-ai-assistant-actions">
					<div
						className="feedback-buttons"
						ref={ feedbackButtonsRef }
					>
						<RenderClipboardButton />
						<RenderFeedbackButton type={ 'positive' } />
						<RenderFeedbackButton type={ 'negative' } />
						<RenderRetryButton />
						{ feedback[ index ]?.showSpecificFeedback && (
							<RenderSpecificFeedbackButtons />
						) }
					</div>
				</div>
			) }
		</>
	);
};

export default MessageItem;
