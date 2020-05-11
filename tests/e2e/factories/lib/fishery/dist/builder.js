"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
var lodash_merge_1 = __importDefault(require("lodash.merge"));
var FactoryBuilder = /** @class */ (function () {
    function FactoryBuilder(generator, factories, sequence, params, options) {
        var _this = this;
        this.generator = generator;
        this.factories = factories;
        this.sequence = sequence;
        this.params = params;
        this.setOnCreateHook = function (hook) {
            _this.onCreateHook = hook;
        };
        this.setAfterBuildHook = function (hook) {
            _this.afterBuildHook = hook;
        };
        this.transientParams = options.transient || {};
        this.associations = options.associations || {};
    }
    FactoryBuilder.prototype.build = function () {
        var generatorOptions = {
            sequence: this.sequence,
            onCreate: this.setOnCreateHook,
            afterBuild: this.setAfterBuildHook,
            factories: this.factories,
            params: this.params,
            associations: this.associations,
            transientParams: this.transientParams,
        };
        var object = this.generator(generatorOptions);
        // merge params and associations into object. The only reason 'associations'
        // is separated is because it is typed differently from `params` (Partial<T>
        // vs DeepPartial<T>) so can do the following in a factory:
        // `user: associations.user || factories.user.build()`
        lodash_merge_1.default(object, this.params, this.associations);
        this._callAfterBuildHook(object);
        return object;
    };
    FactoryBuilder.prototype.create = function () {
        return __awaiter(this, void 0, void 0, function () {
            var object;
            return __generator(this, function (_a) {
                object = this.build();
                return [2 /*return*/, this._callOnCreateHook(object)];
            });
        });
    };
    FactoryBuilder.prototype._callAfterBuildHook = function (object) {
        if (this.afterBuildHook) {
            if (typeof this.afterBuildHook === 'function') {
                this.afterBuildHook(object);
            }
            else {
                throw new Error('"afterBuild" must be a function');
            }
        }
    };
    FactoryBuilder.prototype._callOnCreateHook = function (object) {
        if (this.onCreateHook) {
            if (typeof this.onCreateHook === 'function') {
                return this.onCreateHook(object);
            }
            else {
                throw new Error('"onCreate" must be a function');
            }
        }
        else {
            throw new Error('Tried to call `create` but `onCreate` function is not defined for the factory. Define this function when calling `factory.define`');
        }
    };
    return FactoryBuilder;
}());
exports.FactoryBuilder = FactoryBuilder;
