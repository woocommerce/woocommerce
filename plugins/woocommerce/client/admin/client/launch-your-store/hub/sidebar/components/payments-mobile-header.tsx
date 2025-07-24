/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { SidebarComponentProps } from '../xstate';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import { wooPaymentsOnboardingSessionEntryLYS } from '~/settings-payments/constants';

export const PaymentsMobileHeader = ( props: SidebarComponentProps ) => {
	const handleBackClick = () => {
		recordEvent( 'launch_your_store_payments_back_to_hub_click' );

		// Record the "modal" being closed to keep consistency with the Payments Settings flow.
		recordPaymentsOnboardingEvent( 'woopayments_onboarding_modal_closed', {
			from: 'lys_mobile_header_back_to_hub',
			source: wooPaymentsOnboardingSessionEntryLYS,
		} );

		// Clear session flag to prevent redirect back to payments setup
		// after exiting the flow and returning to the WC Admin home.
		window.sessionStorage.setItem( 'lysWaiting', 'no' );

		props.sendEventToSidebar( {
			type: 'RETURN_FROM_PAYMENTS',
		} );
	};

	return (
		<div className="launch-your-store-mobile-header payments-mobile-header">
			<Button
				className="launch-your-store-mobile-header__back-button"
				onClick={ handleBackClick }
				icon={ chevronLeft }
				iconSize={ 20 }
				aria-label={ __( 'Go back', 'woocommerce' ) }
			/>
			<h1 className="launch-your-store-mobile-header__title">
				{ __( 'Set up WooPayments', 'woocommerce' ) }
			</h1>
		</div>
	);
};
