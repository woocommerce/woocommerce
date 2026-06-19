/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { saveOption } from '../../../settings/data/actions';

export const ConnectionSuccessModal = ( {
	isDismissed,
}: {
	isDismissed: boolean;
} ) => {
	const [ dismissed, setDismissed ] = useState( isDismissed );

	const dismiss = () => {
		setDismissed( true );
		saveOption( 'wcpay_connection_success_modal_dismissed', true );
	};

	if ( dismissed ) {
		return null;
	}

	return (
		<Modal
			title={ __( "You're ready to accept payments!", 'woocommerce' ) }
			className="woocommerce-woopayments-connection-success-modal"
			onRequestClose={ dismiss }
		>
			<div className="woocommerce-woopayments-connection-success-modal__content">
				{ sprintf(
					/* translators: %s: Payment provider name. */
					__(
						'Great news - your %s account has been activated. You can now start accepting payments on your store.',
						'woocommerce'
					),
					'WooPayments'
				) }
			</div>
			<div className="woocommerce-woopayments-connection-success-modal__footer">
				<Button variant="primary" onClick={ dismiss }>
					{ __( 'Dismiss', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
