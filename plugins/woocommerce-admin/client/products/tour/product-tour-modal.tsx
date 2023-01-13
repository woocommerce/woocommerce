/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ProductTourImage from './product-tour.png';
import './product-tour-modal.scss';

type ProductTourModalProps = {
	onClose: () => void;
	onStart: () => void;
};

export const ProductTourModal: React.FC< ProductTourModalProps > = ( {
	onClose,
	onStart,
} ) => {
	return (
		<Modal
			bodyOpenClassName={ '' }
			className="woocommerce-product-tour-modal"
			onRequestClose={ () => onClose() }
			overlayClassName="woocommerce-product-tour-modal__overlay"
			shouldCloseOnClickOutside={ false }
			title={ __( 'Meet the product editing form', 'woocommerce' ) }
		>
			<div className="woocommerce-product-tour-modal__header-img">
				<img
					src={ ProductTourImage }
					alt={ __( 'Product editing tour', 'woocommerce' ) }
				/>
			</div>
			<div className="woocommerce-product-tour-modal__content">
				<p>
					{ __(
						'Let us show you how to navigate the form and create this product from start to finish in no time.',
						'woocommerce'
					) }
				</p>
				<div className="woocommerce-product-tour-modal__actions">
					<Button variant="tertiary" onClick={ () => onClose() }>
						{ __( "I'll explore on my own", 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ () => {
							onStart();
						} }
					>
						{ __( 'Show me around (10s)', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
