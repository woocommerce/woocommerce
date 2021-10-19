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
exports.ObjectLinks = exports.MetaData = void 0;
var model_1 = require("./model");
/**
 * A metadata object.
 */
var MetaData = /** @class */ (function (_super) {
    __extends(MetaData, _super);
    /**
     * Creates a new metadata.
     *
     * @param {Partial.<MetaData>} properties The properties to set.
     */
    function MetaData(properties) {
        var _this = _super.call(this) || this;
        /**
         * The key of the metadata.
         *
         * @type {string}
         */
        _this.key = '';
        /**
         * The value of the metadata.
         *
         * @type {*}
         */
        _this.value = '';
        /**
         * The key of the metadata.
         *
         * @type {string}
         */
        _this.displayKey = '';
        /**
         * The value of the metadata.
         *
         * @type {*}
         */
        _this.displayValue = '';
        Object.assign(_this, properties);
        return _this;
    }
    return MetaData;
}(model_1.Model));
exports.MetaData = MetaData;
/**
 * An object link item.
 */
var LinkItem = /** @class */ (function () {
    /**
     * Creates a new product link item.
     *
     * @param {Partial.<LinkItem>} properties The properties to set.
     */
    function LinkItem(properties) {
        /**
         * The href of the link.
         *
         * @type {ReadonlyArray.<string>}
         */
        this.href = '';
        Object.assign(this, properties);
    }
    return LinkItem;
}());
/**
 * An object's links.
 */
var ObjectLinks = /** @class */ (function () {
    /**
     * Creates a new product link list.
     *
     * @param {Partial.<ObjectLinks>} properties The properties to set.
     */
    function ObjectLinks(properties) {
        /**
         * The collection containing the object.
         *
         * @type {ReadonlyArray.<LinkItem>}
         */
        this.collection = [];
        /**
         * Self referential link to the object.
         *
         * @type {ReadonlyArray.<LinkItem>}
         */
        this.self = [];
        /**
         * The link to the parent object.
         *
         * @type {ReadonlyArray.<LinkItem>}
         */
        this.up = [];
        Object.assign(this, properties);
    }
    return ObjectLinks;
}());
exports.ObjectLinks = ObjectLinks;
