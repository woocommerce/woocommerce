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
var model_1 = require("../../model");
/**
 * The base for external products.
 */
var AbstractProductExternal = /** @class */ (function (_super) {
    __extends(AbstractProductExternal, _super);
    function AbstractProductExternal() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        /**
         * The product's button text.
         *
         * @type {string}
         */
        _this.buttonText = '';
        /**
         * The product's external URL.
         *
         * @type {string}
         */
        _this.externalUrl = '';
        return _this;
    }
    return AbstractProductExternal;
}(model_1.Model));
