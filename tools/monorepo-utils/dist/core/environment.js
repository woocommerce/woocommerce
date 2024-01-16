"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.isGithubCI = exports.getEnvVar = void 0;
/**
 * Internal dependencies
 */
const logger_1 = require("./logger");
const getEnvVar = (varName, isRequired = false) => {
    const value = process.env[varName];
    if (value === undefined && isRequired) {
        logger_1.Logger.error(`You need to provide a value for ${varName} in your environment either via an environment variable or the .env file.`);
    }
    return value || '';
};
exports.getEnvVar = getEnvVar;
const isGithubCI = () => {
    return process.env.GITHUB_ACTIONS === 'true';
};
exports.isGithubCI = isGithubCI;
