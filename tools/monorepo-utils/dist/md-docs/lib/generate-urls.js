"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.generateFileUrl = void 0;
/**
 * External dependencies
 */
const path_1 = __importDefault(require("path"));
/**
 * Generates a file url relative to the root directory provided.
 *
 * @param baseUrl          The base url to use for the file url.
 * @param rootDirectory    The root directory where the file resides.
 * @param subDirectory     The sub-directory where the file resides.
 * @param absoluteFilePath The absolute path to the file.
 * @return The file url.
 */
const generateFileUrl = (baseUrl, rootDirectory, subDirectory, absoluteFilePath) => {
    // check paths are absolute
    for (const filePath of [
        rootDirectory,
        subDirectory,
        absoluteFilePath,
    ]) {
        if (!path_1.default.isAbsolute(filePath)) {
            throw new Error(`File URLs cannot be generated without absolute paths. ${filePath} is not absolute.`);
        }
    }
    // Generate a path from the subdirectory to the file path.
    const relativeFilePath = path_1.default.resolve(subDirectory, absoluteFilePath);
    // Determine the relative path from the rootDirectory to the filePath.
    const relativePath = path_1.default.relative(rootDirectory, relativeFilePath);
    return `${baseUrl}/${relativePath}`;
};
exports.generateFileUrl = generateFileUrl;
