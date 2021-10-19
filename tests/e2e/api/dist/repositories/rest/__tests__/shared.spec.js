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
var jest_mock_extended_1 = require("jest-mock-extended");
var http_1 = require("../../../http");
var dummy_model_1 = require("../../../__test_data__/dummy-model");
var shared_1 = require("../shared");
var models_1 = require("../../../models");
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
describe('Shared REST Functions', function () {
    var mockClient;
    var mockTransformer;
    beforeEach(function () {
        mockClient = jest_mock_extended_1.mock();
        mockTransformer = jest_mock_extended_1.mock();
    });
    it('restList', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.get.mockResolvedValue(new http_1.HTTPResponse(200, {}, [
                        {
                            id: 'Test-1',
                            label: 'Test 1',
                        },
                        {
                            id: 'Test-2',
                            label: 'Test 2',
                        },
                    ]));
                    mockTransformer.toModel.mockReturnValue(new dummy_model_1.DummyModel({ name: 'Test' }));
                    fn = shared_1.restList(function () { return 'test-url'; }, dummy_model_1.DummyModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn({ search: 'Test' })];
                case 1:
                    result = _a.sent();
                    expect(result).toHaveLength(2);
                    expect(result[0]).toMatchObject(new dummy_model_1.DummyModel({ name: 'Test' }));
                    expect(result[1]).toMatchObject(new dummy_model_1.DummyModel({ name: 'Test' }));
                    expect(mockClient.get).toHaveBeenCalledWith('test-url', { search: 'Test' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, { id: 'Test-1', label: 'Test 1' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, { id: 'Test-2', label: 'Test 2' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restListChildren', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.get.mockResolvedValue(new http_1.HTTPResponse(200, {}, [
                        {
                            id: 'Test-1',
                            label: 'Test 1',
                        },
                        {
                            id: 'Test-2',
                            label: 'Test 2',
                        },
                    ]));
                    mockTransformer.toModel.mockReturnValue(new DummyChildModel({ name: 'Test' }));
                    fn = shared_1.restListChild(function (parent) { return 'test-url-' + parent.parent; }, DummyChildModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn({ parent: '123' }, { childSearch: 'Test' })];
                case 1:
                    result = _a.sent();
                    expect(result).toHaveLength(2);
                    expect(result[0]).toMatchObject(new DummyChildModel({ name: 'Test' }));
                    expect(result[1]).toMatchObject(new DummyChildModel({ name: 'Test' }));
                    expect(mockClient.get).toHaveBeenCalledWith('test-url-123', { childSearch: 'Test' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(DummyChildModel, { id: 'Test-1', label: 'Test 1' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(DummyChildModel, { id: 'Test-2', label: 'Test 2' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restCreate', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.post.mockResolvedValue(new http_1.HTTPResponse(200, {}, {
                        id: 'Test-1',
                        label: 'Test 1',
                    }));
                    mockTransformer.fromModel.mockReturnValue({ name: 'From-Test' });
                    mockTransformer.toModel.mockReturnValue(new dummy_model_1.DummyModel({ name: 'Test' }));
                    fn = shared_1.restCreate(function (properties) { return 'test-url-' + properties.name; }, dummy_model_1.DummyModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn({ name: 'Test' })];
                case 1:
                    result = _a.sent();
                    expect(result).toMatchObject(new dummy_model_1.DummyModel({ name: 'Test' }));
                    expect(mockTransformer.fromModel).toHaveBeenCalledWith({ name: 'Test' });
                    expect(mockClient.post).toHaveBeenCalledWith('test-url-Test', { name: 'From-Test' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, { id: 'Test-1', label: 'Test 1' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restRead', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.get.mockResolvedValue(new http_1.HTTPResponse(200, {}, {
                        id: 'Test-1',
                        label: 'Test 1',
                    }));
                    mockTransformer.toModel.mockReturnValue(new dummy_model_1.DummyModel({ name: 'Test' }));
                    fn = shared_1.restRead(function (id) { return 'test-url-' + id; }, dummy_model_1.DummyModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn(123)];
                case 1:
                    result = _a.sent();
                    expect(result).toMatchObject(new dummy_model_1.DummyModel({ name: 'Test' }));
                    expect(mockClient.get).toHaveBeenCalledWith('test-url-123');
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, { id: 'Test-1', label: 'Test 1' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restReadChildren', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.get.mockResolvedValue(new http_1.HTTPResponse(200, {}, {
                        id: 'Test-1',
                        label: 'Test 1',
                    }));
                    mockTransformer.toModel.mockReturnValue(new DummyChildModel({ name: 'Test' }));
                    fn = shared_1.restReadChild(function (parent, id) { return 'test-url-' + parent.parent + '-' + id; }, DummyChildModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn({ parent: '123' }, 456)];
                case 1:
                    result = _a.sent();
                    expect(result).toMatchObject(new dummy_model_1.DummyModel({ name: 'Test' }));
                    expect(mockClient.get).toHaveBeenCalledWith('test-url-123-456');
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(DummyChildModel, { id: 'Test-1', label: 'Test 1' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restUpdate', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.patch.mockResolvedValue(new http_1.HTTPResponse(200, {}, {
                        id: 'Test-1',
                        label: 'Test 1',
                    }));
                    mockTransformer.fromModel.mockReturnValue({ name: 'From-Test' });
                    mockTransformer.toModel.mockReturnValue(new dummy_model_1.DummyModel({ name: 'Test' }));
                    fn = shared_1.restUpdate(function (id) { return 'test-url-' + id; }, dummy_model_1.DummyModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn(123, { name: 'Test' })];
                case 1:
                    result = _a.sent();
                    expect(result).toMatchObject(new dummy_model_1.DummyModel({ name: 'Test' }));
                    expect(mockTransformer.fromModel).toHaveBeenCalledWith({ name: 'Test' });
                    expect(mockClient.patch).toHaveBeenCalledWith('test-url-123', { name: 'From-Test' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(dummy_model_1.DummyModel, { id: 'Test-1', label: 'Test 1' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restUpdateChildren', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.patch.mockResolvedValue(new http_1.HTTPResponse(200, {}, {
                        id: 'Test-1',
                        label: 'Test 1',
                    }));
                    mockTransformer.fromModel.mockReturnValue({ name: 'From-Test' });
                    mockTransformer.toModel.mockReturnValue(new DummyChildModel({ name: 'Test' }));
                    fn = shared_1.restUpdateChild(function (parent, id) { return 'test-url-' + parent.parent + '-' + id; }, DummyChildModel, mockClient, mockTransformer);
                    return [4 /*yield*/, fn({ parent: '123' }, 456, { childName: 'Test' })];
                case 1:
                    result = _a.sent();
                    expect(result).toMatchObject(new DummyChildModel({ name: 'Test' }));
                    expect(mockTransformer.fromModel).toHaveBeenCalledWith({ childName: 'Test' });
                    expect(mockClient.patch).toHaveBeenCalledWith('test-url-123-456', { name: 'From-Test' });
                    expect(mockTransformer.toModel).toHaveBeenCalledWith(DummyChildModel, { id: 'Test-1', label: 'Test 1' });
                    return [2 /*return*/];
            }
        });
    }); });
    it('restDelete', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.delete.mockResolvedValue(new http_1.HTTPResponse(200, {}, {}));
                    fn = shared_1.restDelete(function (id) { return 'test-url-' + id; }, mockClient);
                    return [4 /*yield*/, fn(123)];
                case 1:
                    result = _a.sent();
                    expect(result).toBe(true);
                    expect(mockClient.delete).toHaveBeenCalledWith('test-url-123');
                    return [2 /*return*/];
            }
        });
    }); });
    it('restDeleteChildren', function () { return __awaiter(void 0, void 0, void 0, function () {
        var fn, result;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    mockClient.delete.mockResolvedValue(new http_1.HTTPResponse(200, {}, {}));
                    fn = shared_1.restDeleteChild(function (parent, id) { return 'test-url-' + parent.parent + '-' + id; }, mockClient);
                    return [4 /*yield*/, fn({ parent: '123' }, 456)];
                case 1:
                    result = _a.sent();
                    expect(result).toBe(true);
                    expect(mockClient.delete).toHaveBeenCalledWith('test-url-123-456');
                    return [2 /*return*/];
            }
        });
    }); });
});
