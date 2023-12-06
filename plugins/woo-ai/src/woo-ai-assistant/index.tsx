/**
 * External dependencies
 */
import { useState } from 'react';
import { useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { commentAuthorAvatar } from '@wordpress/icons';
import { useShortcut } from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import ChatModal from './chat-modal';
const SHORTCUT_NAME = 'woo-ai-assistant-toggle';

const PlusIcon = () => commentAuthorAvatar;
const WooAIAssistant = () => {
	const { registerShortcut } = useDispatch( 'core/keyboard-shortcuts' );
	registerShortcut( {
		name: SHORTCUT_NAME,
		category: 'global',
		description: 'Toggle the showing of the Woo AI Assistant',
		keyCombination: {
			modifier: 'shiftAlt',
			character: 'w',
		},
	} );
	const [ isModalOpen, setModalOpen ] = useState( false );
	useShortcut( SHORTCUT_NAME, () => setModalOpen( ! isModalOpen ) );
	return (
		<>
			{ isModalOpen && (
				<ChatModal onClose={ () => setModalOpen( false ) } />
			) }
		</>
	);
};

if ( ! wp.plugins.getPlugin( 'woo-ai-assistant' ) ) {
	registerPlugin( 'woo-ai-assistant', {
		render: WooAIAssistant,
		icon: PlusIcon,
	} );
}
export default WooAIAssistant;
