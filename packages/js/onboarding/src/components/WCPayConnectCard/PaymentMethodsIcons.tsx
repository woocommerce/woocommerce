/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import {
	Visa,
	MasterCard,
	Amex,
	ApplePay,
	GooglePay,
	CB,
	Discover,
} from '../../images/cards';
import {
	WooPay,
	Ideal,
	Klarna,
	Affirm,
	Clearpay,
	Afterpay,
} from '../../images/payment-methods';

export const PaymentMethodsIcons: React.VFC< {
	businessCountry?: string;
	isWooPayEligible: boolean;
} > = ( { businessCountry = '', isWooPayEligible = false } ) => (
	<div className="wcpay-connect-card__payment-methods__icons-container">
		<Visa />
		<MasterCard />
		<Amex />
		<CB />
		<Discover />
		<Ideal />
		<ApplePay />
		<GooglePay />
		{ isWooPayEligible && <WooPay /> }
		<Klarna />
		<Affirm />
		{ businessCountry === 'GB' ? <Clearpay /> : <Afterpay /> }
		<Text variant="caption" as="span" size="12" lineHeight="16px">
			{ __( '& more', 'woocommerce' ) }
		</Text>
	</div>
);
