/**
 * External dependencies
 */
import { useCallback } from 'react';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
/**
 * Internal dependencies
 */
import { Message } from '../woo-ai-assistant/chat-modal';
import { WOO_AI_PLUGIN_NAME } from '../constants';
import { preferencesChatHistory } from '../woo-ai-assistant/constants';

interface UseSetAndStoreMessagesProps {
	setMessages: React.Dispatch< React.SetStateAction< Message[] > >;
}

const useSetAndStoreMessages = ( {
	setMessages,
}: UseSetAndStoreMessagesProps ) => {
	const { set: setStorageData } = useDispatch( preferencesStore );
	const setAndStoreMessages = useCallback(
		(
			messageText: string,
			sender: 'assistant' | 'user',
			userQueryIndex?: number,
			messageType: 'actionable' | 'informational' = 'actionable'
		) => {
			const chatMessage: Message = {
				sender,
				text: messageText,
				timestamp: new Date().getTime(),
				type: messageType,
			};

			if ( typeof userQueryIndex === 'number' && userQueryIndex >= 0 ) {
				chatMessage.userQueryIndex = userQueryIndex;
			}

			if ( typeof messageText !== 'string' ) {
				return;
			}

			setMessages( ( prevMessages ) => {
				const updatedMessages = [ ...prevMessages, chatMessage ];
				setStorageData(
					WOO_AI_PLUGIN_NAME,
					preferencesChatHistory,
					JSON.stringify( updatedMessages )
				);
				return updatedMessages;
			} );
		},
		[ setMessages, setStorageData ]
	);

	return setAndStoreMessages;
};

export default useSetAndStoreMessages;
