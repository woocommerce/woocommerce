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
	UnionPay,
	JCB,
	Sofort,
} from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import strings from './strings';

const PaymentMethods: React.FC = () => {
	return (
		<div className="woopayments-welcome-page__payment-methods">
			<Visa />
			<MasterCard />
			<Amex />
			<ApplePay />
			<GooglePay />
			<CB />
			<UnionPay />
			<JCB />
			<Sofort /> { strings.andMore }
		</div>
	);
};

export default PaymentMethods;
