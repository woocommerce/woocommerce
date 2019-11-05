const getConstantFromData = ( property, fallback = false ) => {
	if ( typeof wc_product_block_data === 'object' && wc_product_block_data.hasOwnProperty( property ) ) {
		return wc_product_block_data[ property ];
	}
	return fallback;
};

export const ENABLE_REVIEW_RATING = getConstantFromData( 'enableReviewRating', true );
export const SHOW_AVATARS = getConstantFromData( 'showAvatars', true );
export const MAX_COLUMNS = getConstantFromData( 'max_columns', 6 );
export const MIN_COLUMNS = getConstantFromData( 'min_columns', 1 );
export const DEFAULT_COLUMNS = getConstantFromData( 'default_columns', 3 );
export const MAX_ROWS = getConstantFromData( 'max_rows', 6 );
export const MIN_ROWS = getConstantFromData( 'min_rows', 1 );
export const DEFAULT_ROWS = getConstantFromData( 'default_rows', 1 );
export const MIN_HEIGHT = getConstantFromData( 'min_height', 500 );
export const DEFAULT_HEIGHT = getConstantFromData( 'default_height', 500 );
export const PLACEHOLDER_IMG_SRC = getConstantFromData( 'placeholderImgSrc ', '' );
export const THUMBNAIL_SIZE = getConstantFromData( 'thumbnail_size', 300 );
export const IS_LARGE_CATALOG = getConstantFromData( 'isLargeCatalog' );
export const LIMIT_TAGS = getConstantFromData( 'limitTags' );
export const HAS_TAGS = getConstantFromData( 'hasTags', true );
export const HOME_URL = getConstantFromData( 'homeUrl', '' );
export const PRODUCT_CATEGORIES = getConstantFromData( 'productCategories', [] );
export { ENDPOINTS } from './endpoints';
