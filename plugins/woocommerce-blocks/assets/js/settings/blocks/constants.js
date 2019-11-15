/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export const ENABLE_REVIEW_RATING = getSetting( 'enableReviewRating', true );
export const SHOW_AVATARS = getSetting( 'showAvatars', true );
export const MAX_COLUMNS = getSetting( 'max_columns', 6 );
export const MIN_COLUMNS = getSetting( 'min_columns', 1 );
export const DEFAULT_COLUMNS = getSetting( 'default_columns', 3 );
export const MAX_ROWS = getSetting( 'max_rows', 6 );
export const MIN_ROWS = getSetting( 'min_rows', 1 );
export const DEFAULT_ROWS = getSetting( 'default_rows', 2 );
export const MIN_HEIGHT = getSetting( 'min_height', 500 );
export const DEFAULT_HEIGHT = getSetting( 'default_height', 500 );
export const PLACEHOLDER_IMG_SRC = getSetting( 'placeholderImgSrc', '' );
export const THUMBNAIL_SIZE = getSetting( 'thumbnail_size', 300 );
export const IS_LARGE_CATALOG = getSetting( 'isLargeCatalog' );
export const LIMIT_TAGS = getSetting( 'limitTags' );
export const HAS_PRODUCTS = getSetting( 'hasProducts', true );
export const HAS_TAGS = getSetting( 'hasTags', true );
export const HOME_URL = getSetting( 'homeUrl', '' );
export const PRODUCT_COUNT = getSetting( 'productCount', 0 );
export const ATTRIBUTES = getSetting( 'attributes', [] );
export const WC_BLOCKS_ASSET_URL = getSetting( 'wcBlocksAssetUrl', '' );
