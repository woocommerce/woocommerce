/**
 * External dependencies
 */
import { Button, ButtonGroup, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';
import RenewButton from './renew-button';

interface RenewProps {
	subscription: Subscription;
	onClose: () => void;
}

export default function RenewModal( props: RenewProps ) {
	return (
		<Modal
			title={ __( 'Renew to update', 'woocommerce' ) }
			onRequestClose={ props.onClose }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ sprintf(
					// translators: %s is the product version number (e.g. 1.0.2).
					__(
						'Version %s is available. To enable this update you need to renew your subscription.',
						'woocommerce'
					),
					props.subscription.version
				) }
			</p>
			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="tertiary"
					onClick={ props.onClose }
					className="woocommerce-marketplace__header-account-modal-button"
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<RenewButton
					subscription={ props.subscription }
					variant="primary"
				/>
			</ButtonGroup>
		</Modal>
	);
}
