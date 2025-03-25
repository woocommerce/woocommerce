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
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentSettingsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Action } from '../Action';
import { connectWcpay } from './utils';
import './suggestion.scss';

interface PaymentGateway {
	id: string;
	needsSetup: boolean;
	installed: boolean;
	enabled: boolean;
}

interface SuggestionProps {
	paymentGateway: PaymentGateway;
	onSetupCallback?: ( () => void ) | null;
}

export const Suggestion: React.FC< SuggestionProps > = ( {
	paymentGateway,
	onSetupCallback = null,
} ) => {
	const {
		id,
		needsSetup,
		installed,
		enabled: isEnabled,
		installed: isInstalled,
	} = paymentGateway;

	const isWooPayEligible = useSelect( ( select ) => {
		const store = select( paymentSettingsStore );
		return store.getIsWooPayEligible();
	}, [] );

	const { createNotice } = useDispatch( 'core/notices' );
	// When WCPay is installed and onSetupCallback is null
	// Overwrite onSetupCallback to redirect to the setup page
	// when the user clicks on the "Finish setup" button.
	// WCPay doesn't need to be configured in WCA.
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
