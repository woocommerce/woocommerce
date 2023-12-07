/**
 * External dependencies
 */
import React, { useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { Button } from '@wordpress/components';
import { useAutoAnimate } from '@formkit/auto-animate/react';

/**
 * Internal dependencies
 */
import { Message } from './chat-modal';

type MessageItemProps = {
	message: Message;
	index: number;
};

type FeedbackType = 'positive' | 'outdated' | 'incorrect' | 'not_answered';

type FeedbackState = {
	[ key: number ]: {
		type?: FeedbackType;
		submitted: boolean;
		showSpecificFeedback?: boolean;
	};
};

const MessageItem: React.FC< MessageItemProps > = ( { message, index } ) => {
	const [ feedback, setFeedback ] = useState< FeedbackState >( {} );
	const [ feedbackButtonsRef ] = useAutoAnimate< HTMLDivElement >();

	const handlePositiveFeedback = ( messageIndex: number ): void => {
		setFeedback( {
			...feedback,
			[ messageIndex ]: { type: 'positive', submitted: true },
		} );
		// Additional logic to send feedback to backend if required
	};

	const handleNegativeFeedback = ( messageIndex: number ): void => {
		setFeedback( {
			...feedback,
			[ messageIndex ]: { submitted: false, showSpecificFeedback: true },
		} );
	};

	const submitSpecificFeedback = (
		messageIndex: number,
		type: FeedbackType
	): void => {
		setFeedback( {
			...feedback,
			[ messageIndex ]: { type, submitted: true },
		} );
	};

	const renderFeedbackButton = ( type: 'positive' | 'negative' ) => {
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
			type === 'positive' ? 'This was helpful.' : 'This was not helpful.';
		const handleClick =
			type === 'positive'
				? handlePositiveFeedback
				: handleNegativeFeedback;

		return (
			<Button
				showTooltip={ true }
				icon={ icon }
				label={ label }
				className={ `woo-ai-assistant-${ type }-feedback-button` }
				onClick={ () => handleClick( index ) }
				disabled={ isSubmitted }
			/>
		);
	};

	return (
		<>
			<li key={ index } className={ `message ${ message.sender }` }>
				<ReactMarkdown>{ message.text }</ReactMarkdown>
			</li>
			{ message.sender === 'assistant' && (
				<div className="woo-ai-assistant-actions">
					<Button
						className="woo-ai-assistant-clipboard"
						icon="clipboard"
						onClick={ () => {
							navigator.clipboard.writeText( message.text );
						} }
					/>
					<div
						className="feedback-buttons"
						ref={ feedbackButtonsRef }
					>
						{ renderFeedbackButton( 'positive' ) }
						{ renderFeedbackButton( 'negative' ) }

						{ feedback[ index ]?.showSpecificFeedback && (
							<div className="specific-feedback">
								<p className="feedback-prompt">
									Sorry to hear that. Could you tell me more?
								</p>
								<Button
									variant="tertiary"
									onClick={ () =>
										submitSpecificFeedback(
											index,
											'outdated'
										)
									}
								>
									Outdated Information
								</Button>
								<Button
									variant="tertiary"
									onClick={ () =>
										submitSpecificFeedback(
											index,
											'incorrect'
										)
									}
								>
									Incorrect Information
								</Button>
								<Button
									variant="tertiary"
									onClick={ () =>
										submitSpecificFeedback(
											index,
											'not_answered'
										)
									}
								>
									Did Not Answer My Question
								</Button>
							</div>
						) }
					</div>
				</div>
			) }
		</>
	);
};

export default MessageItem;
