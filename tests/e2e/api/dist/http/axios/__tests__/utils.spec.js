"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var utils_1 = require("../utils");
describe('buildURL', function () {
    it('should use base when given no url', function () {
        var url = utils_1.buildURL({ baseURL: 'http://test.test' });
        expect(url).toBe('http://test.test');
    });
    it('should use url when given absolute', function () {
        var url = utils_1.buildURL({ baseURL: 'http://test.test', url: 'http://override.test' });
        expect(url).toBe('http://override.test');
    });
    it('should combine base and url', function () {
        var url = utils_1.buildURL({ baseURL: 'http://test.test', url: 'yes/test' });
        expect(url).toBe('http://test.test/yes/test');
    });
});
describe('buildURLWithParams', function () {
    it('should do nothing without query string', function () {
        var url = utils_1.buildURLWithParams({ baseURL: 'http://test.test' });
        expect(url).toBe('http://test.test');
    });
    it('should append query string', function () {
        var url = utils_1.buildURLWithParams({ baseURL: 'http://test.test', params: { test: 'yes' } });
        expect(url).toBe('http://test.test?test=yes');
    });
});
