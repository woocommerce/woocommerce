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
exports.ProductImage = exports.ProductDefaultAttribute = exports.ProductAttribute = exports.AbstractAttribute = exports.ProductDownload = exports.ProductTerm = void 0;
/**
 * A products taxonomy term such as categories or tags.
 */
var ProductTerm = /** @class */ (function () {
    /**
     * Creates a new product term.
     *
     * @param {Partial.<ProductTerm>} properties The properties to set.
     */
    function ProductTerm(properties) {
        /**
         * The ID of the term.
         *
         * @type {number}
         */
        this.id = -1;
        /**
         * The name of the term.
         *
         * @type {string}
         */
        this.name = '';
        /**
         * The slug of the term.
         *
         * @type {string}
         */
        this.slug = '';
        Object.assign(this, properties);
    }
    return ProductTerm;
}());
exports.ProductTerm = ProductTerm;
/**
 * A product's download.
 */
var ProductDownload = /** @class */ (function () {
    /**
     * Creates a new product download.
     *
     * @param {Partial.<ProductDownload>} properties The properties to set.
     */
    function ProductDownload(properties) {
        /**
         * The ID of the downloadable file.
         *
         * @type {string}
         */
        this.id = '';
        /**
         * The name of the downloadable file.
         *
         * @type {string}
         */
        this.name = '';
        /**
         * The URL of the downloadable file.
         *
         *
         * @type {string}
         */
        this.url = '';
        Object.assign(this, properties);
    }
    return ProductDownload;
}());
exports.ProductDownload = ProductDownload;
/**
 * Attribute base class.
 */
var AbstractAttribute = /** @class */ (function () {
    function AbstractAttribute() {
        /**
         * The ID of the attribute.
         *
         * @type {number}
         */
        this.id = -1;
        /**
         * The name of the attribute.
         *
         * @type {string}
         */
        this.name = '';
    }
    return AbstractAttribute;
}());
exports.AbstractAttribute = AbstractAttribute;
/**
 * A product's attributes.
 */
var ProductAttribute = /** @class */ (function (_super) {
    __extends(ProductAttribute, _super);
    /**
     * Creates a new product attribute.
     *
     * @param {Partial.<ProductAttribute>} properties The properties to set.
     */
    function ProductAttribute(properties) {
        var _this = _super.call(this) || this;
        /**
         * The sort order of the attribute.
         *
         * @type {number}
         */
        _this.sortOrder = -1;
        /**
         * Indicates whether or not the attribute is visible on the product page.
         *
         * @type {boolean}
         */
        _this.isVisibleOnProductPage = false;
        /**
         * Indicates whether or not the attribute should be used in variations.
         *
         * @type {boolean}
         */
        _this.isForVariations = false;
        /**
         * The options which are available for the attribute.
         *
         * @type {ReadonlyArray.<string>}
         */
        _this.options = [];
        Object.assign(_this, properties);
        return _this;
    }
    return ProductAttribute;
}(AbstractAttribute));
exports.ProductAttribute = ProductAttribute;
/**
 * Default attributes for variable products.
 */
var ProductDefaultAttribute = /** @class */ (function (_super) {
    __extends(ProductDefaultAttribute, _super);
    /**
     * Creates a new product default attribute.
     *
     * @param {Partial.<ProductDefaultAttribute>} properties The properties to set.
     */
    function ProductDefaultAttribute(properties) {
        var _this = _super.call(this) || this;
        /**
         * The option selected for the attribute.
         *
         * @type {string}
         */
        _this.option = '';
        Object.assign(_this, properties);
        return _this;
    }
    return ProductDefaultAttribute;
}(AbstractAttribute));
exports.ProductDefaultAttribute = ProductDefaultAttribute;
/**
 * A product's image.
 */
var ProductImage = /** @class */ (function () {
    /**
     * Creates a new product image.
     *
     * @param {Partial.<ProductImage>} properties The properties to set.
     */
    function ProductImage(properties) {
        /**
         * The ID of the image.
         *
         * @type {number}
         */
        this.id = -1;
        /**
         * The GMT datetime when the image was created.
         *
         * @type {Date}
         */
        this.created = new Date();
        /**
         * The GMT datetime when the image was last modified.
         *
         * @type {Date}
         */
        this.modified = new Date();
        /**
         * The URL for the image file.
         *
         * @type {string}
         */
        this.url = '';
        /**
         * The name of the image file.
         *
         * @type {string}
         */
        this.name = '';
        /**
         * The alt text to use on the image.
         *
         * @type {string}
         */
        this.altText = '';
        Object.assign(this, properties);
    }
    return ProductImage;
}());
exports.ProductImage = ProductImage;
