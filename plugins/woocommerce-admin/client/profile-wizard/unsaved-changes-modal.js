/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const UnsavedChangesModal = ( { onClose, onSave } ) => {
	const title = __( 'Save changes?', 'woocommerce' );
	const message = __(
		"You're about to go to a different step. Do you want to save the changes you've made here so far?",
		'woocommerce'
	);
	const discardText = __( 'Discard', 'woocommerce' );
	const saveText = __( 'Save', 'woocommerce' );
	return (
		<>
			<Modal
				title={ title }
				className="woocommerce-obw-unsaved-changes"
				onRequestClose={ onClose }
			>
				<div className="woocommerce-obw-unsaved-changes-modal__wrapper">
					<div className="woocommerce-usage-modal__message">
						{ message }
					</div>
					<div className="woocommerce-usage-modal__actions">
						<Button onClick={ () => onClose() }>
							{ discardText }
						</Button>
						<Button isPrimary onClick={ onSave }>
							{ saveText }
						</Button>
					</div>
				</div>
			</Modal>
		</>
	);
};

export default UnsavedChangesModal;
