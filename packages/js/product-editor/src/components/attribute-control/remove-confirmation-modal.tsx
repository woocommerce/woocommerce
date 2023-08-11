/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';

type RemoveConfirmationModalProps = {
	title?: string;
	description?: string | React.ReactElement;
	onCancel: () => void;
	onRemove: () => void;
};

export const RemoveConfirmationModal: React.FC<
	RemoveConfirmationModalProps
> = ( {
	title = __( 'Add attributes', 'woocommerce' ),
	description = '',
	onCancel,
	onRemove,
} ) => {
	return (
		<Modal
			title={ title }
			onRequestClose={ (
				event:
					| React.KeyboardEvent< Element >
					| React.MouseEvent< Element >
					| React.FocusEvent< Element >
			) => {
				if ( ! event.isPropagationStopped() ) {
					onCancel();
				}
			} }
			className="woocommerce-remove-attribute-modal"
		>
			{ description && <p>{ description }</p> }

			<div className="woocommerce-remove-attribute-modal__buttons">
				<Button
					isDestructive
					variant="primary"
					label={ __( 'Delete', 'woocommerce' ) }
					onClick={ onRemove }
				>
					{ __( 'Delete', 'woocommerce' ) }
				</Button>
				<Button
					variant="tertiary"
					label={ __( 'Cancel', 'woocommerce' ) }
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
