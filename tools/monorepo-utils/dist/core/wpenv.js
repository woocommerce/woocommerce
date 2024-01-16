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
exports.stopWPEnv = exports.startWPEnv = exports.isWPEnvPortTaken = void 0;
/**
 * External dependencies
 */
const net_1 = require("net");
const path_1 = require("path");
/**
 * Internal dependencies
 */
const util_1 = require("./util");
/**
 * Determine if the default port for wp-env is already taken. If so, see
 * https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#2-check-the-port-number
 * for alternatives.
 *
 * @return {Promise<boolean>} if the port is being currently used.
 */
const isWPEnvPortTaken = () => {
    return new Promise((resolve, reject) => {
        const test = (0, net_1.createServer)()
            .once('error', (err) => {
            return err.code === 'EADDRINUSE'
                ? resolve(true)
                : reject(err);
        })
            .once('listening', () => {
            return test.once('close', () => resolve(false)).close();
        })
            .listen('8888');
    });
};
exports.isWPEnvPortTaken = isWPEnvPortTaken;
/**
 * Start wp-env.
 *
 * @param {string}   tmpRepoPath - path to the temporary repo to start wp-env from.
 * @param {Function} error       - error print method.
 * @return {boolean} if starting the container succeeded.
 */
const startWPEnv = (tmpRepoPath, error) => __awaiter(void 0, void 0, void 0, function* () {
    try {
        // Stop wp-env if its already running.
        yield (0, util_1.execAsync)('wp-env stop', {
            cwd: (0, path_1.join)(tmpRepoPath, 'plugins/woocommerce'),
            encoding: 'utf-8',
        });
    }
    catch (e) {
        // If an error is produced here, it means wp-env is not initialized and therefore not running already.
    }
    try {
        if (yield (0, exports.isWPEnvPortTaken)()) {
            throw new Error('Unable to start wp-env. Make sure port 8888 is available or specify port number WP_ENV_PORT in .wp-env.override.json');
        }
        yield (0, util_1.execAsync)('wp-env start', {
            cwd: (0, path_1.join)(tmpRepoPath, 'plugins/woocommerce'),
            encoding: 'utf-8',
        });
        return true;
    }
    catch (e) {
        let message = '';
        if (e instanceof Error) {
            message = e.message;
            error(message);
        }
        return false;
    }
});
exports.startWPEnv = startWPEnv;
/**
 * Stop wp-env.
 *
 * @param {string}   tmpRepoPath - path to the temporary repo to stop wp-env from.
 * @param {Function} error       - error print method.
 * @return {boolean} if stopping the container succeeded.
 */
const stopWPEnv = (tmpRepoPath, error) => __awaiter(void 0, void 0, void 0, function* () {
    try {
        yield (0, util_1.execAsync)('wp-env stop', {
            cwd: (0, path_1.join)(tmpRepoPath, 'plugins/woocommerce'),
            encoding: 'utf-8',
        });
        return true;
    }
    catch (e) {
        let message = '';
        if (e instanceof Error) {
            message = e.message;
            error(message);
        }
        return false;
    }
});
exports.stopWPEnv = stopWPEnv;
