"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.processMarkdownLinks = void 0;
/**
 * External dependencies
 */
const path_1 = __importDefault(require("path"));
const fs_1 = __importDefault(require("fs"));
/**
 * Internal dependencies
 */
const generate_manifest_1 = require("./generate-manifest");
/**
 * Process relative markdown links in the manifest.
 *
 * @param manifest       Category or Post
 * @param rootDirectory  Root directory of the project
 * @param absoluteSubDir Path to directory of Markdown files to generate the manifest from.
 * @param projectName    Name of the project
 */
const processMarkdownLinks = (manifest, rootDirectory, absoluteSubDir, projectName) => {
    const updatedManifest = Object.assign({}, manifest);
    if (updatedManifest.posts) {
        updatedManifest.posts = updatedManifest.posts.map((post) => {
            const updatedPost = Object.assign({}, post);
            const filePath = path_1.default.resolve(rootDirectory, updatedPost.filePath);
            const fileContent = fs_1.default.readFileSync(filePath, 'utf-8');
            const linkRegex = /\[(?:.*?)\]\((.*?)\)/g;
            let match;
            while ((match = linkRegex.exec(fileContent))) {
                const relativePath = match[1];
                const absoluteLinkedFilePath = path_1.default.resolve(path_1.default.dirname(filePath), relativePath);
                const relativeLinkedFilePath = path_1.default.relative(absoluteSubDir, absoluteLinkedFilePath);
                if (fs_1.default.existsSync(absoluteLinkedFilePath)) {
                    const linkedId = (0, generate_manifest_1.generatePostId)(relativeLinkedFilePath, projectName);
                    updatedPost.links = updatedPost.links || {};
                    updatedPost.links[relativePath] = linkedId;
                }
            }
            // dont expose filePath on updated posts
            delete updatedPost.filePath;
            return updatedPost;
        });
    }
    if (updatedManifest.categories) {
        updatedManifest.categories = updatedManifest.categories.map((category) => (0, exports.processMarkdownLinks)(category, rootDirectory, absoluteSubDir, projectName));
    }
    return updatedManifest;
};
exports.processMarkdownLinks = processMarkdownLinks;
