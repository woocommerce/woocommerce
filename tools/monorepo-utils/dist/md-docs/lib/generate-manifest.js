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
exports.generateManifestFromDirectory = exports.generatePostId = void 0;
/**
 * External dependencies
 */
const fs_1 = __importDefault(require("fs"));
const path_1 = __importDefault(require("path"));
const glob_1 = require("glob");
const crypto_1 = __importDefault(require("crypto"));
/**
 * Internal dependencies
 */
const generate_frontmatter_1 = require("./generate-frontmatter");
const generate_urls_1 = require("./generate-urls");
function generatePostId(filePath, prefix = '') {
    const hash = crypto_1.default.createHash('sha1');
    hash.update(`${prefix}/${filePath}`);
    return hash.digest('hex');
}
exports.generatePostId = generatePostId;
function filenameMatches(filename, hayStack) {
    const found = hayStack.filter((item) => filename.match(item));
    return found.length > 0;
}
function processDirectory(rootDirectory, subDirectory, projectName, baseUrl, baseEditUrl, fullPathToDocs, exclude, checkReadme = true) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function* () {
        const category = {};
        // Process README.md (if exists) for the category definition.
        const readmePath = path_1.default.join(subDirectory, 'README.md');
        if (checkReadme) {
            if (fs_1.default.existsSync(readmePath)) {
                const readmeContent = fs_1.default.readFileSync(readmePath, 'utf-8');
                const frontMatter = (0, generate_frontmatter_1.generatePostFrontMatter)(readmeContent, true);
                category.content = frontMatter.content;
                category.category_slug = frontMatter.category_slug;
                category.category_title = frontMatter.category_title;
                category.menu_title = frontMatter.menu_title;
            }
            // derive the category title from the directory name, capitalize first letter of each word.
            const categoryFolder = path_1.default.basename(subDirectory);
            const categoryTitle = categoryFolder
                .split('-')
                .map((slugPart) => slugPart.charAt(0).toUpperCase() + slugPart.slice(1))
                .join(' ');
            category.category_slug = (_a = category.category_slug) !== null && _a !== void 0 ? _a : categoryFolder;
            category.category_title = (_b = category.category_title) !== null && _b !== void 0 ? _b : categoryTitle;
        }
        const markdownFiles = glob_1.glob
            .sync(path_1.default.join(subDirectory, '*.md'))
            .filter((markdownFile) => !filenameMatches(markdownFile, exclude));
        // If there are markdown files in this directory, add a posts array to the category. Otherwise, assume its a top level category that will contain subcategories.
        if (markdownFiles.length > 0) {
            category.posts = [];
        }
        markdownFiles.forEach((filePath) => {
            if (filePath !== readmePath || !checkReadme) {
                // Skip README.md which we have already processed.
                const fileContent = fs_1.default.readFileSync(filePath, 'utf-8');
                const fileFrontmatter = (0, generate_frontmatter_1.generatePostFrontMatter)(fileContent);
                if (baseUrl.includes('github')) {
                    fileFrontmatter.edit_url = (0, generate_urls_1.generateFileUrl)(baseEditUrl, rootDirectory, subDirectory, filePath);
                }
                const post = Object.assign({}, fileFrontmatter);
                // Generate hash of the post contents.
                post.hash = crypto_1.default
                    .createHash('sha256')
                    .update(JSON.stringify(fileContent))
                    .digest('hex');
                // get the folder name of rootDirectory.
                const relativePath = path_1.default.relative(fullPathToDocs, filePath);
                category.posts.push(Object.assign(Object.assign({}, post), { url: (0, generate_urls_1.generateFileUrl)(baseUrl, rootDirectory, subDirectory, filePath), filePath, id: generatePostId(relativePath, projectName) }));
            }
        });
        // Recursively process subdirectories.
        category.categories = [];
        const subdirectories = fs_1.default
            .readdirSync(subDirectory, { withFileTypes: true })
            .filter((dirent) => dirent.isDirectory())
            .filter((dirent) => !filenameMatches(dirent.name, exclude))
            .map((dirent) => path_1.default.join(subDirectory, dirent.name));
        for (const subdirectory of subdirectories) {
            const subcategory = yield processDirectory(rootDirectory, subdirectory, projectName, baseUrl, baseEditUrl, fullPathToDocs, exclude);
            category.categories.push(subcategory);
        }
        return category;
    });
}
function generateManifestFromDirectory(rootDirectory, subDirectory, projectName, baseUrl, baseEditUrl) {
    return __awaiter(this, void 0, void 0, function* () {
        const fullPathToDocs = subDirectory;
        const manifestIgnore = path_1.default.join(subDirectory, '.manifestignore');
        let ignoreList;
        if (fs_1.default.existsSync(manifestIgnore)) {
            ignoreList = fs_1.default
                .readFileSync(manifestIgnore, 'utf-8')
                .split('\n')
                .map((item) => item.trim())
                .filter((item) => item.length > 0)
                .filter((item) => item.substring(0, 1) !== '#');
        }
        const manifest = yield processDirectory(rootDirectory, subDirectory, projectName, baseUrl, baseEditUrl, fullPathToDocs, ignoreList !== null && ignoreList !== void 0 ? ignoreList : [], false);
        // Generate hash of the manifest contents.
        const hash = crypto_1.default
            .createHash('sha256')
            .update(JSON.stringify(manifest))
            .digest('hex');
        return Object.assign(Object.assign({}, manifest), { hash });
    });
}
exports.generateManifestFromDirectory = generateManifestFromDirectory;
