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
exports.generateManifestCommand = void 0;
/**
 * External dependencies
 */
const fs_1 = require("fs");
const extra_typings_1 = require("@commander-js/extra-typings");
const path_1 = __importDefault(require("path"));
/**
 * Internal dependencies
 */
const generate_manifest_1 = require("../../../lib/generate-manifest");
const logger_1 = require("../../../../core/logger");
const markdown_links_1 = require("../../../lib/markdown-links");
exports.generateManifestCommand = new extra_typings_1.Command('create')
    .description('Create a manifest file representing the contents of a markdown directory.')
    .argument('<directory>', 'Path to directory of Markdown files to generate the manifest from.')
    .argument('<projectName>', 'Name of the project to generate the manifest for, used to uniquely identify manifest entries.')
    .option('-o --outputFilePath <outputFilePath>', 'Full path and filename of where to output the manifest.', `${process.cwd()}/manifest.json`)
    .option('-b --baseUrl <baseUrl>', 'Base url to resolve markdown file URLs to in the manifest.', 'https://raw.githubusercontent.com/woocommerce/woocommerce/trunk')
    .option('-r --rootDir <rootDir>', 'Root directory of the markdown files, used to generate URLs.', process.cwd())
    .option('-be --baseEditUrl <baseEditUrl>', 'Base url to provide edit links to. This option will be ignored if your baseUrl is not a GitHub URL.', 'https://github.com/woocommerce/woocommerce/edit/trunk')
    .action((dir, projectName, options) => __awaiter(void 0, void 0, void 0, function* () {
    const { outputFilePath, baseUrl, rootDir, baseEditUrl } = options;
    // determine if the rootDir is absolute or relative
    const absoluteRootDir = path_1.default.isAbsolute(rootDir)
        ? rootDir
        : path_1.default.join(process.cwd(), rootDir);
    const absoluteSubDir = path_1.default.isAbsolute(dir)
        ? dir
        : path_1.default.join(process.cwd(), dir);
    const absoluteOutputFilePath = path_1.default.isAbsolute(outputFilePath)
        ? outputFilePath
        : path_1.default.join(process.cwd(), outputFilePath);
    logger_1.Logger.startTask('Generating manifest');
    const manifest = yield (0, generate_manifest_1.generateManifestFromDirectory)(absoluteRootDir, absoluteSubDir, projectName, baseUrl, baseEditUrl);
    const manifestWithLinks = yield (0, markdown_links_1.processMarkdownLinks)(manifest, absoluteRootDir, absoluteSubDir, projectName);
    logger_1.Logger.endTask();
    logger_1.Logger.startTask('Writing manifest');
    yield (0, fs_1.writeFile)(absoluteOutputFilePath, JSON.stringify(manifestWithLinks, null, 2), (err) => {
        if (err) {
            logger_1.Logger.error(err);
        }
    });
    logger_1.Logger.endTask();
    logger_1.Logger.notice(`Manifest output at ${outputFilePath}`);
}));
