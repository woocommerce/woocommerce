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
const generate_manifest_1 = require("../generate-manifest");
const markdown_links_1 = require("../markdown-links");
describe('processMarkdownLinks', () => {
    const dir = path_1.default.join(__dirname, './fixtures/example-docs');
    const rootDir = path_1.default.join(__dirname, './fixtures');
    it('should add the correct relative links to a manifest', () => __awaiter(void 0, void 0, void 0, function* () {
        // generate the manifest from fixture directory
        const manifest = yield (0, generate_manifest_1.generateManifestFromDirectory)(rootDir, dir, 'example-docs', 'https://example.com', 'https://example.com/edit');
        const manifestWithLinks = yield (0, markdown_links_1.processMarkdownLinks)(manifest, rootDir, dir, 'example-docs');
        const localDevelopmentPost = manifestWithLinks.categories[0].posts[0];
        expect(localDevelopmentPost.links['./installation/install-plugin.md']).toBeDefined();
        const installationPost = manifestWithLinks.categories[0].categories[0].posts[0];
        expect(localDevelopmentPost.links['./installation/install-plugin.md']).toEqual(installationPost.id);
    }));
});
