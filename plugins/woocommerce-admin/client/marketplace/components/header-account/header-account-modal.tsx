/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button, ButtonGroup, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

export interface HeaderAccountModalProps {
	setIsModalOpen: ( value: boolean ) => void;
	disconnectURL: string;
}

export default function HeaderAccountModal(
	props: HeaderAccountModalProps
): JSX.Element {
	const { setIsModalOpen, disconnectURL } = props;
	const [ isBusy, setIsBusy ] = useState( false );
	const toggleIsBusy = () => setIsBusy( ! isBusy );
	const closeModal = () => setIsModalOpen( false );

	return (
		<Modal
			title={ __( 'Are you sure?', 'woocommerce' ) }
			onRequestClose={ closeModal }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ __(
					'Keep your your account connected to manage your subscriptions, get updates and support for your extensions and themes.',
					'woocommerce'
				) }
			</p>
			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="tertiary"
					href={ disconnectURL }
					onClick={ toggleIsBusy }
					isBusy={ isBusy }
					isDestructive={ true }
					className="woocommerce-marketplace__header-account-modal-button"
				>
					{ __( 'Disconnect account', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ closeModal }
					className="woocommerce-marketplace__header-account-modal-button"
				>
					{ __( 'Keep connected', 'woocommerce' ) }
				</Button>
			</ButtonGroup>
		</Modal>
	);
}
