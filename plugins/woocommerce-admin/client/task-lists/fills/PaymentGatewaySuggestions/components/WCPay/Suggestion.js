/**
 * External dependencies
 */
import { WCPayConnectCard } from '@woocommerce/onboarding';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */

import { Action } from '../Action';
import { connectWcpay } from './utils';
import './suggestion.scss';
import { getAdminSetting } from '~/utils/admin-settings';

export const Suggestion = ( { paymentGateway, onSetupCallback = null } ) => {
	const {
		id,
		needsSetup,
		enabled: isEnabled,
		installed: isInstalled,
	} = paymentGateway;
	const isWooPayEligible = getAdminSetting( 'isWooPayEligible' );

	const { createNotice } = useDispatch( 'core/notices' );
	// When WCPay is installed and onSetupCallback is null
	// Overwrite onSetupCallback to redirect to the setup page
	// when the user clicks on the "Finish setup" button.
	// WCPay doesn't need to be configured in WCA.
	// It should be configured in its onboarding flow.
	if ( isInstalled && onSetupCallback === null ) {
		onSetupCallback = () => {
			connectWcpay( createNotice );
		};
	}

	const wccomSettings = getAdminSetting( 'wccomHelper', false );

	const firstName = getAdminSetting( 'currentUserData' )?.first_name;

	return (
		<div className="woocommerce-wcpay-suggestion woocommerce-task-payment-wcpay">
			<WCPayConnectCard
				actionButton={
					<Action
						id={ id }
						hasSetup={ true }
						needsSetup={ needsSetup }
						isEnabled={ isEnabled }
						isRecommended={ true }
						isInstalled={ isInstalled }
						hasPlugins={ true }
						onSetupCallback={ onSetupCallback }
					/>
				}
				firstName={ firstName }
				businessCountry={ wccomSettings?.storeCountry ?? '' }
				isWooPayEligible={ isWooPayEligible }
				showNotice={ true }
			/>
		</div>
	);
};
