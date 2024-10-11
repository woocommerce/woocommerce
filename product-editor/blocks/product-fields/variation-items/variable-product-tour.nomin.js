"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.VariableProductTour = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const components_1 = require("@woocommerce/components");
const data_1 = require("@woocommerce/data");
const tracks_1 = require("@woocommerce/tracks");
const data_2 = require("@wordpress/data");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const constants_1 = require("../../../constants");
const VariableProductTour = () => {
    const [isTourOpen, setIsTourOpen] = (0, element_1.useState)(false);
    const productId = (0, core_data_1.useEntityId)('postType', 'product');
    const prevTotalCount = (0, element_1.useRef)();
    const requestParams = (0, element_1.useMemo)(() => ({
        product_id: productId,
        page: 1,
        per_page: constants_1.DEFAULT_VARIATION_PER_PAGE_OPTION,
        order: 'asc',
        orderby: 'menu_order',
    }), [productId]);
    const { totalCount } = (0, data_2.useSelect)((select) => {
        const { getProductVariationsTotalCount } = select(data_1.EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME);
        return {
            totalCount: getProductVariationsTotalCount(requestParams),
        };
    }, [productId]);
    const { updateUserPreferences, variable_product_block_tour_shown: hasShownTour, } = (0, data_1.useUserPreferences)();
    const config = {
        placement: 'top',
        steps: [
            {
                referenceElements: {
                    desktop: '.wp-block-woocommerce-product-variation-items-field',
                },
                focusElement: {
                    desktop: '.wp-block-woocommerce-product-variation-items-field',
                },
                meta: {
                    name: 'product-variations-2',
                    heading: (0, i18n_1.__)('⚡️ This product now has variations', 'woocommerce'),
                    descriptions: {
                        desktop: (0, i18n_1.__)('From now on, you’ll manage pricing, shipping, and inventory for each variation individually—just like any other product in your store.', 'woocommerce'),
                    },
                    primaryButton: {
                        text: (0, i18n_1.__)('Got it', 'woocommerce'),
                    },
                },
            },
        ],
        options: {
            classNames: ['variation-items-product-tour'],
            // WooTourKit does not handle merging of default options properly,
            // so we need to duplicate the effects options here.
            effects: {
                arrowIndicator: true,
                spotlight: {
                    interactivity: {
                        enabled: true,
                    },
                },
            },
            callbacks: {
                onStepViewOnce: () => {
                    (0, tracks_1.recordEvent)('variable_product_block_tour_shown', {
                        variable_count: totalCount,
                    });
                },
            },
            popperModifiers: [
                {
                    name: 'offset',
                    options: {
                        // 24px for additional padding and 8px for arrow.
                        offset: [0, 32],
                    },
                },
            ],
        },
        closeHandler: () => {
            updateUserPreferences({
                variable_product_block_tour_shown: 'yes',
            });
            setIsTourOpen(false);
            (0, tracks_1.recordEvent)('variable_product_block_tour_dismissed');
        },
    };
    (0, element_1.useEffect)(() => {
        const isFirstVariation = prevTotalCount.current !== totalCount &&
            totalCount > 0 &&
            prevTotalCount.current === 0;
        prevTotalCount.current = totalCount;
        if (isFirstVariation && !isTourOpen) {
            setIsTourOpen(true);
        }
    }, [totalCount]);
    const { hasShownProductEditorTour } = (0, data_2.useSelect)((select) => {
        const { getOption } = select(data_1.OPTIONS_STORE_NAME);
        return {
            hasShownProductEditorTour: getOption('woocommerce_block_product_tour_shown') === 'yes',
        };
    });
    if (hasShownTour === 'yes' ||
        !isTourOpen ||
        !hasShownProductEditorTour) {
        return null;
    }
    return (0, element_1.createElement)(components_1.TourKit, { config: config });
};
exports.VariableProductTour = VariableProductTour;
