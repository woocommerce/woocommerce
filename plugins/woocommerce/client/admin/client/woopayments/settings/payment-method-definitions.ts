/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

export type WooPaymentsPaymentMethodDefinition = {
	id: string;
	label: string;
	description: string;
	iconUrl: string;
	stripeKey: string;
	currencies: string[];
	allowsManualCapture: boolean;
	allowsPayLater: boolean;
};

export type WooPaymentsCardBrand = {
	id: string;
	label: string;
	iconUrl: string;
};

const assetUrl = ( path: string ) => `${ WC_ASSET_URL || '' }${ path }`;

const CURRENCIES = {
	all: [],
	aud: [ 'AUD' ],
	cad: [ 'CAD' ],
	chf: [ 'CHF' ],
	cny: [ 'CNY' ],
	dkk: [ 'DKK' ],
	eur: [ 'EUR' ],
	gbp: [ 'GBP' ],
	hkd: [ 'HKD' ],
	huf: [ 'HUF' ],
	jpy: [ 'JPY' ],
	nok: [ 'NOK' ],
	nzd: [ 'NZD' ],
	pln: [ 'PLN' ],
	sek: [ 'SEK' ],
	sgd: [ 'SGD' ],
	usd: [ 'USD' ],
	noneSupported: [ 'NONE_SUPPORTED' ],
	affirm: [ 'USD', 'CAD' ],
	afterpayClearpay: [ 'USD', 'CAD', 'AUD', 'NZD', 'GBP' ],
	klarna: [ 'USD', 'GBP', 'EUR', 'DKK', 'NOK', 'SEK' ],
	p24: [ 'EUR', 'PLN' ],
};

const ALIPAY_EUR_COUNTRIES = new Set( [
	'AT',
	'BE',
	'BG',
	'CY',
	'CZ',
	'DK',
	'EE',
	'FI',
	'FR',
	'DE',
	'GR',
	'IE',
	'IT',
	'LV',
	'LT',
	'LU',
	'MT',
	'NL',
	'NO',
	'PT',
	'RO',
	'SK',
	'SI',
	'ES',
	'SE',
	'CH',
	'HR',
] );

const WECHAT_PAY_EUR_COUNTRIES = new Set( [
	'AT',
	'BE',
	'FI',
	'FR',
	'DE',
	'IE',
	'IT',
	'LU',
	'NL',
	'PT',
	'ES',
] );

const getNormalizedAccountCountry = ( accountCountry?: string ) =>
	accountCountry?.toUpperCase() || '';

const getAlipayCurrencies = ( accountCountry?: string ) => {
	const normalizedCountry = getNormalizedAccountCountry( accountCountry );

	switch ( normalizedCountry ) {
		case 'AU':
			return CURRENCIES.aud;
		case 'CA':
			return CURRENCIES.cad;
		case 'GB':
			return CURRENCIES.gbp;
		case 'HK':
			return CURRENCIES.hkd;
		case 'JP':
			return CURRENCIES.jpy;
		case 'NZ':
			return CURRENCIES.nzd;
		case 'SG':
			return CURRENCIES.sgd;
		case 'US':
			return CURRENCIES.usd;
		case 'HU':
			return CURRENCIES.huf;
	}

	if ( ALIPAY_EUR_COUNTRIES.has( normalizedCountry ) ) {
		return CURRENCIES.eur;
	}

	return CURRENCIES.cny;
};

const getWechatPayCurrencies = ( accountCountry?: string ) => {
	const normalizedCountry = getNormalizedAccountCountry( accountCountry );

	if ( WECHAT_PAY_EUR_COUNTRIES.has( normalizedCountry ) ) {
		return CURRENCIES.eur;
	}

	switch ( normalizedCountry ) {
		case 'AU':
			return CURRENCIES.aud;
		case 'CA':
			return CURRENCIES.cad;
		case 'DK':
			return CURRENCIES.dkk;
		case 'HK':
			return CURRENCIES.hkd;
		case 'JP':
			return CURRENCIES.jpy;
		case 'NO':
			return CURRENCIES.nok;
		case 'SG':
			return CURRENCIES.sgd;
		case 'SE':
			return CURRENCIES.sek;
		case 'CH':
			return CURRENCIES.chf;
		case 'GB':
			return CURRENCIES.gbp;
		case 'US':
			return CURRENCIES.usd;
	}

	return CURRENCIES.noneSupported;
};

