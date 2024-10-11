"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const data_1 = require("@woocommerce/data");
const block_templates_1 = require("@woocommerce/block-templates");
const tracks_1 = require("@woocommerce/tracks");
const element_1 = require("@wordpress/element");
const data_2 = require("@wordpress/data");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const variations_table_1 = require("../../../components/variations-table");
const validation_context_1 = require("../../../contexts/validation-context");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const variable_product_tour_1 = require("./variable-product-tour");
const constants_1 = require("../../../constants");
const handle_prompt_1 = require("../../../utils/handle-prompt");
const empty_state_1 = require("../../../components/empty-state");
function Edit({ attributes, context: { isInSelectedTab }, }) {
    const noticeDismissed = (0, element_1.useRef)(false);
    const { invalidateResolution } = (0, data_2.useDispatch)(data_1.EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME);
    const productId = (0, core_data_1.useEntityId)('postType', 'product');
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [productStatus] = (0, core_data_1.useEntityProp)('postType', 'product', 'status');
    const [productHasOptions] = (0, core_data_1.useEntityProp)('postType', 'product', 'has_options');
    const [productAttributes] = (0, use_product_entity_prop_1.default)('attributes');
    const hasVariationOptions = (0, element_1.useMemo)(function hasAttributesUsedForVariations() {
        return productAttributes === null || productAttributes === void 0 ? void 0 : productAttributes.some((productAttribute) => productAttribute.variation);
    }, [productAttributes]);
    const totalCountWithoutPriceRequestParams = (0, element_1.useMemo)(() => ({
        product_id: productId,
        order: 'asc',
        orderby: 'menu_order',
        has_price: false,
    }), [productId]);
    const { totalCountWithoutPrice } = (0, data_2.useSelect)((select) => {
        const { getProductVariationsTotalCount } = select(data_1.EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME);
        return {
            totalCountWithoutPrice: productHasOptions
                ? getProductVariationsTotalCount(totalCountWithoutPriceRequestParams)
                : 0,
        };
    }, [productHasOptions, totalCountWithoutPriceRequestParams]);
    const { updateUserPreferences, variable_items_without_price_notice_dismissed: itemsWithoutPriceNoticeDismissed, } = (0, data_1.useUserPreferences)();
    const { ref: variationTableRef } = (0, validation_context_1.useValidation)(`variations`, async function regularPriceValidator(defaultValue, newData) {
        /**
         * We cause a validation error if there is:
         * - more then one variation without a price.
         * - the notice hasn't been dismissed.
         * - The product hasn't already been published.
         * - We are publishing the product.
         */
        if (totalCountWithoutPrice > 0 &&
            !noticeDismissed.current &&
            productStatus !== 'publish' &&
            // New status.
            (newData === null || newData === void 0 ? void 0 : newData.status) === 'publish') {
            if (itemsWithoutPriceNoticeDismissed !== 'yes') {
                updateUserPreferences({
                    variable_items_without_price_notice_dismissed: {
                        ...(itemsWithoutPriceNoticeDismissed || {}),
                        [productId]: 'no',
                    },
                });
            }
            return {
                message: (0, i18n_1.__)('Set variation prices before adding this product.', 'woocommerce'),
            };
        }
    }, [totalCountWithoutPrice]);
    function onSetPrices(handleUpdateAll) {
        (0, tracks_1.recordEvent)('product_variations_set_prices_select', {
            source: constants_1.TRACKS_SOURCE,
        });
        const productVariationsListPromise = (0, data_2.resolveSelect)(data_1.EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME).getProductVariations({
            product_id: productId,
            order: 'asc',
            orderby: 'menu_order',
            has_price: false,
            _fields: ['id'],
            per_page: totalCountWithoutPrice,
        });
        (0, handle_prompt_1.handlePrompt)({
            onOk(value) {
                (0, tracks_1.recordEvent)('product_variations_set_prices_update', {
                    source: constants_1.TRACKS_SOURCE,
                });
                productVariationsListPromise.then((variations) => {
                    handleUpdateAll(variations.map(({ id }) => ({
                        id,
                        regular_price: value,
                    })));
                });
            },
        });
    }
    const hasNotDismissedNotice = !itemsWithoutPriceNoticeDismissed ||
        itemsWithoutPriceNoticeDismissed[productId] !== 'yes';
    const noticeText = totalCountWithoutPrice > 0 && hasNotDismissedNotice
        ? (0, i18n_1.sprintf)(
        /** Translators: Number of variations without price */
        (0, i18n_1.__)('%d variations do not have prices. Variations that do not have prices will not be visible to customers.', 'woocommerce'), totalCountWithoutPrice)
        : '';
    if (!hasVariationOptions) {
        return ((0, element_1.createElement)(empty_state_1.EmptyState, { names: [
                (0, i18n_1.__)('Variation', 'woocommerce'),
                (0, i18n_1.__)('Colors', 'woocommerce'),
                (0, i18n_1.__)('Sizes', 'woocommerce'),
            ] }));
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(variations_table_1.VariationsTable, { isVisible: isInSelectedTab, ref: variationTableRef, noticeText: noticeText, onNoticeDismiss: () => {
                noticeDismissed.current = true;
                updateUserPreferences({
                    variable_items_without_price_notice_dismissed: {
                        ...(itemsWithoutPriceNoticeDismissed || {}),
                        [productId]: 'yes',
                    },
                });
            }, noticeActions: [
                {
                    label: (0, i18n_1.__)('Set prices', 'woocommerce'),
                    onClick: onSetPrices,
                    className: 'is-destructive',
                },
            ], onVariationTableChange: (type, update) => {
                if (type === 'delete' ||
                    (type === 'update' &&
                        update &&
                        update.find((variation) => 'regular_price' in variation ||
                            'sale_price' in variation))) {
                    invalidateResolution('getProductVariationsTotalCount', [totalCountWithoutPriceRequestParams]);
                }
            } }),
        isInSelectedTab && (0, element_1.createElement)(variable_product_tour_1.VariableProductTour, null)));
}
exports.Edit = Edit;
