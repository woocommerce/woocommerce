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
Object.defineProperty(exports, "__esModule", { value: true });
exports.updatePluginFile = exports.updateJSON = exports.updateClassPluginFile = exports.updateReadmeChangelog = void 0;
/**
 * External dependencies
 */
const promises_1 = require("fs/promises");
const fs_1 = require("fs");
const path_1 = require("path");
/**
 * Internal dependencies
 */
const logger_1 = require("../../../../core/logger");
/**
 * Update plugin readme changelog.
 *
 * @param tmpRepoPath cloned repo path
 * @param nextVersion version to bump to
 */
const updateReadmeChangelog = (tmpRepoPath, nextVersion) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, 'plugins/woocommerce/readme.txt');
    try {
        const readmeContents = yield (0, promises_1.readFile)(filePath, 'utf8');
        const updatedReadmeContents = readmeContents.replace(/= \d+\.\d+\.\d+ \d\d\d\d-XX-XX =\n/m, `= ${nextVersion} ${new Date().getFullYear()}-XX-XX =\n`);
        yield (0, promises_1.writeFile)(filePath, updatedReadmeContents);
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.updateReadmeChangelog = updateReadmeChangelog;
/**
 * Update plugin class file.
 *
 * @param tmpRepoPath cloned repo path
 * @param nextVersion version to bump to
 */
const updateClassPluginFile = (tmpRepoPath, nextVersion) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, `plugins/woocommerce/includes/class-woocommerce.php`);
    if (!(0, fs_1.existsSync)(filePath)) {
        logger_1.Logger.error("File 'class-woocommerce.php' does not exist.");
    }
    try {
        const classPluginFileContents = yield (0, promises_1.readFile)(filePath, 'utf8');
        const updatedClassPluginFileContents = classPluginFileContents.replace(/public \$version = '\d+\.\d+\.\d+';\n/m, `public $version = '${nextVersion}';\n`);
        yield (0, promises_1.writeFile)(filePath, updatedClassPluginFileContents);
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.updateClassPluginFile = updateClassPluginFile;
/**
 * Update plugin JSON files.
 *
 * @param {string} type        plugin to update
 * @param {string} tmpRepoPath cloned repo path
 * @param {string} nextVersion version to bump to
 */
const updateJSON = (type, tmpRepoPath, nextVersion) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, `plugins/woocommerce/${type}.json`);
    try {
        const composerJson = JSON.parse(yield (0, promises_1.readFile)(filePath, 'utf8'));
        composerJson.version = nextVersion;
        yield (0, promises_1.writeFile)(filePath, JSON.stringify(composerJson, null, '\t') + '\n');
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.updateJSON = updateJSON;
/**
 * Update plugin main file.
 *
 * @param tmpRepoPath cloned repo path
 * @param nextVersion version to bump to
 */
const updatePluginFile = (tmpRepoPath, nextVersion) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, `plugins/woocommerce/woocommerce.php`);
    try {
        const pluginFileContents = yield (0, promises_1.readFile)(filePath, 'utf8');
        const updatedPluginFileContents = pluginFileContents.replace(/Version: \d+\.\d+\.\d+.*\n/m, `Version: ${nextVersion}\n`);
        yield (0, promises_1.writeFile)(filePath, updatedPluginFileContents);
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.updatePluginFile = updatePluginFile;
