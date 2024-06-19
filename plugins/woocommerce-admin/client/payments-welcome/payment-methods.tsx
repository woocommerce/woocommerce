/**
 * External dependencies
 */
import {
	Visa,
	MasterCard,
	Amex,
	ApplePay,
	GooglePay,
	CB,
	Discover,
	Ideal,
	Klarna,
	Affirm,
	AfterPay,
	ClearPay,
	Woo,
} from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import strings from './strings';
import { getAdminSetting } from '~/utils/admin-settings';

const PaymentMethods: React.FC = () => {
	const wccomSettings = getAdminSetting( 'wccomHelper', false );
	return (
		<div className="woopayments-welcome-page__payment-methods">
			<Visa />
			<MasterCard />
			<Amex />
			<CB />
			<Discover />
			<Ideal />
			<ApplePay />
			<GooglePay />
			<Woo />
			<Klarna />
			<Affirm />
			{ wccomSettings && wccomSettings.storeCountry === 'GB' ? (
				<ClearPay />
			) : (
				<AfterPay />
			) }
			<span>{ strings.andMore }</span>
		</div>
	);
};

export default PaymentMethods;
