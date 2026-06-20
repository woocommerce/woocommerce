/**
 * External dependencies
 */
import { Modal, Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import WooPayLogoImage from './express-checkout/assets/woopay-preview-logo.svg';

export const WOOPAY_DISABLE_FEEDBACK_URL =
	'https://woocommerce.survey.fm/woopay-disabled-merchants-feedback-triggered';

export const WooPayDisableFeedback = ( {
	onRequestClose,
}: {
	onRequestClose: () => void;
} ) => {
	const [ isLoading, setIsLoading ] = useState( true );

	return (
		<Modal
			title={ __( 'WooPay feedback', 'woocommerce' ) }
			isDismissible
			shouldCloseOnClickOutside={ false }
			shouldCloseOnEsc
			onRequestClose={ onRequestClose }
			className="woopayments-woopay-disable-feedback"
		>
			<div className="woopayments-woopay-disable-feedback__body">
				<img
					src={ WooPayLogoImage }
					alt={ __( 'WooPay logo', 'woocommerce' ) }
					className="woopayments-woopay-disable-feedback__logo"
				/>
				{ isLoading && (
					<p
						className="woopayments-woopay-disable-feedback__status"
						aria-live="polite"
					>
						<Spinner />
						{ __( 'Loading feedback form…', 'woocommerce' ) }
					</p>
				) }
				<iframe
					title={ __( 'WooPay disable feedback', 'woocommerce' ) }
					src={ WOOPAY_DISABLE_FEEDBACK_URL }
					className="woopayments-woopay-disable-feedback__iframe"
					onLoad={ () => setIsLoading( false ) }
				/>
			</div>
		</Modal>
	);
};
