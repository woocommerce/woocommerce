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
			<JCB /> { strings.andMore }
		</div>
	);
};

export default PaymentMethods;
