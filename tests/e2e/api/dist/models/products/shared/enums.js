"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.BackorderStatus = exports.Taxability = exports.CatalogVisibility = void 0;
/**
 * An enum describing the catalog visibility options for products.
 *
 * @enum {string}
 */
var CatalogVisibility;
(function (CatalogVisibility) {
    /**
     * The product should be visible everywhere.
     */
    CatalogVisibility["Everywhere"] = "visible";
    /**
     * The product should only be visible in the shop catalog.
     */
    CatalogVisibility["ShopOnly"] = "catalog";
    /**
     * The product should only be visible in search results.
     */
    CatalogVisibility["SearchOnly"] = "search";
    /**
     * The product should be hidden everywhere.
     */
    CatalogVisibility["Hidden"] = "hidden";
})(CatalogVisibility = exports.CatalogVisibility || (exports.CatalogVisibility = {}));
/**
 * Indicates the taxability of a product.
 *
 * @enum {string}
 */
var Taxability;
(function (Taxability) {
    /**
     * The product and shipping are both taxable.
     */
    Taxability["ProductAndShipping"] = "taxable";
    /**
     * Only the product's shipping is taxable.
     */
    Taxability["ShippingOnly"] = "shipping";
    /**
     * The product and shipping are not taxable.
     */
    Taxability["None"] = "none";
})(Taxability = exports.Taxability || (exports.Taxability = {}));
/**
 * Indicates the status for backorders for a product.
 *
 * @enum {string}
 */
var BackorderStatus;
(function (BackorderStatus) {
    /**
     * The product is allowed to be backordered.
     */
    BackorderStatus["Allowed"] = "yes";
    /**
     * The product is allowed to be backordered but it will notify the customer of that fact.
     */
    BackorderStatus["AllowedWithNotification"] = "notify";
    /**
     * The product is not allowed to be backordered.
     */
    BackorderStatus["NotAllowed"] = "no";
})(BackorderStatus = exports.BackorderStatus || (exports.BackorderStatus = {}));
