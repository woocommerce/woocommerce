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
exports.parseTestEnvConfig = void 0;
/**
 * External dependencies
 */
const node_http_1 = __importDefault(require("node:http"));
/**
 * Gets all of the available WordPress versions and their associated stability.
 *
 * @return {Promise.<Object>} The response from the WordPress.org API.
 */
function getWordPressVersions() {
    return new Promise((resolve, reject) => {
        // We're going to use the WordPress.org API to get information about available versions of WordPress.
        const request = node_http_1.default.get('http://api.wordpress.org/core/stable-check/1.0/', (response) => {
            // Listen for the response data.
            let responseData = '';
            response.on('data', (chunk) => {
                responseData += chunk;
            });
            // Once we have the entire response we can process it.
            response.on('end', () => resolve(JSON.parse(responseData)));
        });
        request.on('error', (error) => {
            reject(error);
        });
    });
}
/**
 * Uses the WordPress API to get the download URL to the latest version of an X.X version line. This
 * also accepts "latest-X" to get an offset from the latest version of WordPress.
 *
 * @param {string} wpVersion The version of WordPress to look for.
 * @return {Promise.<string>} The precise WP version download URL.
 */
function getPreciseWPVersionURL(wpVersion) {
    return __awaiter(this, void 0, void 0, function* () {
        const allVersions = yield getWordPressVersions();
        // If we're requesting a "latest" offset then we need to figure out what version line we're offsetting from.
        const latestSubMatch = wpVersion.match(/^latest(?:-([0-9]+))?$/i);
        if (latestSubMatch) {
            for (const version in allVersions) {
                if (allVersions[version] !== 'latest') {
                    continue;
                }
                // We don't care about the patch version because we will
                // the latest version from the version line below.
                const versionParts = version.match(/^([0-9]+)\.([0-9]+)/);
                // We're going to subtract the offset to figure out the right version.
                let offset = latestSubMatch[1]
                    ? parseInt(latestSubMatch[1], 10)
                    : 0;
                let majorVersion = parseInt(versionParts[1], 10);
                let minorVersion = parseInt(versionParts[2], 10);
                while (offset > 0) {
                    minorVersion--;
                    if (minorVersion < 0) {
                        majorVersion--;
                        minorVersion = 9;
                    }
                    offset--;
                }
                // Set the version that we found in the offset.
                wpVersion = majorVersion + '.' + minorVersion;
            }
        }
        // Scan through all of the versions to find the latest version in the version line.
        let latestVersion = null;
        let latestPatch = -1;
        for (const v in allVersions) {
            // Parse the version so we can make sure we're looking for the latest.
            const matches = v.match(/([0-9]+)\.([0-9]+)(?:\.([0-9]+))?/);
            // We only care about the correct minor version.
            const minor = `${matches[1]}.${matches[2]}`;
            if (minor !== wpVersion) {
                continue;
            }
            // Track the latest version in the line.
            const patch = matches[3] === undefined ? 0 : parseInt(matches[3], 10);
            if (patch > latestPatch) {
                latestPatch = patch;
                latestVersion = v;
            }
        }
        if (!latestVersion) {
            throw new Error(`Unable to find latest version for version line ${wpVersion}.`);
        }
        return `https://wordpress.org/wordpress-${latestVersion}.zip`;
    });
}
/**
 * Parses a display-friendly WordPress version and returns a link to download the given version.
 *
 * @param {string} wpVersion A display-friendly WordPress version. Supports ("master", "trunk", "nightly", "latest", "latest-X", "X.X" for version lines, and "X.X.X" for specific versions)
 * @return {Promise.<string>} A link to download the given version of WordPress.
 */
function parseWPVersion(wpVersion) {
    return __awaiter(this, void 0, void 0, function* () {
        // Allow for download URLs in place of a version.
        if (wpVersion.match(/[a-z]+:\/\//i)) {
            return wpVersion;
        }
        // Start with versions we can infer immediately.
        switch (wpVersion) {
            case 'master':
            case 'trunk': {
                return 'WordPress/WordPress#master';
            }
            case 'nightly': {
                return 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
            }
            case 'latest': {
                return 'https://wordpress.org/latest.zip';
            }
        }
        // We can also infer X.X.X versions immediately.
        const parsedVersion = wpVersion.match(/^([0-9]+)\.([0-9]+)\.([0-9]+)$/);
        if (parsedVersion) {
            // Note that X.X.0 versions use a X.X download URL.
            let urlVersion = `${parsedVersion[1]}.${parsedVersion[2]}`;
            if (parsedVersion[3] !== '0') {
                urlVersion += `.${parsedVersion[3]}`;
            }
            return `https://wordpress.org/wordpress-${urlVersion}.zip`;
        }
        // Since we haven't found a URL yet we're going to use the WordPress.org API to try and infer one.
        return getPreciseWPVersionURL(wpVersion);
    });
}
/**
 * Parses the test environment's configuration and returns any environment variables that
 * should be set.
 *
 * @param {Object} config The test environment configuration.
 * @return {Promise.<Object>} The environment variables for the test environment.
 */
function parseTestEnvConfig(config) {
    return __awaiter(this, void 0, void 0, function* () {
        const envVars = {};
        // Convert `wp-env` configuration options to environment variables.
        if (config.wpVersion) {
            try {
                envVars.WP_ENV_CORE = yield parseWPVersion(config.wpVersion);
            }
            catch (error) {
                throw new Error(`Failed to parse WP version: ${error.message}.`);
            }
        }
        if (config.phpVersion) {
            envVars.WP_ENV_PHP_VERSION = config.phpVersion;
        }
        return envVars;
    });
}
exports.parseTestEnvConfig = parseTestEnvConfig;
