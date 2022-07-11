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
	Giropay,
	GooglePay,
	CB,
	DinersClub,
	Discover,
	UnionPay,
	JCB,
	Sofort,
} from './Icons';

export const PaymentMethodsIcons = () => (
	<div className="woocommerce-recommended-payments-banner__footer_icon_container">
		<Visa />
		<MasterCard />
		<Amex />
		<ApplePay />
		<GooglePay />
		<CB />
		<Giropay />
		<DinersClub />
		<Discover />
		<UnionPay />
		<JCB />
		<Sofort />
		<Text variant="caption" as="p" size="12" lineHeight="16px">
			{ __( '& more.', 'woocommerce' ) }
		</Text>
	</div>
);
