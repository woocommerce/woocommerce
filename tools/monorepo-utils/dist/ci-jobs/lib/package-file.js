"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.loadPackage = void 0;
/**
 * External dependencies
 */
const node_fs_1 = __importDefault(require("node:fs"));
const node_path_1 = __importDefault(require("node:path"));
// We're going to store a cache of package files so that we don't load
// ones that we have already loaded. The key is the normalized path
// to the package file that was loaded.
const packageCache = {};
/**
 * Loads a package file's contents either from the cache or from the file system.
 *
 * @param {string} packagePath The package file to load.
 * @return {Object} The package file's contents.
 */
function loadPackage(packagePath) {
    // Use normalized paths to accomodate any path tokens.
    packagePath = node_path_1.default.normalize(packagePath);
    if (packageCache[packagePath]) {
        return packageCache[packagePath];
    }
    packageCache[packagePath] = JSON.parse(node_fs_1.default.readFileSync(packagePath, 'utf8'));
    return packageCache[packagePath];
}
exports.loadPackage = loadPackage;
