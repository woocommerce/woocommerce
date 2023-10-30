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
	WooPay,
	ApplePay,
	Giropay,
	GooglePay,
	CB,
	Discover,
	UnionPay,
	JCB,
} from './Icons';

export const PaymentMethodsIcons: React.VFC< {
	isWooPayEligible: boolean;
} > = ( { isWooPayEligible = false } ) => (
	<div className="woocommerce-recommended-payments-banner__footer_icon_container">
		<Visa />
		<MasterCard />
		<Amex />
		{ isWooPayEligible && <WooPay /> }
		<ApplePay />
		<GooglePay />
		<CB />
		<Giropay />
		<Discover />
		<UnionPay />
		<JCB />
		<Text variant="caption" as="p" size="12" lineHeight="16px">
			{ __( '& more.', 'woocommerce' ) }
		</Text>
	</div>
);
