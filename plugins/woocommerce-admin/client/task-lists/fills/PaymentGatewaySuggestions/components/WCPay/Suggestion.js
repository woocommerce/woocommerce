/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	WCPayBanner,
	WCPayBannerFooter,
	WCPayBannerBody,
	WCPayBenefits,
	WCPayBannerImageCut,
} from '@woocommerce/onboarding';
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
		installed,
		enabled: isEnabled,
		installed: isInstalled,
	} = paymentGateway;
	const isWooPayEligible = getAdminSetting( 'isWooPayEligible' );

	const { createNotice } = useDispatch( 'core/notices' );
	// When the WC Pay is installed and onSetupCallback is null
	// Overwrite onSetupCallback to redirect to the setup page
	// when the user clicks on the "Finish setup" button.
	// WC Pay doesn't need to be configured in WCA.
	// It should be configured in its onboarding flow.
	if ( installed && onSetupCallback === null ) {
		onSetupCallback = () => {
			connectWcpay( createNotice );
		};
	}

	return (
		<div className="woocommerce-wcpay-suggestion">
			<WCPayBanner>
				<WCPayBannerBody
					textPosition="left"
					actionButton={
						<Action
							id={ id }
							hasSetup={ true }
							needsSetup={ needsSetup }
							isEnabled={ isEnabled }
							isRecommended={ true }
							isInstalled={ isInstalled }
							hasPlugins={ true }
							setupButtonText={ __(
								'Get started',
								'woocommerce'
							) }
							onSetupCallback={ onSetupCallback }
						/>
					}
					bannerImage={ <WCPayBannerImageCut /> }
					isWooPayEligible={ isWooPayEligible }
				/>
				<WCPayBenefits isWooPayEligible={ isWooPayEligible } />
				<WCPayBannerFooter isWooPayEligible={ isWooPayEligible } />
			</WCPayBanner>
		</div>
	);
};
