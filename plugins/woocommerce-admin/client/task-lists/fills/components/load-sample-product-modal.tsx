/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Modal, Spinner } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './load-sample-product-modal.scss';

const LoadSampleProductModal: React.FC = () => {
	return (
		<Modal
			className="woocommerce-products-load-sample-product-modal"
			overlayClassName="woocommerce-products-load-sample-product-modal-overlay"
			title=""
			onRequestClose={ () => {} }
		>
			{ /* @ts-expect-error - We don't get the size prop typed here for some reason. */ }
			<Spinner color="#007cba" size={ 48 } />
			<Text className="woocommerce-load-sample-product-modal__title">
				{ __( 'Loading sample products', 'woocommerce' ) }
			</Text>
			<Text className="woocommerce-load-sample-product-modal__description">
				{ __(
					'We are loading 9 sample products into your store',
					'woocommerce'
				) }
			</Text>
		</Modal>
	);
};

export default LoadSampleProductModal;
