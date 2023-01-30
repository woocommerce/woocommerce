/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	PaymentCardIcon,
	InternationalMarketIcon,
	EarnManageIcon,
} from './icons';

export const WCPayBenefits: React.VFC = () => {
	return (
		<Flex className="woocommerce-wcpay-benefits" align="top">
			<Flex className="woocommerce-wcpay-benefits-benefit">
				<PaymentCardIcon />
				<Text as="p">
					{ __(
						'Offer your customers their preferred way to pay including debit and credit card payments, Apple Pay, Sofort, SEPA, iDeal and many more.',
						'woocommerce'
					) }
				</Text>
			</Flex>
			<Flex className="woocommerce-wcpay-benefits-benefit">
				<InternationalMarketIcon />
				<Text as="p">
					{ __(
						'Sell to international markets and accept more than 135 currencies with local payment methods.',
						'woocommerce'
					) }
				</Text>
			</Flex>
			<Flex className="woocommerce-wcpay-benefits-benefit">
				<EarnManageIcon />
				<Text as="p">
					{ __(
						'Earn and manage recurring revenue and get automatic deposits into your nominated bank account.',
						'woocommerce'
					) }
				</Text>
			</Flex>
		</Flex>
	);
};
