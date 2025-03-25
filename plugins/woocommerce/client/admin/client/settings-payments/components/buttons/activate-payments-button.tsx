/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { PaymentIncentive } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getWooPaymentsSetupLiveAccountLink } from '~/settings-payments/utils';

interface ActivatePaymentsButtonProps {
	/**
	 * Callback used when an incentive is accepted.
	 *
	 * @param id Incentive ID.
	 */
	acceptIncentive: ( id: string ) => void;
	/**
	 * The text of the button.
	 */
	buttonText?: string;
	/**
	 * Incentive data. If provided, the incentive will be accepted when the button is clicked.
	 */
	incentive?: PaymentIncentive | null;
	/**
	 * ID of the plugin that is being installed.
	 */
	installingPlugin: string | null;
}

/**
 * A button component that initiates the payment activation process.
 * If incentive data is provided, it will trigger the `acceptIncentive` callback with the incentive ID before redirecting to setup live payments link.
 */
export const ActivatePaymentsButton = ( {
	acceptIncentive,
	installingPlugin,
	buttonText = __( 'Activate payments', 'woocommerce' ),
	incentive = null,
}: ActivatePaymentsButtonProps ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );

	const activatePayments = () => {
		setIsUpdating( true );

		if ( incentive ) {
			acceptIncentive( incentive.promo_id );
		}

		window.location.href = getWooPaymentsSetupLiveAccountLink();
	};

	return (
		<Button
			variant={ 'primary' }
			isBusy={ isUpdating }
			disabled={ isUpdating || !! installingPlugin }
			onClick={ activatePayments }
		>
			{ buttonText }
		</Button>
	);
};
