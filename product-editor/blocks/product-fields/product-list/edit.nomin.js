"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.ProductListBlockEdit = void 0;
/**
 * External dependencies
 */
const components_1 = require("@wordpress/components");
const core_data_1 = require("@wordpress/core-data");
const data_1 = require("@wordpress/data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
const block_templates_1 = require("@woocommerce/block-templates");
const currency_1 = require("@woocommerce/currency");
const data_2 = require("@woocommerce/data");
const navigation_1 = require("@woocommerce/navigation");
const classnames_1 = __importDefault(require("classnames"));
/**
 * Internal dependencies
 */
const add_products_modal_1 = require("../../../components/add-products-modal");
const utils_1 = require("../../../utils");
const shirt_1 = require("../../../images/shirt");
const pants_1 = require("../../../images/pants");
const glasses_1 = require("../../../images/glasses");
const advice_card_1 = require("../../../components/advice-card");
const block_slot_fill_1 = require("../../../components/block-slot-fill");
function ProductListBlockEdit({ attributes, context: { postType }, }) {
    const { property } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [openAddProductsModal, setOpenAddProductsModal] = (0, element_1.useState)(false);
    const [openReorderProductsModal, setOpenReorderProductsModal] = (0, element_1.useState)(false);
    const [isLoading, setIsLoading] = (0, element_1.useState)(false);
    const [preventFetch, setPreventFetch] = (0, element_1.useState)(false);
    const [groupedProductIds, setGroupedProductIds] = (0, core_data_1.useEntityProp)('postType', postType, property);
    const [groupedProducts, setGroupedProducts] = (0, element_1.useState)([]);
    const { formatAmount } = (0, element_1.useContext)(currency_1.CurrencyContext);
    (0, element_1.useEffect)(function loadGroupedProducts() {
        if (preventFetch)
            return;
        if (groupedProductIds.length) {
            setIsLoading(false);
            (0, data_1.resolveSelect)(data_2.PRODUCTS_STORE_NAME)
                .getProducts({
                include: groupedProductIds,
                orderby: 'include',
            })
                .then(setGroupedProducts)
                .finally(() => setIsLoading(false));
        }
        else {
            setGroupedProducts([]);
        }
    }, [groupedProductIds, preventFetch]);
    function handleAddProductsButtonClick() {
        setOpenAddProductsModal(true);
    }
    function handleReorderProductsButtonClick() {
        setOpenReorderProductsModal(true);
    }
    function handleAddProductsModalSubmit(value) {
        const newGroupedProducts = [...groupedProducts, ...value];
        setPreventFetch(true);
        setGroupedProducts(newGroupedProducts);
        setGroupedProductIds(newGroupedProducts.map((product) => product.id));
        setOpenAddProductsModal(false);
    }
    function handleReorderProductsModalSubmit(value) {
        setGroupedProducts(value);
        setGroupedProductIds(value.map((product) => product.id));
        setOpenReorderProductsModal(false);
    }
    function handleAddProductsModalClose() {
        setOpenAddProductsModal(false);
    }
    function handleReorderProductsModalClose() {
        setOpenReorderProductsModal(false);
    }
    function removeProductHandler(product) {
        return function handleRemoveClick() {
            const newGroupedProducts = groupedProducts.filter((groupedProduct) => groupedProduct.id !== product.id);
            setPreventFetch(true);
            setGroupedProducts(newGroupedProducts);
            setGroupedProductIds(newGroupedProducts.map((groupedProduct) => groupedProduct.id));
        };
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(block_slot_fill_1.SectionActions, null,
            !isLoading && groupedProducts.length > 0 && ((0, element_1.createElement)(components_1.Button, { onClick: handleReorderProductsButtonClick, variant: "tertiary" }, (0, i18n_1.__)('Reorder', 'woocommerce'))),
            (0, element_1.createElement)(components_1.Button, { onClick: handleAddProductsButtonClick, variant: "secondary" }, (0, i18n_1.__)('Add products', 'woocommerce'))),
        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__body" },
            !isLoading && groupedProducts.length === 0 && ((0, element_1.createElement)(advice_card_1.AdviceCard, { tip: (0, i18n_1.__)('Tip: Group together items that have a clear relationship or compliment each other well, e.g., garment bundles, camera kits, or skincare product sets.', 'woocommerce'), isDismissible: false },
                (0, element_1.createElement)(shirt_1.Shirt, null),
                (0, element_1.createElement)(pants_1.Pants, null),
                (0, element_1.createElement)(glasses_1.Glasses, null))),
            !isLoading && groupedProducts.length > 0 && ((0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table", role: "table" },
                (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-header" },
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-row", role: "rowheader" },
                        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-header-column", role: "columnheader" }, (0, i18n_1.__)('Product', 'woocommerce')),
                        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-header-column", role: "columnheader" }, (0, i18n_1.__)('Price', 'woocommerce')),
                        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-header-column", role: "columnheader" }, (0, i18n_1.__)('Stock', 'woocommerce')),
                        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-header-column", role: "columnheader" }))),
                (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-body", role: "rowgroup" }, groupedProducts.map((product) => ((0, element_1.createElement)("div", { key: product.id, className: "wp-block-woocommerce-product-list-field__table-row", role: "row" },
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-cell", role: "cell" },
                        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__product-image", style: (0, add_products_modal_1.getProductImageStyle)(product) }),
                        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__product-info" },
                            (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__product-name" },
                                (0, element_1.createElement)(components_1.Button, { variant: "link", href: (0, navigation_1.getNewPath)({}, `/product/${product.id}`), target: "_blank" }, product.name)),
                            (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__product-sku" }, product.sku))),
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-cell", role: "cell" },
                        product.on_sale && ((0, element_1.createElement)("span", null, product.sale_price
                            ? formatAmount(product.sale_price)
                            : formatAmount(product.price))),
                        product.regular_price && ((0, element_1.createElement)("span", { className: (0, classnames_1.default)({
                                'wp-block-woocommerce-product-list-field__price--on-sale': product.on_sale,
                            }) }, formatAmount(product.regular_price)))),
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-cell", role: "cell" },
                        (0, element_1.createElement)("span", { className: (0, classnames_1.default)('woocommerce-product-variations__status-dot', (0, utils_1.getProductStockStatusClass)(product)) }, "\u25CF"),
                        (0, element_1.createElement)("span", null, (0, utils_1.getProductStockStatus)(product))),
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-list-field__table-cell", role: "cell" },
                        (0, element_1.createElement)(components_1.Button, { variant: "tertiary", icon: icons_1.external, "aria-label": (0, i18n_1.__)('Preview the product', 'woocommerce'), href: product.permalink, target: "_blank" }),
                        (0, element_1.createElement)(components_1.Button, { type: "button", variant: "tertiary", icon: icons_1.closeSmall, "aria-label": (0, i18n_1.__)('Remove product', 'woocommerce'), onClick: removeProductHandler(product) }))))))))),
        openAddProductsModal && ((0, element_1.createElement)(add_products_modal_1.AddProductsModal, { initialValue: groupedProducts, onSubmit: handleAddProductsModalSubmit, onClose: handleAddProductsModalClose })),
        openReorderProductsModal && ((0, element_1.createElement)(add_products_modal_1.ReorderProductsModal, { products: groupedProducts, onSubmit: handleReorderProductsModalSubmit, onClose: handleReorderProductsModalClose }))));
}
exports.ProductListBlockEdit = ProductListBlockEdit;
