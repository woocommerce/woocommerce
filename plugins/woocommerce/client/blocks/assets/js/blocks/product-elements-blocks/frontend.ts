/**
 * External dependencies
 */
import {
	getElement,
	store,
	getContext,
	getConfig,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type { ProductResponseItem } from '@woocommerce/types';
import { formatPriceWithCurrency } from '@woocommerce/price-format/utils/currency';

/**
 * Internal dependencies
 */
import {
	swapPreformattedHtml,
	PRODUCT_ELEMENT_HTML_CONFIG,
} from '../../base/utils/preformatted-html';

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
};

type SaleBadgeContext = {
	saleBadgeText: string;
	saleBadgeScreenReaderText: string;
	badgeContent: string;
	prefix: string;
	suffix: string;
};

type Context = {
	productElementKey: keyof ProductResponseItem;
};

const { state } = store(
	'woocommerce/product-elements',
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
				const regular = Number( variation.prices.regular_price );
				const discount = regular - Number( variation.prices.price );
				if ( ! ( regular > 0 && discount > 0 ) ) {
					return context.saleBadgeText;
				}
				const percentage = Math.round( ( discount / regular ) * 100 );
				if ( context.badgeContent === 'percentage' && percentage < 1 ) {
					return null;
				}
				const config = getConfig() as SaleBadgeConfig;
				const prices = variation.prices;
				const value =
					context.badgeContent === 'percentage'
						? config.saleBadgePercentageTemplate.replace(
								'%s',
								String( percentage )
						  )
						: formatPriceWithCurrency( discount, {
								code: prices.currency_code,
								symbol: prices.currency_symbol,
								minorUnit: prices.currency_minor_unit,
								decimalSeparator:
									prices.currency_decimal_separator,
								thousandSeparator:
									prices.currency_thousand_separator,
								prefix: prices.currency_prefix,
								suffix: prices.currency_suffix,
						  } );
				return `${ context.prefix }${ value }${ context.suffix }`;
			},
			get saleBadgeScreenReaderText(): string {
				const config = getConfig() as SaleBadgeConfig;
				return productsState.productVariationInContext
					? config.saleBadgeScreenReaderTemplate.replace(
							'%s',
							() => state.saleBadgeText ?? ''
					  )
					: getContext< SaleBadgeContext >()
							.saleBadgeScreenReaderText;
			},
			get isSaleBadgeHidden(): boolean {
				return state.saleBadgeText === null;
			},
		},
		callbacks: {
			updateValue: () => {
				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				const { productElementKey } = getContext< Context >();

				swapPreformattedHtml(
					getElement().ref,
					product[ productElementKey ],
					PRODUCT_ELEMENT_HTML_CONFIG
				);
			},
		},
	},
	{ lock: true }
);
