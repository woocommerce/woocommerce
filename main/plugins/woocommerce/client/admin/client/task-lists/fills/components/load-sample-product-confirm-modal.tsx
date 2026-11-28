/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './load-sample-product-confirm-modal.scss';

type Props = {
	onCancel: () => void;
	onImport: () => void;
};

export const LoadSampleProductConfirmModal: React.VFC< Props > = ( {
	onCancel,
	onImport,
} ) => {
	return (
		<Modal
			className="woocommerce-products-load-sample-product-confirm-modal"
			overlayClassName="woocommerce-products-load-sample-product-confirm-modal-overlay"
			title={ __( 'Load sample products', 'woocommerce' ) }
			onRequestClose={ onCancel }
		>
			<Text className="woocommerce-confirmation-modal__message">
				{ __(
					'We’ll import images from WooCommerce.com to set up your sample products.',
					'woocommerce'
				) }
			</Text>
			<div className="woocommerce-confirmation-modal-actions">
				<Button isSecondary onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button isPrimary onClick={ onImport }>
					{ __( 'Import sample products', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default LoadSampleProductConfirmModal;
