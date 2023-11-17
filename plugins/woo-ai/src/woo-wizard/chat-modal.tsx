/**
 * External dependencies
 */
import React, { useState } from 'react';
import { Modal, Button, Spinner, TextControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import './index.scss';

interface Message {
	sender: 'user' | 'assistant';
	text: string;
}

interface ChatModalProps {
	onClose: () => void;
}

const ChatModal: React.FC< ChatModalProps > = ( { onClose } ) => {
	const [ input, setInput ] = useState( '' );
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ isLoading, setLoading ] = useState( false );

	const handleSubmit = async ( event: React.FormEvent ) => {
		event.preventDefault();
		if ( ! input.trim() ) return;
		setLoading( true );

		const userMessage: Message = { sender: 'user', text: input };
		setMessages( [ ...messages, userMessage ] );
		setInput( '' );

		try {
			const { token } = await requestJetpackToken();
			const url = new URL(
				'https://public-api.wordpress.com/wpcom/v2/woo-wizard'
			);
			url.searchParams.append( 'message', input );
			url.searchParams.append( 'token', token );
			const response = ( await apiFetch( {
				url: url.toString(),
				method: 'GET',
			} ) ) as any;

			// Assuming the response contains the assistant's message
			const messages = response.messages;
			if ( ! messages || ! messages.length ) {
				throw new Error( 'No messages returned from assistant' );
			}
			// create a new assistantMessage array
			const assistantMessages: Message[] = [];
			for ( const message of messages ) {
				const assistantMessage: Message = {
					sender: 'assistant',
					text: message.content,
				};
				assistantMessages.push( assistantMessage );
			}
			setMessages( ( messages ) => [
				...messages,
				...assistantMessages,
			] );
		} catch ( error ) {
			console.error( 'Error fetching response:', error );
			// Handle the error appropriately
		}
		setLoading( false );
	};

	return (
		<Modal
			title="Woo Wizard Assistant"
			onRequestClose={ onClose }
			className="woo-wizard-chat-modal"
		>
			<div className="woo-chat-history">
				{ messages.map( ( message, index ) => (
					<div
						key={ index }
						className={ `message ${ message.sender }` }
					>
						{ message.text }
					</div>
				) ) }
			</div>
			<form onSubmit={ handleSubmit } className="chat-form">
				<TextControl
					value={ input }
					onChange={ setInput }
					placeholder="Type your message..."
				/>
				<Button
					disabled={ isLoading }
					isBusy={ isLoading }
					type="submit"
					variant="primary"
				>
					Send
				</Button>
			</form>
		</Modal>
	);
};

export default ChatModal;
