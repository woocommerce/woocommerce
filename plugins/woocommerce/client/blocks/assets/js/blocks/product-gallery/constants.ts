/**
 * Centralized magic strings used across the Product Gallery frontend.
 *
 * Anything hardcoded that the gallery's runtime depends on — CSS class
 * names, attribute-based selectors, jQuery event namespaces — lives here
 * so call sites stay name-driven and refactors only touch one place.
 */

export const SELECTORS = {
	galleryContainer: '.wp-block-woocommerce-product-gallery',
	largeImageContainer: '.wc-block-product-gallery-large-image__container',
	largeImageWrapper: '.wc-block-product-gallery-large-image__wrapper',
	dialogContent: '.wc-block-product-gallery-dialog__content',
	thumbnail: '.wc-block-product-gallery-thumbnails__thumbnail',
	thumbnailsScrollable: '.wc-block-product-gallery-thumbnails__scrollable',
	legacyVariationIdInput: 'input[name="variation_id"]',
	legacyResetVariations: '.reset_variations',
	imgByImageId: ( imageId: number | string ): string =>
		`img[data-image-id="${ imageId }"]`,
	elementByImageId: ( imageId: number | string ): string =>
		`[data-image-id="${ imageId }"]`,
	cartFormForProduct: ( productId: number | string ): string =>
		`form[data-product_id="${ productId }"]`,
} as const;

export const CLASSES = {
	activeThumbnail:
		'wc-block-product-gallery-thumbnails__thumbnail__image--is-active',
	dialogOpenBody: 'wc-block-product-gallery-dialog-open',
} as const;

/**
 * jQuery event names used by the legacy Add to Cart variation form.
 * Suffixed with the `.wc-product-gallery` namespace so we can `.off()`
 * everything we bound without disturbing handlers added by other code.
 */
export const LEGACY_FORM_JQUERY_EVENTS = {
	namespace: '.wc-product-gallery',
	foundVariation: 'found_variation.wc-product-gallery',
	hideOrResetVariation:
		'hide_variation.wc-product-gallery reset_data.wc-product-gallery',
} as const;
