"use strict";
/* eslint-disable @typescript-eslint/no-explicit-any */
Object.defineProperty(exports, "__esModule", { value: true });
exports.parseCIConfig = exports.ConfigError = void 0;
/**
 * A configuration error type.
 */
class ConfigError extends Error {
}
exports.ConfigError = ConfigError;
/**
 * Parses and validates a raw change config entry.
 *
 * @param {string|string[]} raw The raw config to parse.
 */
function parseChangesConfig(raw) {
    if (typeof raw === 'string') {
        return [new RegExp(raw)];
    }
    if (!Array.isArray(raw)) {
        throw new ConfigError('Changes configuration must be a string or array of strings.');
    }
    const changes = [];
    for (const entry of raw) {
        if (typeof entry !== 'string') {
            throw new ConfigError('Changes configuration must be a string or array of strings.');
        }
        changes.push(new RegExp(entry));
    }
    return changes;
}
/**
 * Parses the lint job configuration.
 *
 * @param {Object} raw The raw config to parse.
 */
function parseLintJobConfig(raw) {
    if (!raw.changes) {
        throw new ConfigError('A "changes" option is required for the lint job.');
    }
    if (!raw.command || typeof raw.command !== 'string') {
        throw new ConfigError('A string "command" option is required for the lint job.');
    }
    return {
        type: "lint" /* JobType.Lint */,
        changes: parseChangesConfig(raw.changes),
        command: raw.command,
    };
}
/**
 * Parses the test env config vars.
 *
 * @param {Object} raw The raw config to parse.
 */
function parseTestEnvConfigVars(raw) {
    const config = {};
    if (raw.wpVersion) {
        if (typeof raw.wpVersion !== 'string') {
            throw new ConfigError('The "wpVersion" option must be a string.');
        }
        config.wpVersion = raw.wpVersion;
    }
    if (raw.phpVersion) {
        if (typeof raw.phpVersion !== 'string') {
            throw new ConfigError('The "phpVersion" option must be a string.');
        }
        config.phpVersion = raw.phpVersion;
    }
    return config;
}
/**
 * parses the cascade config.
 *
 * @param {string|string[]} raw The raw config to parse.
 */
function parseTestCascade(raw) {
    if (typeof raw === 'string') {
        return [raw];
    }
    if (!Array.isArray(raw)) {
        throw new ConfigError('Cascade configuration must be a string or array of strings.');
    }
    const changes = [];
    for (const entry of raw) {
        if (typeof entry !== 'string') {
            throw new ConfigError('Cascade configuration must be a string or array of strings.');
        }
        changes.push(entry);
    }
    return changes;
}
/**
 * Parses the test job config.
 *
 * @param {Object} raw The raw config to parse.
 */
function parseTestJobConfig(raw) {
    if (!raw.name || typeof raw.name !== 'string') {
        throw new ConfigError('A string "name" option is required for test jobs.');
    }
    if (!raw.changes) {
        throw new ConfigError('A "changes" option is required for the test jobs.');
    }
    if (!raw.command || typeof raw.command !== 'string') {
        throw new ConfigError('A string "command" option is required for the test jobs.');
    }
    const config = {
        type: "test" /* JobType.Test */,
        name: raw.name,
        changes: parseChangesConfig(raw.changes),
        command: raw.command,
    };
    if (raw.testEnv) {
        if (typeof raw.testEnv !== 'object') {
            throw new ConfigError('The "testEnv" option must be an object.');
        }
        if (!raw.testEnv.start || typeof raw.testEnv.start !== 'string') {
            throw new ConfigError('A string "start" option is required for test environments.');
        }
        config.testEnv = {
            start: raw.testEnv.start,
            config: parseTestEnvConfigVars(raw.testEnv.config),
        };
    }
    if (raw.cascade) {
        config.cascadeKeys = parseTestCascade(raw.cascade);
    }
    return config;
}
/**
 * Parses the raw CI config.
 *
 * @param {Object} raw The raw config.
 */
function parseCIConfig(raw) {
    var _a;
    const config = {
        jobs: [],
    };
    const ciConfig = (_a = raw.config) === null || _a === void 0 ? void 0 : _a.ci;
    if (!ciConfig) {
        return config;
    }
    if (ciConfig.lint) {
        if (typeof ciConfig.lint !== 'object') {
            throw new ConfigError('The "lint" option must be an object.');
        }
        config.jobs.push(parseLintJobConfig(ciConfig.lint));
    }
    if (ciConfig.tests) {
        if (!Array.isArray(ciConfig.tests)) {
            throw new ConfigError('The "tests" option must be an array.');
        }
        for (const rawTestConfig of ciConfig.tests) {
            config.jobs.push(parseTestJobConfig(rawTestConfig));
        }
    }
    return config;
}
exports.parseCIConfig = parseCIConfig;
