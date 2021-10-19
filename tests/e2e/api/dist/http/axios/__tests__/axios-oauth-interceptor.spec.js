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
Object.defineProperty(exports, "__esModule", { value: true });
var axios_1 = require("axios");
var moxios = require("moxios");
var axios_oauth_interceptor_1 = require("../axios-oauth-interceptor");
describe('AxiosOAuthInterceptor', function () {
    var apiAuthInterceptor;
    var axiosInstance;
    beforeEach(function () {
        axiosInstance = axios_1.default.create();
        moxios.install(axiosInstance);
        apiAuthInterceptor = new axios_oauth_interceptor_1.AxiosOAuthInterceptor('consumer_key', 'consumer_secret');
        apiAuthInterceptor.start(axiosInstance);
    });
    afterEach(function () {
        apiAuthInterceptor.stop(axiosInstance);
        moxios.uninstall(axiosInstance);
    });
    it('should not run unless started', function () { return __awaiter(void 0, void 0, void 0, function () {
        var request;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    moxios.stubRequest('https://api.test', { status: 200 });
                    apiAuthInterceptor.stop(axiosInstance);
                    return [4 /*yield*/, axiosInstance.get('https://api.test')];
                case 1:
                    _a.sent();
                    request = moxios.requests.mostRecent();
                    expect(request.headers).not.toHaveProperty('Authorization');
                    apiAuthInterceptor.start(axiosInstance);
                    return [4 /*yield*/, axiosInstance.get('https://api.test')];
                case 2:
                    _a.sent();
                    request = moxios.requests.mostRecent();
                    expect(request.headers).toHaveProperty('Authorization');
                    return [2 /*return*/];
            }
        });
    }); });
    it('should use basic auth for HTTPS', function () { return __awaiter(void 0, void 0, void 0, function () {
        var request;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    moxios.stubRequest('https://api.test', { status: 200 });
                    return [4 /*yield*/, axiosInstance.get('https://api.test')];
                case 1:
                    _a.sent();
                    request = moxios.requests.mostRecent();
                    expect(request.headers).toHaveProperty('Authorization');
                    expect(request.headers.Authorization).toBe('Basic ' +
                        Buffer.from('consumer_key:consumer_secret').toString('base64'));
                    return [2 /*return*/];
            }
        });
    }); });
    it('should use OAuth 1.0a for HTTP', function () { return __awaiter(void 0, void 0, void 0, function () {
        var request;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    moxios.stubRequest('http://api.test', { status: 200 });
                    return [4 /*yield*/, axiosInstance.get('http://api.test')];
                case 1:
                    _a.sent();
                    request = moxios.requests.mostRecent();
                    // We're going to assume that the oauth-1.0a package added the signature data correctly so we will
                    // focus on ensuring that the header looks roughly correct given what we readily know.
                    expect(request.headers).toHaveProperty('Authorization');
                    expect(request.headers.Authorization).toMatch(/^OAuth oauth_consumer_key="consumer_key".*oauth_signature_method="HMAC-SHA256".*oauth_version="1.0"/);
                    return [2 /*return*/];
            }
        });
    }); });
    it('should work with base URL', function () { return __awaiter(void 0, void 0, void 0, function () {
        var request;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    moxios.stubRequest('/test', { status: 200 });
                    return [4 /*yield*/, axiosInstance.request({
                            method: 'GET',
                            baseURL: 'https://api.test/',
                            url: '/test',
                        })];
                case 1:
                    _a.sent();
                    request = moxios.requests.mostRecent();
                    expect(request.headers).toHaveProperty('Authorization');
                    expect(request.headers.Authorization).toBe('Basic ' +
                        Buffer.from('consumer_key:consumer_secret').toString('base64'));
                    return [2 /*return*/];
            }
        });
    }); });
});
