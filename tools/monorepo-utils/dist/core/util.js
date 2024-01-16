"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.requestAsync = exports.execAsync = void 0;
/**
 * External dependencies
 */
const util_1 = require("util");
const child_process_1 = require("child_process");
const https_1 = require("https");
exports.execAsync = (0, util_1.promisify)(child_process_1.exec);
// A wrapper around https.request that returns a promise encapulating the response body and other response attributes.
const requestAsync = (options, data) => {
    return new Promise((resolve, reject) => {
        const req = (0, https_1.request)(options, (res) => {
            let body = '';
            res.setEncoding('utf8');
            res.on('data', (chunk) => {
                body += chunk;
            });
            res.on('end', () => {
                const httpsResponse = Object.assign(Object.assign({}, res), { body });
                resolve(httpsResponse);
            });
        });
        req.on('error', (err) => {
            reject(err);
        });
        if (data) {
            req.write(data);
        }
        req.end();
    });
};
exports.requestAsync = requestAsync;
