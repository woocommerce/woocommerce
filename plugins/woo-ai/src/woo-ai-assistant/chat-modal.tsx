/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Modal } from '@wordpress/components';
import { useDispatch, select } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';
import MessageItem from './message-item';
import { recordWooAIAssistantTracks } from './utils';
import ChatInputForm from './chat-input-form';
import TypingIndicator from '../components/typing-indicator';
import useWooAssistantApiCall from '../hooks/useWooAssistantApiCall';
import useSetAndStoreMessages from '../hooks/useSetAndStoreMessages';
import { WOO_AI_PLUGIN_NAME } from '../constants';
import { preferencesChatHistory, preferencesThreadID } from './constants';

export type Message = {
	sender: 'user' | 'assistant';
	text: string;
	userQueryIndex?: number;
	timestamp: number;
	type: 'informational' | 'actionable';
};

type ChatModalProps = {
	onClose: () => void;
};

const ChatModal: React.FC< ChatModalProps > = ( { onClose } ) => {
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ isLoading, setLoading ] = useState( false );
	const [ threadID, setThreadID ] = useState< string >( '' );
	const [ isResponseError, setIsResponseError ] = useState( false );
	const { set: setStorageData } = useDispatch( preferencesStore );
	const startSessionTimeRef = useRef< number >( Date.now() );

	const [ parent ] = useAutoAnimate();
	const chatMessagesEndRef = React.useRef< HTMLLIElement | null >( null );

	// Calculate the session duration based on the component mounting and unmounting (opening and closing modal).
	useEffect( () => {
		return () => {
			const sessionDurationInSeconds = Math.floor(
				( Date.now() - startSessionTimeRef.current ) / 1000
			);
			if (
				startSessionTimeRef?.current &&
				sessionDurationInSeconds > 0
			) {
				recordWooAIAssistantTracks( 'session_duration', {
					session_duration_in_seconds: sessionDurationInSeconds,
				} );
			}
			startSessionTimeRef.current = 0;
		};
	}, [] );

	// Record to Tracks an event when we open the chat modal.
	useEffect( () => {
		recordWooAIAssistantTracks( 'open' );
	}, [] );

	// Scroll to the bottom of the chat when a new message is added.
	useEffect( () => {
		if ( chatMessagesEndRef && chatMessagesEndRef.current ) {
			const element = chatMessagesEndRef.current;
			element.scrollIntoView( { behavior: 'smooth' } );
		}
	}, [ chatMessagesEndRef, messages ] );

	useEffect( () => {
		if ( ! threadID ) {
			const storedthreadID = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				preferencesThreadID
			);
			setThreadID( storedthreadID );
		}
	}, [ threadID, setStorageData ] );

	useEffect( () => {
		if ( ! messages || ! messages.length ) {
			const storedMessages = select( preferencesStore ).get(
				WOO_AI_PLUGIN_NAME,
				preferencesChatHistory
			);
			// Check that each message text property is a string, otherwise we'll get an error when rendering.
			if ( storedMessages ) {
				const parsedMessages = JSON.parse( storedMessages );
				const filteredMessages = parsedMessages.filter(
					( message: Message ) => typeof message.text === 'string'
				);
				setMessages( filteredMessages );
			}
		}
	}, [ messages ] );

	const handleError = useCallback( ( message: string ) => {
		setIsResponseError( true );
		setMessages( ( prevMessages ) => [
			...prevMessages,
			{
				sender: 'assistant',
				text: message,
				timestamp: new Date().getTime(),
				type: 'actionable',
			},
		] );
	}, [] );
	const setAndStoreMessages = useSetAndStoreMessages( { setMessages } );

	const generateInformationalMessage = useCallback(
		( message ) => {
			setAndStoreMessages(
				message,
				'assistant',
				undefined,
				'informational'
			);
		},
		[ setAndStoreMessages ]
	);

	const setAndStorethreadID = useCallback(
		( newthreadID: string ) => {
			if ( newthreadID !== threadID ) {
				setThreadID( newthreadID );
				setStorageData(
					WOO_AI_PLUGIN_NAME,
					preferencesThreadID,
					newthreadID
				);
			}
		},
		[ setStorageData, threadID ]
	);

	const { makeWooAssistantApiCall } = useWooAssistantApiCall( {
		setAndStorethreadID,
		generateInformationalMessage,
		messages,
		setMessages,
		setLoading,
		handleError,
		isResponseError,
		threadID,
	} );

	const retryUserQuery = useCallback(
		async ( index: number ) => {
			const assistantMessage = messages[ index ];
			if (
				assistantMessage.userQueryIndex === undefined ||
				assistantMessage.sender !== 'assistant'
			) {
				return;
			}
			const userQuery = messages[ assistantMessage.userQueryIndex ];
			if (
				! userQuery ||
				userQuery.sender !== 'user' ||
				! userQuery.text
			) {
				return;
			}

			setAndStoreMessages( userQuery.text, 'user' );
			const retryUserQueryPrompt = `Your answer to the previous request was unsatisfactory, please try again. ${ userQuery.text }`;

			try {
				makeWooAssistantApiCall( retryUserQueryPrompt );
			} catch ( error ) {
				handleError( 'I had trouble generating a response for you.' );
			} finally {
				recordWooAIAssistantTracks( 'send_message_retry', {
					message: userQuery.text,
				} );
			}
		},
		[ messages, handleError, makeWooAssistantApiCall, setAndStoreMessages ]
	);

	const handleSubmit = useCallback(
		async ( input: string ) => {
			if ( ! input.trim() ) {
				return;
			}
			try {
				setAndStoreMessages( input, 'user', messages.length );
				makeWooAssistantApiCall( input );
			} catch ( error ) {
				handleError(
					"I'm sorry, I had trouble generating a response for you."
				);
			}
		},
		[
			makeWooAssistantApiCall,
			setAndStoreMessages,
			handleError,
			messages.length,
		]
	);

	const OnboardingMessage = useCallback( () => {
		return (
			<li className="woo-ai-onboarding-message">
				<p className="message assistant">
					Hi there! I am your helpful Woo AI Assistant, here to answer
					questions, find relevant documentation, and perform common
					store tasks. How can I help you today?
				</p>
			</li>
		);
	}, [] );

	const ExampleClickableQuestions = useCallback( () => {
		const examples = {
			action: 'Run a sales report for today.',
			information:
				'Recommend a Woo Extension that enables selling subscription products on my store.',
			consultation:
				'What are some tips to improve my store conversion rates?',
		};
		return (
			<div className="woo-ai-message-examples">
				<Button
					variant="tertiary"
					onClick={ () => handleSubmit( examples.action ) }
				>
					{ __( 'Run a sales report for today.', 'woocommerce' ) }
				</Button>
				<Button
					variant="tertiary"
					onClick={ () => handleSubmit( examples.information ) }
				>
					{ __(
						'Recommend a Woo Extension that enables selling subscription products on my store.',
						'woocommerce'
					) }
				</Button>
				<Button
					variant="tertiary"
					onClick={ () => handleSubmit( examples.consultation ) }
				>
					{ __(
						'What are some tips to improve my store conversion rates?',
						'woocommerce'
					) }
				</Button>
			</div>
		);
	}, [ handleSubmit ] );

	return (
		<Modal
			title="Woo Wizard Assistant"
			onRequestClose={ onClose }
			className="woo-ai-assistant-chat-modal"
			shouldCloseOnClickOutside={ false }
		>
			<ul className="woo-chat-history" ref={ parent }>
				{ ! messages.length && <OnboardingMessage /> }
				{ ! messages.length && <ExampleClickableQuestions /> }
				{ messages.map( ( message, index ) => (
					<MessageItem
						message={ message }
						key={ message.timestamp }
						index={ index }
						onRetry={ retryUserQuery }
					/>
				) ) }
				{ isLoading && <TypingIndicator /> }
				<li ref={ chatMessagesEndRef }></li>
			</ul>
			<ChatInputForm
				onSubmit={ handleSubmit }
				isLoading={ isLoading }
				handleError={ handleError }
			/>
		</Modal>
	);
};

export default ChatModal;
