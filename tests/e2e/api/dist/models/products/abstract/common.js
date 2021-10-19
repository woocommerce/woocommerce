"use strict";
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.AbstractProduct = exports.deleteProductURL = exports.buildProductURL = exports.baseProductURL = void 0;
var data_1 = require("./data");
var shared_1 = require("../shared");
/**
 * The base product URL.
 *
 * @return {string} RESTful Url.
 */
exports.baseProductURL = function () { return '/wc/v3/products/'; };
/**
 * A common product URL builder.
 *
 * @param {ModelID} id the id of the product.
 * @return {string} RESTful Url.
 */
exports.buildProductURL = function (id) { return exports.baseProductURL() + id; };
/**
 * A common delete product URL builder.
 *
 * @param {ModelID} id the id of the product.
 * @return {string} RESTful Url.
 */
exports.deleteProductURL = function (id) { return exports.buildProductURL(id) + '?force=true'; };
/**
 * The base for all product types.
 */
var AbstractProduct = /** @class */ (function (_super) {
    __extends(AbstractProduct, _super);
    function AbstractProduct() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The name of the product.
         *
         * @type {string}
         */
        _this.name = '';
        /**
         * The slug of the product.
         *
         * @type {string}
         */
        _this.slug = '';
        /**
         * The GMT datetime when the product was created.
         *
         * @type {Date}
         */
        _this.created = new Date();
        /**
         * The GMT datetime when the product was last modified.
         *
         * @type {Date}
         */
        _this.modified = new Date();
        /**
         * The product's short description.
         *
         * @type {string}
         */
        _this.shortDescription = '';
        /**
         * An array of the categories this product is in.
         *
         * @type {ReadonlyArray.<ProductTerm>}
         */
        _this.categories = [];
        /**
         * An array of the tags this product has.
         *
         * @type {ReadonlyArray.<ProductTerm>}
         */
        _this.tags = [];
        /**
         * Indicates whether or not the product should be featured.
         *
         * @type {boolean}
         */
        _this.isFeatured = false;
        /**
         * Indicates whether or not the product should be visible in the catalog.
         *
         * @type {CatalogVisibility}
         */
        _this.catalogVisibility = shared_1.CatalogVisibility.Everywhere;
        /**
         * The count of sales of the product
         *
         * @type {number}
         */
        _this.totalSales = 0;
        /**
         * Indicates whether or not a product allows reviews.
         *
         * @type {boolean}
         */
        _this.allowReviews = false;
        /**
         * The average rating for the product.
         *
         * @type {number}
         */
        _this.averageRating = -1;
        /**
         * The number of ratings for the product.
         *
         * @type {number}
         */
        _this.numRatings = -1;
        /**
         * An array of IDs of related products.
         *
         * @type {ReadonlyArray.<number>}
         */
        _this.relatedIds = [];
        /**
         * The attributes for the product.
         *
         * @type {ReadonlyArray.<ProductAttribute>}
         */
        _this.attributes = [];
        /**
         * The product's links.
         *
         * @type {ReadonlyArray.<ObjectLinks>}
         */
        _this.links = {
            collection: [{ href: '' }],
            self: [{ href: '' }],
        };
        return _this;
    }
    return AbstractProduct;
}(data_1.AbstractProductData));
exports.AbstractProduct = AbstractProduct;
