/**
 * External dependencies
 */
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Visa from '../images/cards/visa.js';
import MasterCard from '../images/cards/mastercard.js';
import Maestro from '../images/cards/maestro.js';
import Amex from '../images/cards/amex.js';
import ApplePay from '../images/cards/applepay.js';
import CB from '../images/cards/cb.js';
import DinersClub from '../images/cards/diners.js';
import Discover from '../images/cards/discover.js';
import JCB from '../images/cards/jcb.js';
import UnionPay from '../images/cards/unionpay.js';

export const WCPayAcceptedMethods = () => (
	<>
		<Text as="h3" variant="label">
			{ __( 'Accepted payment methods', 'woocommerce-admin' ) }
		</Text>

		<div className="woocommerce-task-payment-wcpay__accepted">
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
	</>
);
