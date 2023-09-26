/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';

type ConfirmationModalProps = {
	onClose: () => void;
	onDismiss: () => void;
	buttonLabel?: string;
};

export const InboxDismissConfirmationModal: React.FC<
	ConfirmationModalProps
> = ( {
	onClose,
	onDismiss,
	buttonLabel = __( "Yes, I'm sure", 'woocommerce' ),
} ) => {
	const [ inAction, setInAction ] = useState( false );

	return (
		<Modal
			title={ __( 'Are you sure?', 'woocommerce' ) }
			onRequestClose={ () => onClose() }
			className="woocommerce-inbox-dismiss-confirmation_modal"
		>
			<div className="woocommerce-inbox-dismiss-confirmation_wrapper">
				<p>
					{ __(
						'Dismissed messages cannot be viewed again',
						'woocommerce'
					) }
				</p>
				<div className="woocommerce-inbox-dismiss-confirmation_buttons">
					<Button isSecondary onClick={ () => onClose() }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isSecondary
						isBusy={ inAction }
						disabled={ inAction }
						onClick={ () => {
							setInAction( true );
							onDismiss();
						} }
					>
						{ buttonLabel }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
