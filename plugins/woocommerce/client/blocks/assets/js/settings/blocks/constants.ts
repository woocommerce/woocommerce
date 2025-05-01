/**
 * External dependencies
 */
import { getSetting, STORE_PAGES } from '@woocommerce/settings';
import { CountryData } from '@woocommerce/types';
import type {
	OrderForm,
	AddressForm,
	ContactForm,
} from '@woocommerce/settings';

export type WordCountType =
	| 'words'
	| 'characters_excluding_spaces'
	| 'characters_including_spaces';

export interface WcBlocksConfig {
	pluginUrl: string;
	productCount?: number;
	defaultAvatar: string;
	restApiRoutes: Record< string, string[] >;
	wordCountType: WordCountType;
	experimentalBlocksEnabled?: boolean;
}

export const blocksConfig = getSetting( 'wcBlocksConfig', {
	pluginUrl: '',
	productCount: 0,
	defaultAvatar: '',
	restApiRoutes: {},
	wordCountType: 'words',
} ) as WcBlocksConfig;

export const WC_BLOCKS_IMAGE_URL = blocksConfig.pluginUrl + 'assets/images/';
export const WC_BLOCKS_BUILD_URL =
	blocksConfig.pluginUrl + 'assets/client/blocks/';
export const SHOP_URL = STORE_PAGES.shop?.permalink;
export const CHECKOUT_PAGE_ID = STORE_PAGES.checkout?.id;
export const CHECKOUT_URL = STORE_PAGES.checkout?.permalink;
export const PRIVACY_URL = STORE_PAGES.privacy?.permalink;
export const PRIVACY_PAGE_NAME = STORE_PAGES.privacy?.title;
export const TERMS_URL = STORE_PAGES.terms?.permalink;
export const TERMS_PAGE_NAME = STORE_PAGES.terms?.title;
export const CART_PAGE_ID = STORE_PAGES.cart?.id;
export const CART_URL = STORE_PAGES.cart?.permalink;
export const LOGIN_URL = STORE_PAGES.myaccount?.permalink
	? STORE_PAGES.myaccount.permalink
	: getSetting( 'wpLoginUrl', '/wp-login.php' );

export const LOCAL_PICKUP_ENABLED = getSetting< boolean >(
	'localPickupEnabled',
	false
);

export const SHIPPING_METHODS_EXIST = getSetting< boolean >(
	'shippingMethodsExist',
	false
);
export const SHIPPING_ENABLED = getSetting< boolean >(
	'shippingEnabled',
	true
);

type FieldsLocations = {
	address: Array< keyof AddressForm >;
	contact: Array< keyof ContactForm >;
	order: Array< keyof OrderForm >;
};

// Contains country names.
const countries = getSetting< Record< string, string > >( 'countries', {} );

// Contains country settings.
const countryData = getSetting< Record< string, CountryData > >(
	'countryData',
	{}
);

export const ALLOWED_COUNTRIES = Object.fromEntries(
	Object.keys( countryData )
		.filter( ( countryCode ) => {
			return countryData[ countryCode ].allowBilling === true;
		} )
		.map( ( countryCode ) => {
			return [ countryCode, countries[ countryCode ] || '' ];
		} )
);

export const SHIPPING_COUNTRIES = Object.fromEntries(
	Object.keys( countryData )
		.filter( ( countryCode ) => {
			return countryData[ countryCode ].allowShipping === true;
		} )
		.map( ( countryCode ) => {
			return [ countryCode, countries[ countryCode ] || '' ];
		} )
);

// Previously we used ALLOWED_COUNTRIES and SHIPPING_COUNTRIES, however, this lead to problems when syncing values
// between billing and shipping if some countries were not available for shipping or vice versa. To get around this,
// we combine countries available for billing and/or shipping so all are available for selection.
export const COUNTRIES = {
	...ALLOWED_COUNTRIES,
	...SHIPPING_COUNTRIES,
};

export const STATES = Object.fromEntries(
	Object.keys( COUNTRIES ).map( ( countryCode ) => {
		return [ countryCode, countryData[ countryCode ].states || {} ];
	} )
);

export const COUNTRY_LOCALE = Object.fromEntries(
	Object.keys( COUNTRIES ).map( ( countryCode ) => {
		return [ countryCode, countryData[ countryCode ].locale || {} ];
	} )
);

const defaultFieldsLocations: FieldsLocations = {
	address: [
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'postcode',
		'country',
		'state',
		'phone',
	],
	contact: [ 'email' ],
	order: [],
};

export const ADDRESS_FORM_KEYS = getSetting< FieldsLocations >(
	'addressFieldsLocations',
	defaultFieldsLocations
).address;

export const CONTACT_FORM_KEYS = getSetting< FieldsLocations >(
	'addressFieldsLocations',
	defaultFieldsLocations
).contact;

export const ORDER_FORM_KEYS = getSetting< FieldsLocations >(
	'addressFieldsLocations',
	defaultFieldsLocations
).order;

export interface CheckoutField {
	label: string;
	type: string;
	options: { label: string; value: string }[];
}

export const ORDER_FORM_FIELDS = getSetting< CheckoutField[] >(
	'additionalOrderFields',
	{}
);
export const CONTACT_FORM_FIELDS = getSetting< CheckoutField[] >(
	'additionalContactFields',
	{}
);
export const ADDRESS_FORM_FIELDS = getSetting< CheckoutField[] >(
	'additionalAddressFields',
	{}
);
