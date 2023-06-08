/**
 * External dependencies
 */
import {
	Visa,
	MasterCard,
	Maestro,
	Amex,
	ApplePay,
	CB,
	DinersClub,
	Discover,
	JCB,
	UnionPay,
} from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import strings from './strings';

const PaymentMethods: React.FC = () => {
	return (
		<div className="wcpay-connect-account-page-payment-methods">
			<Visa />
			<MasterCard />
			<Maestro />
			<Amex />
			<DinersClub />
			<CB />
			<Discover />
			<UnionPay />
			<JCB />
			<ApplePay />
		</div>
	);
};

export default PaymentMethods;
