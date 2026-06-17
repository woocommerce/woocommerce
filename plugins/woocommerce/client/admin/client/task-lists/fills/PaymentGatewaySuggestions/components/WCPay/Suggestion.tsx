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
import { useSelect } from '@wordpress/data';
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
	onSetupCallback?: ( ( resolve?: () => void ) => void ) | null;
}

export const Suggestion = ( {
	paymentGateway,
	onSetupCallback = null,
}: SuggestionProps ) => {
	const {
		id,
		needsSetup,
		enabled: isEnabled,
		installed: isInstalled,
	} = paymentGateway;

	const isWooPayEligible = useSelect( ( select ) => {
		const store = select( paymentSettingsStore );
		return store.getIsWooPayEligible();
	}, [] );

	if ( onSetupCallback === null ) {
		onSetupCallback = ( resolve?: () => void ) => {
			connectWcpay();
			resolve?.();
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
							isEnabled={ isEnabled && ! needsSetup }
							isRecommended={ true }
							isInstalled={ isInstalled }
							hasPlugins={ false }
							setupButtonText={
								isInstalled
									? __( 'Finish setup', 'woocommerce' )
									: __( 'Get started', 'woocommerce' )
							}
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
