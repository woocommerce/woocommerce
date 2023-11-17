/**
 * External dependencies
 */
import React, { useState } from 'react';
import { Modal, Button, TextControl } from '@wordpress/components';

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

	const handleSubmit = ( event: React.FormEvent ) => {
		event.preventDefault();
		if ( ! input.trim() ) return;
		const newMessage: Message = { sender: 'user', text: input };
		setMessages( [ ...messages, newMessage ] );
		setInput( '' );
		// Add logic here to send the message to the assistant and receive a reply
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
				<Button isPrimary type="submit">
					Send
				</Button>
			</form>
		</Modal>
	);
};

export default ChatModal;
