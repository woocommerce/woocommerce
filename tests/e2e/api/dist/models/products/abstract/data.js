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
exports.AbstractProductData = void 0;
var model_1 = require("../../model");
/**
 * Base product data.
 */
var AbstractProductData = /** @class */ (function (_super) {
    __extends(AbstractProductData, _super);
    function AbstractProductData() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The permalink of the product.
         *
         * @type {string}
         */
        _this.permalink = '';
        /**
         * The Id of the product.
         *
         * @type {number}
         */
        _this.id = 0;
        /**
         * The parent Id of the product.
         *
         * @type {number}
         */
        _this.parentId = 0;
        /**
         * The menu order assigned to the product.
         *
         * @type {number}
         */
        _this.menuOrder = 0;
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
         * The product's current post status.
         *
         * @type {PostStatus}
         */
        _this.postStatus = '';
        /**
         * The product's full description.
         *
         * @type {string}
         */
        _this.description = '';
        /**
         * The product's SKU.
         *
         * @type {string}
         */
        _this.sku = '';
        /**
         * Indicates whether or not the product is currently able to be purchased.
         *
         * @type {boolean}
         */
        _this.isPurchasable = true;
        /**
         * The images for the product.
         *
         * @type {ReadonlyArray.<ProductImage>}
         */
        _this.images = [];
        /**
         * The extra metadata for the product.
         *
         * @type {ReadonlyArray.<MetaData>}
         */
        _this.metaData = [];
        /**
         * The product data links.
         *
         * @type {ReadonlyArray.<ObjectLinks>}
         */
        _this.links = {
            collection: [{ href: '' }],
            self: [{ href: '' }],
        };
        return _this;
    }
    return AbstractProductData;
}(model_1.Model));
exports.AbstractProductData = AbstractProductData;
