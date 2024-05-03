export const PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME =
	'woocommerce_product_editor_show_feedback_bar';
export const PRODUCT_EDITOR_FEEDBACK_CES_ACTION = 'product_editor';
export const NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME =
	'woocommerce_new_product_management_enabled';
export const SINGLE_VARIATION_NOTICE_DISMISSED_OPTION =
	'woocommerce_single_variation_notice_dismissed';

export const NUMBERS_AND_ALLOWED_CHARS = '[^-0-9%s1%s2]';
export const NUMBERS_AND_DECIMAL_SEPARATOR = '[^-\\d\\%s]+';
export const ONLY_ONE_DECIMAL_SEPARATOR = '[%s](?=%s*[%s])';

// This should never be a real slug value of any existing shipping class
export const ADD_NEW_SHIPPING_CLASS_OPTION_VALUE =
	'__ADD_NEW_SHIPPING_CLASS_OPTION__';
export const UNCATEGORIZED_CATEGORY_SLUG = 'uncategorized';
export const PRODUCT_VARIATION_TITLE_LIMIT = 32;
export const STANDARD_RATE_TAX_CLASS_SLUG = 'standard';

// Fill constants

export const TAB_GENERAL_ID = 'tab/general';
export const TAB_PRICING_ID = 'tab/pricing';
export const TAB_INVENTORY_ID = 'tab/inventory';
export const TAB_SHIPPING_ID = 'tab/shipping';
export const TAB_OPTIONS_ID = 'tab/options';

export const VARIANT_TAB_GENERAL_ID = `variant/${ TAB_GENERAL_ID }`;
export const VARIANT_TAB_PRICING_ID = `variant/${ TAB_PRICING_ID }`;
export const VARIANT_TAB_INVENTORY_ID = `variant/${ TAB_INVENTORY_ID }`;
export const VARIANT_TAB_SHIPPING_ID = `variant/${ TAB_SHIPPING_ID }`;

export const DETAILS_SECTION_ID = `${ TAB_GENERAL_ID }/details`;
export const IMAGES_SECTION_ID = `${ TAB_GENERAL_ID }/images`;
export const ATTRIBUTES_SECTION_ID = `${ TAB_GENERAL_ID }/attributes`;
export const PRICING_SECTION_BASIC_ID = `${ TAB_PRICING_ID }/basic`;
export const PRICING_SECTION_TAXES_ID = `${ TAB_PRICING_ID }/taxes`;
export const PRICING_SECTION_TAXES_ADVANCED_ID = `${ TAB_PRICING_ID }/taxes/advanced`;
export const INVENTORY_SECTION_ID = `${ TAB_INVENTORY_ID }/basic`;
export const INVENTORY_SECTION_ADVANCED_ID = `${ TAB_INVENTORY_ID }/advanced`;
export const SHIPPING_SECTION_BASIC_ID = `${ TAB_SHIPPING_ID }/basic`;
export const SHIPPING_SECTION_DIMENSIONS_ID = `${ TAB_SHIPPING_ID }/dimensions`;

export const VARIANT_PRICING_SECTION_BASIC_ID = `variant/${ PRICING_SECTION_BASIC_ID }`;
export const VARIANT_PRICING_SECTION_TAXES_ID = `variant/${ PRICING_SECTION_TAXES_ID }`;
export const VARIANT_PRICING_SECTION_TAXES_ADVANCED_ID = `variant/${ PRICING_SECTION_TAXES_ADVANCED_ID }`;
export const VARIANT_INVENTORY_SECTION_ID = `variant/${ INVENTORY_SECTION_ID }`;
export const VARIANT_INVENTORY_SECTION_ADVANCED_ID = `variant/${ INVENTORY_SECTION_ADVANCED_ID }`;
export const VARIANT_SHIPPING_SECTION_BASIC_ID = `variant/${ SHIPPING_SECTION_BASIC_ID }`;
export const VARIANT_SHIPPING_SECTION_DIMENSIONS_ID = `variant/${ SHIPPING_SECTION_DIMENSIONS_ID }`;

export const PRODUCT_DETAILS_SLUG = 'product-details';

export const PRODUCT_SCHEDULED_SALE_SLUG = 'product-scheduled-sale';

export const TRACKS_SOURCE = 'product-block-editor-v1';

export const HEADER_PINNED_ITEMS_SCOPE = 'woocommerce/product-editor';

/**
 * Since the pagination component does not exposes the way of
 * changing the per page options which are [25, 50, 75, 100]
 * the default per page option will be the min in the list to
 * keep compatibility.
 *
 * @see https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/components/src/pagination/index.js#L12
 */
export const DEFAULT_PER_PAGE_OPTION = 25;

export const DEFAULT_VARIATION_PER_PAGE_OPTION = 5;
export const DEFAULT_VARIATION_PER_PAGE_OPTIONS = [ 5, 10, 25 ];
