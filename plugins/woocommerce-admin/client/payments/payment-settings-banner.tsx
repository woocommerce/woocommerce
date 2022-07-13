/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';
import {
	WCPayBanner,
	WCPayBannerBody,
	WCPayBannerFooter,
} from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import './payment-recommendations.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { usePaymentExperiment } from './use-payments-experiment';

const WCPaySettingBanner = () => {
	const WC_PAY_SETUP_URL = getAdminLink(
		'admin.php?wcpay-connect=1&_wpnonce=' +
			getAdminSetting( 'wcpay_welcome_page_connect_nonce' )
	);

	return (
		<WCPayBanner>
			<WCPayBannerBody
				textPosition="right"
				actionButton={
					<Button href={ WC_PAY_SETUP_URL } isPrimary>
						{ __( 'Get started', 'woocommerce' ) }
					</Button>
				}
			/>
			<WCPayBannerFooter />
		</WCPayBanner>
	);
};

const DefaultPaymentMethodsHeaderText = () => (
	<>
		<h2>{ __( 'Payment Methods', 'woocommerce' ) }</h2>
		<div id="payment_gateways_options-description">
			<p>
				{ __(
					'Installed payment methods are listed below and can be sorted to control their display order on the frontend.',
					'woocommerce'
				) }
			</p>
		</div>
	</>
);

export const PaymentsBannerWrapper = () => {
	const { isLoadingExperiment, experimentAssignment } =
		usePaymentExperiment();

	if (
		! isLoadingExperiment &&
		experimentAssignment?.variationName === 'treatment'
	) {
		return <WCPaySettingBanner />;
	}
	return <DefaultPaymentMethodsHeaderText />;
};
