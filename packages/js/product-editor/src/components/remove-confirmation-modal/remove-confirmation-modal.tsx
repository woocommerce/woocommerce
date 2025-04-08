/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { RemoveConfirmationModalProps } from './types';

export function RemoveConfirmationModal( {
	title,
	description,
	onCancel,
	onRemove,
}: RemoveConfirmationModalProps ) {
	const [ isRemoving, setIsRemoving ] = useState( false );

	async function handleRemoveClick() {
		try {
			setIsRemoving( true );

			await onRemove();
		} finally {
			setIsRemoving( false );
		}
	}

	return (
		<Modal
			title={ title }
			onRequestClose={ ( event ) => {
				if ( event && ! event.isPropagationStopped() && onCancel ) {
					onCancel();
				}
			} }
			className="woocommerce-remove-confirmation-modal"
		>
			<div className="woocommerce-remove-confirmation-modal__content">
				{ description }
			</div>

			<div className="woocommerce-remove-confirmation-modal__buttons">
				<Button
					isDestructive
					variant="primary"
					isBusy={ isRemoving }
					onClick={ handleRemoveClick }
				>
					{ __( 'Delete', 'woocommerce' ) }
				</Button>
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}
