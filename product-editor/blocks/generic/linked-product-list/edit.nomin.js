"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.LinkedProductListBlockEdit = exports.EmptyStateImage = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const data_1 = require("@wordpress/data");
const data_2 = require("@woocommerce/data");
const components_1 = require("@wordpress/components");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
const tracks_1 = require("@woocommerce/tracks");
const compose_1 = require("@wordpress/compose");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const product_list_1 = require("../../../components/product-list");
const product_select_1 = require("../../../components/product-select");
const advice_card_1 = require("../../../components/advice-card");
const constants_1 = require("../../../constants");
const shopping_bags_1 = require("../../../images/shopping-bags");
const cash_register_1 = require("../../../images/cash-register");
const reducer_1 = require("./reducer");
const get_related_products_1 = require("../../../utils/get-related-products");
const block_slot_fill_1 = require("../../../components/block-slot-fill");
function EmptyStateImage({ image, tip: description, }) {
    switch (image) {
        case 'CashRegister':
            return (0, element_1.createElement)(cash_register_1.CashRegister, null);
        case 'ShoppingBags':
            return (0, element_1.createElement)(shopping_bags_1.ShoppingBags, null);
        default:
            if (/^https?:\/\//.test(image)) {
                return ((0, element_1.createElement)("img", { src: image, alt: description, height: 88, width: 88 }));
            }
            return null;
    }
}
exports.EmptyStateImage = EmptyStateImage;
async function getProductsBySearchValue(searchValue = '', excludedIds = []) {
    return (0, data_1.resolveSelect)(data_2.PRODUCTS_STORE_NAME).getProducts({
        search: searchValue,
        orderby: 'title',
        order: 'asc',
        per_page: 5,
        exclude: excludedIds,
    });
}
function LinkedProductListBlockEdit({ attributes, context: { postType, isInSelectedTab }, }) {
    const { property, emptyState } = attributes;
    const loadInitialSearchResults = (0, element_1.useRef)(false);
    const [, setSearchValue] = (0, element_1.useState)('');
    const [searchedProducts, setSearchedProducts] = (0, element_1.useState)([]);
    const [isSearching, setIsSearching] = (0, element_1.useState)(false);
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [state, dispatch] = (0, element_1.useReducer)(reducer_1.reducer, {
        linkedProducts: [],
    });
    const productId = (0, core_data_1.useEntityId)('postType', postType);
    const loadLinkedProductsDispatcher = (0, reducer_1.getLoadLinkedProductsDispatcher)(dispatch);
    const selectSearchedProductDispatcher = (0, reducer_1.getSelectSearchedProductDispatcher)(dispatch);
    const removeLinkedProductDispatcher = (0, reducer_1.getRemoveLinkedProductDispatcher)(dispatch);
    const [linkedProductIds, setLinkedProductIds] = (0, use_product_entity_prop_1.default)(property, { postType });
    (0, element_1.useEffect)(() => {
        if (!state.selectedProduct &&
            linkedProductIds &&
            linkedProductIds.length > 0) {
            loadLinkedProductsDispatcher(linkedProductIds);
        }
    }, [linkedProductIds, state.selectedProduct]);
    function searchProducts(search = '', excludedIds = []) {
        setSearchValue(search);
        setIsSearching(true);
        return getProductsBySearchValue(search, excludedIds)
            .then((products) => {
            setSearchedProducts(products);
        })
            .finally(() => {
            setIsSearching(false);
        });
    }
    const debouncedFilter = (0, compose_1.useDebounce)(function filter(search = '') {
        searchProducts(search, [...(linkedProductIds || []), productId]);
    }, 300);
    (0, element_1.useEffect)(() => {
        // Only filter when the tab is selected and initial search results haven't been loaded yet.
        if (!isInSelectedTab || loadInitialSearchResults.current) {
            return;
        }
        loadInitialSearchResults.current = true;
        searchProducts('', [...(linkedProductIds || []), productId]);
    }, [
        isInSelectedTab,
        loadInitialSearchResults,
        linkedProductIds,
        productId,
    ]);
    const handleSelect = (0, element_1.useCallback)((product) => {
        const isAlreadySelected = (linkedProductIds || []).includes(product.id);
        if (isAlreadySelected) {
            return;
        }
        const newLinkedProductIds = selectSearchedProductDispatcher(product, state.linkedProducts);
        setLinkedProductIds(newLinkedProductIds);
        searchProducts('', [
            ...(newLinkedProductIds || []),
            productId,
        ]);
        (0, tracks_1.recordEvent)('linked_products_product_add', {
            source: constants_1.TRACKS_SOURCE,
            field: property,
            product_id: productId,
            linked_product_id: product.id,
        });
    }, [linkedProductIds, state.linkedProducts]);
    function handleProductListRemove(product) {
        const newLinkedProductIds = removeLinkedProductDispatcher(product, state.linkedProducts);
        setLinkedProductIds(newLinkedProductIds);
        searchProducts('', [...(newLinkedProductIds || []), productId]);
        (0, tracks_1.recordEvent)('linked_products_product_remove', {
            source: constants_1.TRACKS_SOURCE,
            field: property,
            product_id: productId,
            linked_product_id: product.id,
        });
    }
    function handleProductListEdit(product) {
        (0, tracks_1.recordEvent)('linked_products_product_select', {
            source: constants_1.TRACKS_SOURCE,
            field: property,
            product_id: productId,
            linked_product_id: product.id,
        });
    }
    function handleProductListPreview(product) {
        (0, tracks_1.recordEvent)('linked_products_product_preview_click', {
            source: constants_1.TRACKS_SOURCE,
            field: property,
            product_id: productId,
            linked_product_id: product.id,
        });
    }
    const [isChoosingProducts, setIsChoosingProducts] = (0, element_1.useState)(false);
    async function chooseProductsForMe() {
        (0, tracks_1.recordEvent)('linked_products_choose_related_click', {
            source: constants_1.TRACKS_SOURCE,
            field: property,
        });
        dispatch({
            type: 'LOADING_LINKED_PRODUCTS',
            payload: {
                isLoading: true,
            },
        });
        setIsChoosingProducts(true);
        const linkedProducts = (await (0, get_related_products_1.getSuggestedProductsFor)({
            postId: productId,
            forceRequest: true,
        }));
        dispatch({
            type: 'LOADING_LINKED_PRODUCTS',
            payload: {
                isLoading: false,
            },
        });
        setIsChoosingProducts(false);
        if (!linkedProducts) {
            return;
        }
        const newLinkedProducts = selectSearchedProductDispatcher(linkedProducts, []);
        setLinkedProductIds(newLinkedProducts);
    }
    function handleAdviceCardDismiss() {
        (0, tracks_1.recordEvent)('linked_products_placeholder_dismiss', {
            source: constants_1.TRACKS_SOURCE,
            field: property,
        });
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(block_slot_fill_1.SectionActions, null,
            (0, element_1.createElement)(components_1.Button, { variant: "tertiary", icon: icons_1.reusableBlock, onClick: chooseProductsForMe, isBusy: isChoosingProducts, disabled: isChoosingProducts }, (0, i18n_1.__)('Choose products for me', 'woocommerce'))),
        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-linked-list-field__form-group-content" },
            (0, element_1.createElement)(product_select_1.ProductSelect, { items: searchedProducts, filter: debouncedFilter, onSelect: handleSelect, isLoading: isSearching, selected: null })),
        state.isLoading && (0, element_1.createElement)(product_list_1.Skeleton, null),
        !state.isLoading && state.linkedProducts.length === 0 && ((0, element_1.createElement)(advice_card_1.AdviceCard, { tip: emptyState.tip, dismissPreferenceId: `woocommerce-product-${property}-advice-card-dismissed`, isDismissible: emptyState.isDismissible, onDismiss: handleAdviceCardDismiss },
            (0, element_1.createElement)(EmptyStateImage, { ...emptyState }))),
        !state.isLoading && state.linkedProducts.length > 0 && ((0, element_1.createElement)(product_list_1.ProductList, { products: state.linkedProducts, onRemove: handleProductListRemove, onEdit: handleProductListEdit, onPreview: handleProductListPreview }))));
}
exports.LinkedProductListBlockEdit = LinkedProductListBlockEdit;
