/**
 * External dependencies
 */
import {
	Button,
	Modal,
	Flex,
	__experimentalText as Text,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface ImportModeConfirmationModalProps {
	isOpen: boolean;
	onClose: () => void;
	onConfirm: () => void;
}

/**
 * Confirmation modal for switching to immediate import mode.
 *
 * @param {Object}   props
 * @param {boolean}  props.isOpen    - Whether the modal is open.
 * @param {Function} props.onClose   - Callback when the modal is closed (cancel).
 * @param {Function} props.onConfirm - Callback when the user confirms the switch.
 */
export const ImportModeConfirmationModal = ( {
	isOpen,
	onClose,
	onConfirm,
}: ImportModeConfirmationModalProps ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Are you sure?', 'woocommerce' ) }
			onRequestClose={ onClose }
			className="analytics-import-mode-confirmation-modal"
			size="medium"
		>
			<Flex direction="column" gap={ 6 }>
				<Text>
					{ __(
						'Immediate updates to Analytics can impact your performance as it may slow busy stores.',
						'woocommerce'
					) }
				</Text>
				<Flex
					direction="row"
					className="analytics-import-mode-confirmation-modal__buttons"
					justify="flex-end"
				>
					<Button variant="tertiary" onClick={ onClose }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button variant="primary" onClick={ onConfirm }>
						{ __( 'Confirm', 'woocommerce' ) }
					</Button>
				</Flex>
			</Flex>
		</Modal>
	);
};
