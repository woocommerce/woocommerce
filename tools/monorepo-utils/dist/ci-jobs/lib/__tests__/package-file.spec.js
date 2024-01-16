"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const node_fs_1 = __importDefault(require("node:fs"));
/**
 * Internal dependencies
 */
const package_file_1 = require("../package-file");
jest.mock('node:fs');
describe('Package File', () => {
    describe('loadPackage', () => {
        it("should throw for file that doesn't exist", () => {
            jest.mocked(node_fs_1.default.readFileSync).mockImplementation((path) => {
                if (path === 'foo') {
                    throw new Error('ENOENT');
                }
                return '';
            });
            expect(() => (0, package_file_1.loadPackage)('foo')).toThrow('ENOENT');
        });
        it('should load package.json', () => {
            jest.mocked(node_fs_1.default.readFileSync).mockImplementationOnce((path) => {
                if (path === __dirname + '/test-package.json') {
                    return JSON.stringify({
                        name: 'foo',
                    });
                }
                throw new Error('ENOENT');
            });
            const loadedFile = (0, package_file_1.loadPackage)(__dirname + '/test-package.json');
            expect(loadedFile).toMatchObject({
                name: 'foo',
            });
        });
        it('should cache using normalized paths', () => {
            jest.mocked(node_fs_1.default.readFileSync).mockImplementationOnce((path) => {
                if (path === __dirname + '/test-package.json') {
                    return JSON.stringify({
                        name: 'foo',
                    });
                }
                throw new Error('ENOENT');
            });
            (0, package_file_1.loadPackage)(__dirname + '/test-package.json');
            // Just throw if it's called again so that we can make sure we're using the cache.
            jest.mocked(node_fs_1.default.readFileSync).mockImplementationOnce(() => {
                throw new Error('ENOENT');
            });
            const cachedFile = (0, package_file_1.loadPackage)(
            // Use a token that needs to be normalized to match the cached path.
            __dirname + '/./test-package.json');
            expect(cachedFile).toMatchObject({
                name: 'foo',
            });
        });
    });
});
