"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getMajorMinor = exports.WPIncrement = void 0;
/**
 * External dependencies
 */
const semver_1 = require("semver");
/**
 * Bumps the version according to WP rules.
 *
 * @param {string} version Version to increment
 * @return {string} Incremented version
 */
const WPIncrement = (version) => {
    const parsedVersion = (0, semver_1.parse)(version);
    return (0, semver_1.inc)(parsedVersion, parsedVersion.minor === 9 ? 'major' : 'minor');
};
exports.WPIncrement = WPIncrement;
/**
 * Gets the major-minor of a given version number.
 *
 * @param {string} version Version to gather major minor from.
 * @return {string} major minor
 */
const getMajorMinor = (version) => {
    const parsedVersion = (0, semver_1.parse)(version);
    return `${parsedVersion.major}.${parsedVersion.minor}`;
};
exports.getMajorMinor = getMajorMinor;
