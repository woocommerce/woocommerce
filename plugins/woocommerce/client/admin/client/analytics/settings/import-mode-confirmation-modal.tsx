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
			className="woocommerce-analytics-import-mode-confirmation-modal"
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
					className="woocommerce-analytics-import-mode-confirmation-modal__buttons"
					justify="flex-end"
				>
					<Button
						variant="tertiary"
						onClick={ onClose }
						aria-label={ __(
							'Cancel import mode change',
							'woocommerce'
						) }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ onConfirm }
						aria-label={ __(
							'Confirm switching to immediate import mode',
							'woocommerce'
						) }
					>
						{ __( 'Confirm', 'woocommerce' ) }
					</Button>
				</Flex>
			</Flex>
		</Modal>
	);
};
