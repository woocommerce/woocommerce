/**
 * External dependencies
 */
import { useState } from 'react';
import { registerPlugin } from '@wordpress/plugins';
import { useCommand } from '@wordpress/commands';
import { commentAuthorAvatar } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ChatModal from './chat-modal';

const PlusIcon = () => commentAuthorAvatar;
const WooWizard = () => {
	const [ isModalOpen, setModalOpen ] = useState( false );
	useCommand( {
		name: 'woo-wizard',
		label: 'Woo Wizard',
		icon: commentAuthorAvatar,
		callback: () => setModalOpen( true ),
		context: 'block-editor',
	} );
	if ( isModalOpen ) {
		return (
			<ChatModal
				onClose={ () => {
					setModalOpen( false );
				} }
			/>
		);
	}
	return null;
};
registerPlugin( 'woo-wizard', {
	render: WooWizard,
	icon: PlusIcon,
} );
export default WooWizard;
