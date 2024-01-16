"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const path_1 = __importDefault(require("path"));
/**
 * Internal dependencies
 */
const generate_urls_1 = require("../generate-urls");
describe('generateFileUrl', () => {
    it('should generate a file url relative to the root directory provided', () => {
        const url = (0, generate_urls_1.generateFileUrl)('https://example.com', path_1.default.join(__dirname, 'fixtures/example-docs'), path_1.default.join(__dirname, 'fixtures/example-docs/get-started'), path_1.default.join(__dirname, 'fixtures/example-docs/get-started/local-development.md'));
        expect(url).toBe('https://example.com/get-started/local-development.md');
    });
    it('should throw an error if relative paths are passed', () => {
        expect(() => (0, generate_urls_1.generateFileUrl)('https://example.com', './example-docs', path_1.default.join(__dirname, 'fixtures/example-docs/get-started'), path_1.default.join(__dirname, 'fixtures/example-docs/get-started/local-development.md'))).toThrow();
        expect(() => (0, generate_urls_1.generateFileUrl)('https://example.com', path_1.default.join(__dirname, 'fixtures/example-docs'), './get-started', path_1.default.join(__dirname, 'fixtures/example-docs/get-started/local-development.md'))).toThrow();
        expect(() => (0, generate_urls_1.generateFileUrl)('https://example.com', path_1.default.join(__dirname, 'fixtures/example-docs'), path_1.default.join(__dirname, 'fixtures/example-docs/get-started'), './local-development.md')).toThrow();
    });
});
