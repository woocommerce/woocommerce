/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import React, { useState } from 'react';

interface InfoModalProps {
	id: string;
	message: string;
}

export const InfoModal: React.FC< InfoModalProps > = ( { id, message } ) => {
	const hasBeenDismissedBefore = useSelect(
		// @ts-ignore
		( select ) =>
			select( preferencesStore ).get(
				'woo-ai-plugin',
				`modalDismissed-${ id }`
			),
		[ id ]
	);

	const { set } = useDispatch( preferencesStore );

	const [ isOpen, setIsOpen ] = useState( ! hasBeenDismissedBefore );

	const closeModal = () => {
		setIsOpen( false );
		set( 'woo-ai-plugin', `modalDismissed-${ id }`, true );
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal title="Notice" onRequestClose={ closeModal }>
			<p>{ message }</p>
			<Button onClick={ closeModal }>Okay</Button>
		</Modal>
	);
};