export const CARD_BRANDS: WooPaymentsCardBrand[] = [
	{
		id: 'visa',
		label: 'Visa',
		iconUrl: assetUrl( 'images/payment-methods-cards/visa.svg' ),
	},
	{
		id: 'mastercard',
		label: 'Mastercard',
		iconUrl: assetUrl( 'images/payment-methods-cards/mastercard.svg' ),
	},
	{
		id: 'amex',
		label: 'American Express',
		iconUrl: assetUrl( 'images/payment-methods-cards/amex.svg' ),
	},
	{
		id: 'discover',
		label: 'Discover',
		iconUrl: assetUrl( 'images/payment-methods-cards/discover.svg' ),
	},
	{
		id: 'diners',
		label: 'Diners Club',
		iconUrl: assetUrl( 'images/icons/credit-cards/diners.svg' ),
	},
	{
		id: 'jcb',
		label: 'JCB',
		iconUrl: assetUrl( 'images/payment-methods-cards/jcb.svg' ),
	},
	{
		id: 'unionpay',
		label: 'UnionPay',
		iconUrl: assetUrl( 'images/payment-methods/unionpay.svg' ),
	},
	{
		id: 'cartes_bancaires',
		label: 'Cartes Bancaires',
		iconUrl: assetUrl(
			'images/payment-methods-cards/cartes_bancaires.svg'
		),
	},
];

const afterpayClearpayBase = {
	id: 'afterpay_clearpay',
	stripeKey: 'afterpay_clearpay_payments',
	currencies: CURRENCIES.afterpayClearpay,
	allowsManualCapture: false,
	allowsPayLater: true,
};

const getAfterpayClearpayDefinition = (
	accountCountry?: string
): WooPaymentsPaymentMethodDefinition => {
	switch ( accountCountry?.toUpperCase() ) {
		case 'GB':
			return {
				...afterpayClearpayBase,
				label: __( 'Clearpay', 'woocommerce' ),
				description: __(
					'Allow customers to pay over time with Clearpay.',
					'woocommerce'
				),
				iconUrl: assetUrl( 'images/payment-methods/clearpay.svg' ),
			};
		case 'US':
			return {
				...afterpayClearpayBase,
				label: __( 'Cash App Afterpay', 'woocommerce' ),
				description: __(
					'Allow customers to pay over time with Cash App Afterpay.',
					'woocommerce'
				),
				iconUrl: assetUrl(
					'images/payment-methods/afterpay-cashapp-badge.svg'
				),
			};
		default:
			return {
				...afterpayClearpayBase,
				label: __( 'Afterpay', 'woocommerce' ),
				description: __(
					'Allow customers to pay over time with Afterpay.',
					'woocommerce'
				),
				iconUrl: assetUrl( 'images/payment-methods/afterpay-logo.svg' ),
			};
	}
};

const PAYMENT_METHOD_DEFINITIONS: Record<
	string,
	WooPaymentsPaymentMethodDefinition
