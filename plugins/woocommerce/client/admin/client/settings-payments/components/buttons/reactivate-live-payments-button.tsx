/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { dispatch, useDispatch } from '@wordpress/data';
import { paymentSettingsStore } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

interface ReactivateLivePaymentsButtonProps {
	/**
	 * The text of the button.
	 */
	buttonText?: string;
	/**
	 * The settings URL to navigate to when the enable gateway button is clicked.
	 */
	settingsHref: string;
}

/**
 * A button component that allows users to disable test mode payments (only for WooPayments at the moment).
 */
export const ReactivateLivePaymentsButton = ( {
	buttonText = __( 'Reactivate payments', 'woocommerce' ),
	settingsHref,
}: ReactivateLivePaymentsButtonProps ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		dispatch( 'core/notices' );
	const { invalidateResolutionForStoreSelector } =
		useDispatch( paymentSettingsStore );

	const disableTestModePayments = ( e: React.MouseEvent ) => {
		e.preventDefault();
		setIsUpdating( true );

		apiFetch( {
			path: '/wc/v3/payments/settings',
			method: 'POST',
			data: {
				is_test_mode_enabled: false,
			},
		} )
			.then( () => {
				createSuccessNotice(
					sprintf(
						/* translators: %s: WooPayments */
						__(
							'%s is now processing live payments (real payment methods and charges).',
							'woocommerce'
						),
						'WooPayments'
					),
					{
						type: 'snackbar',
						explicitDismiss: false,
					}
				);

				// Force the providers to be refreshed.
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );

				setIsUpdating( false );
			} )
			.catch( () => {
				// In case of errors, redirect to the gateway settings page.
				setIsUpdating( false );

				createErrorNotice(
					sprintf(
						/* translators: %s: WooPayments */
						__(
							'An error occurred. You will be redirected to the %s settings page to manage payments processing mode from there.',
							'woocommerce'
						),
						'WooPayments'
					),
					{
						type: 'snackbar',
						explicitDismiss: true,
					}
				);

				window.location.href = settingsHref;
			} );
	};

	return (
		<Button
			variant={ 'primary' }
			isBusy={ isUpdating }
			disabled={ isUpdating }
			onClick={ disableTestModePayments }
			href={ settingsHref }
		>
			{ buttonText }
		</Button>
	);
};
