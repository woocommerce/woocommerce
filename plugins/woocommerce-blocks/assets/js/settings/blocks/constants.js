/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

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
export const SHOP_URL = getSetting( 'shopUrl', '' );
export const CHECKOUT_URL = getSetting( 'checkoutUrl', '' );
export const COUPONS_ENABLED = getSetting( 'couponsEnabled', true );
export const DISPLAY_PRICES_INCLUDING_TAXES = getSetting(
	'displayPricesIncludingTaxes',
	false
);
export const PRODUCT_COUNT = getSetting( 'productCount', 0 );
export const ATTRIBUTES = getSetting( 'attributes', [] );
export const WC_BLOCKS_ASSET_URL = getSetting( 'wcBlocksAssetUrl', '' );
export const SHIPPING_COUNTRIES = getSetting( 'shippingCountries', {} );
export const ALLOWED_COUNTRIES = getSetting( 'allowedCountries', {} );
export const SHIPPING_COUNTIES = getSetting( 'shippingCounties', {} );
export const ALLOWED_COUNTIES = getSetting( 'allowedCounties', {} );
