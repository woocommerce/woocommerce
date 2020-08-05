/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export const CURRENT_USER_IS_ADMIN = getSetting( 'currentUserIsAdmin', false );
export const REVIEW_RATINGS_ENABLED = getSetting(
	'reviewRatingsEnabled',
	true
);
export const SHOW_AVATARS = getSetting( 'showAvatars', true );
export const MAX_COLUMNS = getSetting( 'max_columns', 6 );
export const MIN_COLUMNS = getSetting( 'min_columns', 1 );
export const DEFAULT_COLUMNS = getSetting( 'default_columns', 3 );
export const MAX_ROWS = getSetting( 'max_rows', 6 );
export const MIN_ROWS = getSetting( 'min_rows', 1 );
export const DEFAULT_ROWS = getSetting( 'default_rows', 3 );
export const MIN_HEIGHT = getSetting( 'min_height', 500 );
export const DEFAULT_HEIGHT = getSetting( 'default_height', 500 );
export const PLACEHOLDER_IMG_SRC = getSetting( 'placeholderImgSrc', '' );
export const THUMBNAIL_SIZE = getSetting( 'thumbnail_size', 300 );
export const IS_LARGE_CATALOG = getSetting( 'isLargeCatalog' );
export const LIMIT_TAGS = getSetting( 'limitTags' );
export const HAS_PRODUCTS = getSetting( 'hasProducts', true );
export const HAS_TAGS = getSetting( 'hasTags', true );
export const HOME_URL = getSetting( 'homeUrl', '' );
export const COUPONS_ENABLED = getSetting( 'couponsEnabled', true );
export const SHIPPING_ENABLED = getSetting( 'shippingEnabled', true );
export const TAXES_ENABLED = getSetting( 'taxesEnabled', true );
export const DISPLAY_ITEMIZED_TAXES = getSetting(
	'displayItemizedTaxes',
	false
);
export const DISPLAY_SHOP_PRICES_INCLUDING_TAX = getSetting(
	'displayShopPricesIncludingTax',
	false
);
export const DISPLAY_CART_PRICES_INCLUDING_TAX = getSetting(
	'displayCartPricesIncludingTax',
	false
);
export const PRODUCT_COUNT = getSetting( 'productCount', 0 );
export const ATTRIBUTES = getSetting( 'attributes', [] );
export const IS_SHIPPING_CALCULATOR_ENABLED = getSetting(
	'isShippingCalculatorEnabled',
	true
);
export const IS_SHIPPING_COST_HIDDEN = getSetting(
	'isShippingCostHidden',
	false
);
export const WOOCOMMERCE_BLOCKS_PHASE = getSetting(
	'woocommerceBlocksPhase',
	1
);
export const WC_BLOCKS_ASSET_URL = getSetting( 'wcBlocksAssetUrl', '' );
export const WC_BLOCKS_BUILD_URL = getSetting( 'wcBlocksBuildUrl', '' );
export const SHIPPING_COUNTRIES = getSetting( 'shippingCountries', {} );
export const ALLOWED_COUNTRIES = getSetting( 'allowedCountries', {} );
export const SHIPPING_STATES = getSetting( 'shippingStates', {} );
export const ALLOWED_STATES = getSetting( 'allowedStates', {} );
export const SHIPPING_METHODS_EXIST = getSetting(
	'shippingMethodsExist',
	false
);

export const PAYMENT_GATEWAY_SORT_ORDER = getSetting( 'paymentGatewaySortOrder', [] );

export const CHECKOUT_SHOW_LOGIN_REMINDER = getSetting(
	'checkoutShowLoginReminder',
	true
);

const defaultPage = {
	id: 0,
	title: '',
	permalink: '',
};
const storePages = getSetting( 'storePages', {
	shop: defaultPage,
	cart: defaultPage,
	checkout: defaultPage,
	privacy: defaultPage,
	terms: defaultPage,
} );
export const SHOP_URL = storePages.shop.permalink;

export const CHECKOUT_PAGE_ID = storePages.checkout.id;
export const CHECKOUT_URL = storePages.checkout.permalink;

export const PRIVACY_URL = storePages.privacy.permalink;
export const PRIVACY_PAGE_NAME = storePages.privacy.title;

export const TERMS_URL = storePages.terms.permalink;
export const TERMS_PAGE_NAME = storePages.terms.title;

export const CART_PAGE_ID = storePages.cart.id;
export const CART_URL = storePages.cart.permalink;

export const CHECKOUT_ALLOWS_GUEST = getSetting( 'checkoutAllowsGuest', false );
export const CHECKOUT_ALLOWS_SIGNUP = getSetting(
	'checkoutAllowsSignup',
	false
);
