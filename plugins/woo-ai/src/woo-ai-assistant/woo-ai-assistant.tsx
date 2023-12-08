/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import ChatModal from './chat-modal';

const WooAIAssistant = () => {
	const [ isModalOpen, setModalOpen ] = useState( false );

	const handleKeyDown = useCallback( ( event ) => {
		if ( event.altKey && event.shiftKey && event.code === 'KeyW' ) {
			setModalOpen( ( currentIsModalOpen ) => ! currentIsModalOpen );
		}
	}, [] );

	useEffect( () => {
		window.addEventListener( 'keydown', handleKeyDown );
		return () => {
			window.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ handleKeyDown ] );

	return (
		<>
			{ isModalOpen && (
				<ChatModal
					onClose={ () => {
						setModalOpen( false );
					} }
				/>
			) }
		</>
	);
};

export default WooAIAssistant;
