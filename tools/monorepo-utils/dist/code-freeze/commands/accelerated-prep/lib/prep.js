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
exports.createChangelog = exports.addHeader = void 0;
/**
 * External dependencies
 */
const promises_1 = require("fs/promises");
const path_1 = require("path");
/**
 * Internal dependencies
 */
const logger_1 = require("../../../../core/logger");
/**
 * Add Woo header to main plugin file.
 *
 * @param tmpRepoPath cloned repo path
 */
const addHeader = (tmpRepoPath) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, 'plugins/woocommerce/woocommerce.php');
    try {
        const pluginFileContents = yield (0, promises_1.readFile)(filePath, 'utf8');
        const updatedPluginFileContents = pluginFileContents.replace(' * @package WooCommerce\n */', ' *\n * Woo: 18734002369816:624a1b9ba2fe66bb06d84bcdd401c6a6\n *\n * @package WooCommerce\n */');
        yield (0, promises_1.writeFile)(filePath, updatedPluginFileContents);
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.addHeader = addHeader;
/**
 * Create changelog file.
 *
 * @param tmpRepoPath cloned repo path
 * @param version     version for the changelog file
 * @param date        date of the release (Y-m-d)
 */
const createChangelog = (tmpRepoPath, version, date) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, 'plugins/woocommerce/changelog.txt');
    try {
        const changelogContents = `*** WooCommerce ***

${date} - Version ${version}
* Update - Deploy of WooCommerce ${version}
`;
        yield (0, promises_1.writeFile)(filePath, changelogContents);
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.createChangelog = createChangelog;
