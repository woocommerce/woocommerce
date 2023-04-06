export const PRODUCT_MVP_CES_ACTION_OPTION_NAME =
	'woocommerce_ces_product_mvp_ces_action';
export const NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME =
	'woocommerce_new_product_management_enabled';

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
