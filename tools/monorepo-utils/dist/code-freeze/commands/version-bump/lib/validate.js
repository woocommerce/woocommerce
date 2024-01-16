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
exports.validateArgs = exports.stripPrereleaseParameters = exports.getCurrentVersion = exports.getIsAccelRelease = void 0;
/**
 * External dependencies
 */
const semver_1 = require("semver");
const path_1 = require("path");
const promises_1 = require("fs/promises");
/**
 * Internal dependencies
 */
const logger_1 = require("../../../../core/logger");
/**
 * Determine whether a version is an accel release.
 *
 * @param {string} version Version number
 * @return {boolean} True if the version corresponds with an accel release, otherwise false
 */
const getIsAccelRelease = (version) => {
    const isAccelRelease = version.match(/^(?:\d+\.){3}\d+?$/);
    return isAccelRelease !== null;
};
exports.getIsAccelRelease = getIsAccelRelease;
/**
 * Get a plugin's current version.
 *
 * @param tmpRepoPath cloned repo path
 */
const getCurrentVersion = (tmpRepoPath) => __awaiter(void 0, void 0, void 0, function* () {
    const filePath = (0, path_1.join)(tmpRepoPath, `plugins/woocommerce/woocommerce.php`);
    try {
        const data = yield (0, promises_1.readFile)(filePath, 'utf8');
        const matches = data.match(/Version:\s*(.*)/);
        return matches ? matches[1] : undefined;
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.getCurrentVersion = getCurrentVersion;
/**
 * When given a prerelease version, return just the version.
 *
 * @param {string} prereleaseVersion version with prerelease params
 * @return {string} version
 */
const stripPrereleaseParameters = (prereleaseVersion) => {
    const parsedVersion = (0, semver_1.parse)(prereleaseVersion);
    if (parsedVersion) {
        const { major, minor, patch } = parsedVersion;
        return `${major}.${minor}.${patch}`;
    }
    return prereleaseVersion;
};
exports.stripPrereleaseParameters = stripPrereleaseParameters;
/**
 * Validate the arguments passed to the version bump command.
 *
 * @param tmpRepoPath cloned repo path
 * @param version     version to bump to
 * @param options     options passed to the command
 */
const validateArgs = (tmpRepoPath, version, options) => __awaiter(void 0, void 0, void 0, function* () {
    const { allowAccel, base, force } = options;
    const nextVersion = version;
    const isAllowedAccelRelease = allowAccel && (0, exports.getIsAccelRelease)(nextVersion);
    if (isAllowedAccelRelease) {
        if (base === 'trunk') {
            logger_1.Logger.error(`Version ${nextVersion} is not a development version bump and cannot be applied to trunk, which only accepts development version bumps.`);
        }
    }
    else {
        if (!(0, semver_1.valid)(nextVersion)) {
            logger_1.Logger.error('Invalid version supplied, please pass in a semantically correct version or use the correct option for accel releases.');
        }
        const prereleaseParameters = (0, semver_1.prerelease)(nextVersion);
        const isDevVersionBump = prereleaseParameters && prereleaseParameters[0] === 'dev';
        if (!isDevVersionBump && base === 'trunk') {
            logger_1.Logger.error(`Version ${nextVersion} is not a development version bump and cannot be applied to trunk, which only accepts development version bumps.`);
        }
    }
    if (force) {
        // When the force option is set, we do not compare currentVersion.
        return;
    }
    const currentVersion = yield (0, exports.getCurrentVersion)(tmpRepoPath);
    if (!currentVersion) {
        logger_1.Logger.error('Unable to determine current version');
    }
    else if ((0, semver_1.lt)(nextVersion, currentVersion)) {
        // Semver thinks -a.1 is less than -dev, but -a.1 from -dev will be a valid version bump.
        if (nextVersion.includes('a.') &&
            currentVersion.includes('dev')) {
            return;
        }
        logger_1.Logger.error('The version supplied is less than the current version, please supply a valid version.');
    }
});
exports.validateArgs = validateArgs;
