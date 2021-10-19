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
Object.defineProperty(exports, "__esModule", { value: true });
var models_1 = require("../../models");
var model_repository_1 = require("../model-repository");
var dummy_model_1 = require("../../__test_data__/dummy-model");
var DummyChildModel = /** @class */ (function (_super) {
    __extends(DummyChildModel, _super);
    function DummyChildModel(partial) {
        var _this = _super.call(this) || this;
        _this.childName = '';
        Object.assign(_this, partial);
        return _this;
    }
    return DummyChildModel;
}(models_1.Model));
describe('ModelRepository', function () {
    it('should list', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, listed;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new dummy_model_1.DummyModel();
                    callback = jest.fn().mockResolvedValue([model]);
                    repository = new model_repository_1.ModelRepository(callback, null, null, null, null);
                    return [4 /*yield*/, repository.list({ search: 'test' })];
                case 1:
                    listed = _a.sent();
                    expect(listed).toContain(model);
                    expect(callback).toHaveBeenCalledWith({ search: 'test' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('should list child', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, listed;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new DummyChildModel();
                    callback = jest.fn().mockResolvedValue([model]);
                    repository = new model_repository_1.ModelRepository(callback, null, null, null, null);
                    return [4 /*yield*/, repository.list({ parent: 'test' }, { childSearch: 'test' })];
                case 1:
                    listed = _a.sent();
                    expect(listed).toContain(model);
                    expect(callback).toHaveBeenCalledWith({ parent: 'test' }, { childSearch: 'test' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('should throw error on list without callback', function () {
        var repository = new model_repository_1.ModelRepository(null, null, null, null, null);
        expect(function () { return repository.list(); }).toThrowError(/not supported/i);
    });
    it('should create', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, created;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new dummy_model_1.DummyModel();
                    callback = jest.fn().mockResolvedValue(model);
                    repository = new model_repository_1.ModelRepository(null, callback, null, null, null);
                    return [4 /*yield*/, repository.create({ name: 'test' })];
                case 1:
                    created = _a.sent();
                    expect(created).toBe(model);
                    expect(callback).toHaveBeenCalledWith({ name: 'test' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('should create child', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, created;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new DummyChildModel();
                    callback = jest.fn().mockResolvedValue(model);
                    repository = new model_repository_1.ModelRepository(null, callback, null, null, null);
                    return [4 /*yield*/, repository.create({ parent: 'yes' }, { childName: 'test' })];
                case 1:
                    created = _a.sent();
                    expect(created).toBe(model);
                    expect(callback).toHaveBeenCalledWith({ childName: 'test' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('should throw error on create without callback', function () {
        var repository = new model_repository_1.ModelRepository(null, null, null, null, null);
        expect(function () { return repository.create({ name: 'test' }); }).toThrowError(/not supported/i);
    });
    it('should read', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, created;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new dummy_model_1.DummyModel();
                    callback = jest.fn().mockResolvedValue(model);
                    repository = new model_repository_1.ModelRepository(null, null, callback, null, null);
                    return [4 /*yield*/, repository.read(1)];
                case 1:
                    created = _a.sent();
                    expect(created).toBe(model);
                    expect(callback).toHaveBeenCalledWith(1);
                    return [2 /*return*/];
            }
        });
    }); });
    it('should read child', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, created;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new DummyChildModel();
                    callback = jest.fn().mockResolvedValue(model);
                    repository = new model_repository_1.ModelRepository(null, null, callback, null, null);
                    return [4 /*yield*/, repository.read({ parent: 'yes' }, 1)];
                case 1:
                    created = _a.sent();
                    expect(created).toBe(model);
                    expect(callback).toHaveBeenCalledWith({ parent: 'yes' }, 1);
                    return [2 /*return*/];
            }
        });
    }); });
    it('should throw error on read without callback', function () {
        var repository = new model_repository_1.ModelRepository(null, null, null, null, null);
        expect(function () { return repository.read(1); }).toThrowError(/not supported/i);
    });
    it('should update', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, updated;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new dummy_model_1.DummyModel();
                    callback = jest.fn().mockResolvedValue(model);
                    repository = new model_repository_1.ModelRepository(null, null, null, callback, null);
                    return [4 /*yield*/, repository.update(1, { name: 'new-name' })];
                case 1:
                    updated = _a.sent();
                    expect(updated).toBe(model);
                    expect(callback).toHaveBeenCalledWith(1, { name: 'new-name' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('should update child', function () { return __awaiter(void 0, void 0, void 0, function () {
        var model, callback, repository, updated;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    model = new DummyChildModel();
                    callback = jest.fn().mockResolvedValue(model);
                    repository = new model_repository_1.ModelRepository(null, null, null, callback, null);
                    return [4 /*yield*/, repository.update({ parent: 'test' }, 1, { childName: 'new-name' })];
                case 1:
                    updated = _a.sent();
                    expect(updated).toBe(model);
                    expect(callback).toHaveBeenCalledWith({ parent: 'test' }, 1, { childName: 'new-name' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('should throw error on update without callback', function () {
        var repository = new model_repository_1.ModelRepository(null, null, null, null, null);
        expect(function () { return repository.update(1, { name: 'new-name' }); }).toThrowError(/not supported/i);
    });
    it('should delete', function () { return __awaiter(void 0, void 0, void 0, function () {
        var callback, repository, success;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    callback = jest.fn().mockResolvedValue(true);
                    repository = new model_repository_1.ModelRepository(null, null, null, null, callback);
                    return [4 /*yield*/, repository.delete(1)];
                case 1:
                    success = _a.sent();
                    expect(success).toBe(true);
                    expect(callback).toHaveBeenCalledWith(1);
                    return [2 /*return*/];
            }
        });
    }); });
    it('should delete child', function () { return __awaiter(void 0, void 0, void 0, function () {
        var callback, repository, success;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    callback = jest.fn().mockResolvedValue(true);
                    repository = new model_repository_1.ModelRepository(null, null, null, null, callback);
                    return [4 /*yield*/, repository.delete({ parent: 'yes' }, 1)];
                case 1:
                    success = _a.sent();
                    expect(success).toBe(true);
                    expect(callback).toHaveBeenCalledWith({ parent: 'yes' }, 1);
                    return [2 /*return*/];
            }
        });
    }); });
    it('should throw error on delete without callback', function () {
        var repository = new model_repository_1.ModelRepository(null, null, null, null, null);
        expect(function () { return repository.delete(1); }).toThrowError(/not supported/i);
    });
});
