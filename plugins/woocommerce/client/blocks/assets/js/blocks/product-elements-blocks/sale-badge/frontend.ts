/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import { formatPriceWithCurrency } from '@woocommerce/price-format/utils/currency';
import type { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { getSaleBadgeDiscount } from './utils';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

type SaleBadgeConfig = {
	saleBadgePercentageTemplate: string;
	saleBadgeScreenReaderTemplate: string;
	storePriceDecimals?: number;
};

type SaleBadgeContext = {
	saleBadgeText: string;
	saleBadgeScreenReaderText: string;
	badgeContent: string;
	prefix: string;
	suffix: string;
};

const { state } = store(
	'woocommerce/product-sale-badge',
	{
		state: {
			get saleBadgeText(): string | null {
				const variation = productsState.productVariationInContext;
				const context = getContext< SaleBadgeContext >();
				if ( ! variation ) {
					return context.saleBadgeText;
				}
				if ( ! variation.on_sale ) {
					return null;
				}
				if (
					! [ 'amount', 'percentage' ].includes(
						context.badgeContent
					)
				) {
					return context.saleBadgeText;
				}
				const prices = variation.prices;
				const discount = getSaleBadgeDiscount( prices );
				if ( ! discount ) {
					return context.saleBadgeText;
				}
				if (
					context.badgeContent === 'percentage' &&
					discount.percentage === 0
				) {
					return null;
				}
				const config = getConfig(
					'woocommerce/product-sale-badge'
				) as SaleBadgeConfig;
				let value: string;
				if ( context.badgeContent === 'percentage' ) {
					value = config.saleBadgePercentageTemplate.replace(
						'%s',
						String( discount.percentage )
					);
				} else {
					const priceCurrency = {
						code: prices.currency_code,
						symbol: prices.currency_symbol,
						minorUnit: prices.currency_minor_unit,
						decimalSeparator: prices.currency_decimal_separator,
						thousandSeparator: prices.currency_thousand_separator,
						prefix: prices.currency_prefix,
						suffix: prices.currency_suffix,
					} as Currency;
					// Prices shown as entered can have more decimals than the store's (£0.317 − £0.207), so drop zeros
					// past the store's decimals: £0.11, not £0.110.
					value = formatPriceWithCurrency(
						discount.amount,
						{
							...priceCurrency,
							minorUnit:
								config.storePriceDecimals ??
								prices.currency_minor_unit,
						},
						{ minorUnit: prices.currency_minor_unit }
					);
				}
				return `${ context.prefix }${ value }${ context.suffix }`;
			},
			get saleBadgeScreenReaderText(): string {
				const config = getConfig(
					'woocommerce/product-sale-badge'
				) as SaleBadgeConfig;
				if ( ! productsState.productVariationInContext ) {
					return getContext< SaleBadgeContext >()
						.saleBadgeScreenReaderText;
				}
				return config.saleBadgeScreenReaderTemplate.replace(
					'%s',
					() => state.saleBadgeText ?? ''
				);
			},
			get isSaleBadgeHidden(): boolean {
				return state.saleBadgeText === null;
			},
		},
	},
	{ lock: true }
);