> = {
	card: {
		id: 'card',
		label: __( 'Credit / Debit Cards', 'woocommerce' ),
		description: __(
			'Let your customers pay with major credit and debit cards without leaving your store.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/icons/credit-cards/visa.svg' ),
		stripeKey: 'card_payments',
		currencies: CURRENCIES.all,
		allowsManualCapture: true,
		allowsPayLater: false,
	},
	alipay: {
		id: 'alipay',
		label: __( 'Alipay', 'woocommerce' ),
		description: __(
			'A digital wallet for customers with mainland China Alipay accounts. Regional versions like AlipayHK are not supported.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/alipay.svg' ),
		stripeKey: 'alipay_payments',
		currencies: getAlipayCurrencies(),
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	au_becs_debit: {
		id: 'au_becs_debit',
		label: __( 'BECS Direct Debit', 'woocommerce' ),
		description: __(
			'Bulk Electronic Clearing System — Accept secure bank transfer from Australia.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/sepa.svg' ),
		stripeKey: 'au_becs_debit_payments',
		currencies: CURRENCIES.aud,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	bancontact: {
		id: 'bancontact',
		label: __( 'Bancontact', 'woocommerce' ),
		description: __(
			'Bancontact is a bank redirect payment method offered by more than 80% of online businesses in Belgium.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/bancontact.svg' ),
		stripeKey: 'bancontact_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	eps: {
		id: 'eps',
		label: __( 'EPS', 'woocommerce' ),
		description: __(
			'Accept your payment with EPS — a common payment method in Austria.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/eps.svg' ),
		stripeKey: 'eps_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	giropay: {
		id: 'giropay',
		label: __( 'giropay', 'woocommerce' ),
		description: __(
			"Expand your business with giropay — Germany's second most popular payment system.",
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/giropay.svg' ),
		stripeKey: 'giropay_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	grabpay: {
		id: 'grabpay',
		label: __( 'GrabPay', 'woocommerce' ),
		description: __(
			'A popular digital wallet for cashless payments in Singapore.',
			'woocommerce'
		),
		iconUrl: '',
		stripeKey: 'grabpay_payments',
		currencies: CURRENCIES.sgd,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	ideal: {
		id: 'ideal',
		label: __( 'iDEAL | Wero', 'woocommerce' ),
		description: __(
			"Expand your business with iDEAL | Wero — Netherlands's most popular payment method.",
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/ideal.svg' ),
		stripeKey: 'ideal_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	jcb: {
		id: 'jcb',
		label: __( 'JCB', 'woocommerce' ),
		description: __(
			'Let your customers pay with JCB, the only international payment brand based in Japan.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/jcb.svg' ),
		stripeKey: 'jcb_payments',
		currencies: CURRENCIES.jpy,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	multibanco: {
		id: 'multibanco',
		label: __( 'Multibanco', 'woocommerce' ),
		description: __(
			'A voucher based payment method for your customers in Portugal.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/multibanco.svg' ),
		stripeKey: 'multibanco_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	p24: {
		id: 'p24',
		label: __( 'Przelewy24 (P24)', 'woocommerce' ),
		description: __(
			'Accept payments with Przelewy24 (P24), the most popular payment method in Poland.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/p24.svg' ),
		stripeKey: 'p24_payments',
		currencies: CURRENCIES.p24,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	sepa_debit: {
		id: 'sepa_debit',
		label: __( 'SEPA Direct Debit', 'woocommerce' ),
		description: __(
			'Reach 500 million customers and over 20 million businesses across the European Union.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/sepa.svg' ),
		stripeKey: 'sepa_debit_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	sofort: {
		id: 'sofort',
		label: __( 'Sofort', 'woocommerce' ),
		description: __(
			'Accept secure bank transfers from Austria, Belgium, Germany, Italy, Netherlands, and Spain.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/sofort.svg' ),
		stripeKey: 'sofort_payments',
		currencies: CURRENCIES.eur,
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	wechat_pay: {
		id: 'wechat_pay',
		label: __( 'WeChat Pay', 'woocommerce' ),
		description: __(
			'A digital wallet for customers with mainland China WeChat Pay wallets. Regional versions like WeChat Pay HK are not supported.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment-methods/wechat.svg' ),
		stripeKey: 'wechat_pay_payments',
		currencies: getWechatPayCurrencies(),
		allowsManualCapture: false,
		allowsPayLater: false,
	},
	affirm: {
		id: 'affirm',
		label: __( 'Affirm', 'woocommerce' ),
		description: __(
			'Allow customers to pay over time with Affirm.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment_methods/72x72/affirm.png' ),
		stripeKey: 'affirm_payments',
		currencies: CURRENCIES.affirm,
		allowsManualCapture: false,
		allowsPayLater: true,
	},
	klarna: {
		id: 'klarna',
		label: __( 'Klarna', 'woocommerce' ),
		description: __(
			'Allow customers to pay over time or pay now with Klarna.',
			'woocommerce'
		),
		iconUrl: assetUrl( 'images/payment_methods/72x72/klarna.png' ),
		stripeKey: 'klarna_payments',
		currencies: CURRENCIES.klarna,
		allowsManualCapture: false,
		allowsPayLater: true,
	},
};

export const getPaymentMethodDefinition = (
	methodId: string,
	accountCountry?: string
): WooPaymentsPaymentMethodDefinition | undefined => {
	if ( methodId === 'afterpay_clearpay' ) {
		return getAfterpayClearpayDefinition( accountCountry );
	}

	const definition = PAYMENT_METHOD_DEFINITIONS[ methodId ];

	if ( ! definition ) {
		return undefined;
	}

	if ( methodId === 'alipay' ) {
		return {
			...definition,
			currencies: getAlipayCurrencies( accountCountry ),
		};
	}

	if ( methodId === 'wechat_pay' ) {
		return {
			...definition,
			currencies: getWechatPayCurrencies( accountCountry ),
		};
	}

	return definition;
};
