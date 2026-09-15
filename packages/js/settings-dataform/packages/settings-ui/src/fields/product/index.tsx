/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SETTINGS_ENTITY } from '../../constants';
import { unlock } from '../../unlock';
import shopPageId from './shop-page-id';
import cartRedirectAfterAdd from './cart-redirect-after-add';
import enableAjaxAddToCart from './enable-ajax-add-to-cart';
import placeholderImage from './placeholder-image';
import weightUnit from './weight-unit';
import dimensionUnit from './dimension-unit';
import enableReviews from './enable-reviews';
import reviewRatingVerificationLabel from './review-rating-verification-label';
import reviewRatingVerificationRequired from './review-rating-verification-required';
import enableReviewRating from './enable-review-rating';
import reviewRatingRequired from './review-rating-required';
import manageStock from './manage-stock';
import holdStockMinutes from './hold-stock-minutes';
import notifyLowStock from './notify-low-stock';
import notifyNoStock from './notify-no-stock';
import notifyBackorder from './notify-backorder';
import stockEmailRecipient from './stock-email-recipient';
import notifyLowStockAmount from './notify-low-stock-amount';
import notifyNoStockAmount from './notify-no-stock-amount';
import hideOutOfStockItems from './hide-out-of-stock-items';
import stockFormat from './stock-format';
import fileDownloadMethod from './file-download-method';
import downloadsRedirectFallbackAllowed from './downloads-redirect-fallback-allowed';
import downloadsRequireLogin from './downloads-require-login';
import downloadsGrantAccessAfterPayment from './downloads-grant-access-after-payment';
import downloadsDeliverInline from './downloads-deliver-inline';
import downloadsAddHashToFilename from './downloads-add-hash-to-filename';
import downloadsCountPartial from './downloads-count-partial';
import attributeLookupEnabled from './attribute-lookup-enabled';
import attributeLookupDirectUpdates from './attribute-lookup-direct-updates';
import attributeLookupOptimizedUpdates from './attribute-lookup-optimized-updates';
import productMatchFeaturedImageBySku from './product-match-featured-image-by-sku';

const fields = [
	shopPageId,
	cartRedirectAfterAdd,
	enableAjaxAddToCart,
	placeholderImage,
	weightUnit,
	dimensionUnit,
	enableReviews,
	reviewRatingVerificationLabel,
	reviewRatingVerificationRequired,
	enableReviewRating,
	reviewRatingRequired,
	manageStock,
	holdStockMinutes,
	notifyLowStock,
	notifyNoStock,
	notifyBackorder,
	stockEmailRecipient,
	notifyLowStockAmount,
	notifyNoStockAmount,
	hideOutOfStockItems,
	stockFormat,
	fileDownloadMethod,
	downloadsRedirectFallbackAllowed,
	downloadsRequireLogin,
	downloadsGrantAccessAfterPayment,
	downloadsDeliverInline,
	downloadsAddHashToFilename,
	downloadsCountPartial,
	attributeLookupEnabled,
	attributeLookupDirectUpdates,
	attributeLookupOptimizedUpdates,
	productMatchFeaturedImageBySku,
];
const registeredProductEntities = new Set< string >();

export function registerProductFields( entity = SETTINGS_ENTITY ) {
	const entityKey = JSON.stringify( [ entity.kind, entity.name ] );
	if ( registeredProductEntities.has( entityKey ) ) {
		return;
	}

	const { registerEntityField } = unlock( dispatch( editorStore ) );

	fields.forEach( ( field ) => {
		registerEntityField( entity.kind, entity.name, field );
	} );
	registeredProductEntities.add( entityKey );
}
