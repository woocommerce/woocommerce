"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Logger = void 0;
/**
 * External dependencies
 */
const ora_1 = __importDefault(require("ora"));
const chalk_1 = __importDefault(require("chalk"));
const cli_table_1 = __importDefault(require("cli-table"));
/**
 * Internal dependencies
 */
const environment_1 = require("./environment");
const LOGGING_LEVELS = {
    verbose: 3,
    warn: 2,
    error: 1,
    silent: 0,
};
const { log, error, warn } = console;
class Logger {
    static get loggingLevel() {
        return LOGGING_LEVELS[(0, environment_1.getEnvVar)('LOGGER_LEVEL') || 'warn'];
    }
    static error(err, failOnErr = true) {
        if (Logger.loggingLevel >= LOGGING_LEVELS.error) {
            if (err instanceof Error) {
                error(chalk_1.default.red(`${err.message}\n${err.stack}`));
            }
            else if (typeof err === 'string') {
                error(chalk_1.default.red(err));
            }
            else {
                // Best effort to log the error when we don't know the type.
                error(chalk_1.default.red(JSON.stringify(err, null, 2)));
            }
            if (failOnErr) {
                process.exit(1);
            }
        }
    }
    static warn(message) {
        if (Logger.loggingLevel >= LOGGING_LEVELS.warn) {
            warn(chalk_1.default.yellow(message));
        }
    }
    static notice(message) {
        if (Logger.loggingLevel > LOGGING_LEVELS.silent) {
            log(chalk_1.default.green(message));
        }
    }
    static startTask(message) {
        if (Logger.loggingLevel > LOGGING_LEVELS.silent && !(0, environment_1.isGithubCI)()) {
            const spinner = (0, ora_1.default)(chalk_1.default.green(`${message}...`)).start();
            Logger.lastSpinner = spinner;
        }
        else if ((0, environment_1.isGithubCI)()) {
            Logger.notice(message);
        }
    }
    static table(head, rows) {
        if (Logger.loggingLevel > LOGGING_LEVELS.silent) {
            const table = new cli_table_1.default({ head, rows });
            log(table.toString());
        }
    }
    static endTask() {
        if (Logger.loggingLevel > LOGGING_LEVELS.silent &&
            Logger.lastSpinner &&
            !(0, environment_1.isGithubCI)()) {
            Logger.lastSpinner.succeed(`${Logger.lastSpinner.text} complete.`);
            Logger.lastSpinner = null;
        }
        else if ((0, environment_1.isGithubCI)()) {
            Logger.notice('Task complete.');
        }
    }
}
exports.Logger = Logger;
