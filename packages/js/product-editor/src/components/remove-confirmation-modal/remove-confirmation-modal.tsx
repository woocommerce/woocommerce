/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { RemoveConfirmationModalProps } from './types';

export const RemoveConfirmationModal: React.FC<
	RemoveConfirmationModalProps
> = ( { title, description, onCancel, onRemove } ) => {
	return (
		<Modal
			title={ title }
			onRequestClose={ (
				event:
					| React.KeyboardEvent< Element >
					| React.MouseEvent< Element >
					| React.FocusEvent< Element >
			) => {
				if ( ! event.isPropagationStopped() && onCancel ) {
					onCancel();
				}
			} }
			className="woocommerce-remove-confirmation-modal"
		>
			<div className="woocommerce-remove-confirmation-modal__content">
				{ description }
			</div>

			<div className="woocommerce-remove-confirmation-modal__buttons">
				<Button isDestructive variant="primary" onClick={ onRemove }>
					{ __( 'Delete', 'woocommerce' ) }
				</Button>
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
