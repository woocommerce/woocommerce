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
exports.Setting = void 0;
var model_1 = require("../model");
var repositories_1 = require("../../repositories");
/**
 * A setting object.
 */
var Setting = /** @class */ (function (_super) {
    __extends(Setting, _super);
    /**
     * Creates a new setting instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    function Setting(properties) {
        var _this = _super.call(this) || this;
        /**
         * The label of the setting.
         *
         * @type {string}
         */
        _this.label = '';
        /**
         * The description of the setting.
         *
         * @type {string}
         */
        _this.description = '';
        /**
         * The type of the setting.
         *
         * @type {string}
         */
        _this.type = '';
        /**
         * The default value for the setting.
         *
         * @type {string}
         */
        _this.default = '';
        /**
         * The current value of the setting.
         *
         * @type {string}
         */
        _this.value = '';
        Object.assign(_this, properties);
        return _this;
    }
    /**
     * Returns the repository for interacting with this type of model.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    Setting.restRepository = function (httpClient) {
        return repositories_1.settingRESTRepository(httpClient);
    };
    return Setting;
}(model_1.Model));
exports.Setting = Setting;
