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
/**
 * External dependencies
 */
const node_http_1 = require("node:http");
const node_stream_1 = require("node:stream");
/**
 * Internal dependencies
 */
const test_environment_1 = require("../test-environment");
jest.mock('node:http');
describe('Test Environment', () => {
    describe('parseTestEnvConfig', () => {
        it('should parse empty configs', () => __awaiter(void 0, void 0, void 0, function* () {
            const envVars = yield (0, test_environment_1.parseTestEnvConfig)({});
            expect(envVars).toEqual({});
        }));
        describe('wpVersion', () => {
            // We're going to mock an implementation of the request to the WordPress.org API.
            // This simulates what happens when we call https.get() for it.
            jest.mocked(node_http_1.get).mockImplementation((url, callback) => {
                if (url !== 'http://api.wordpress.org/core/stable-check/1.0/') {
                    throw new Error('Invalid URL');
                }
                const getStream = new node_stream_1.Stream();
                // Let the consumer set up listeners for the stream.
                callback(getStream);
                const wpVersions = {
                    '5.9': 'insecure',
                    '6.0': 'insecure',
                    '6.0.1': 'insecure',
                    '6.1': 'insecure',
                    '6.1.1': 'insecure',
                    '6.1.2': 'outdated',
                    '6.2': 'latest',
                };
                getStream.emit('data', JSON.stringify(wpVersions));
                getStream.emit('end'); // this will trigger the promise resolve
                return jest.fn();
            });
            it('should parse "master" and "trunk" branches', () => __awaiter(void 0, void 0, void 0, function* () {
                let envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: 'master',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'WordPress/WordPress#master',
                });
                envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: 'trunk',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'WordPress/WordPress#master',
                });
            }));
            it('should parse nightlies', () => __awaiter(void 0, void 0, void 0, function* () {
                const envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: 'nightly',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'https://wordpress.org/nightly-builds/wordpress-latest.zip',
                });
            }));
            it('should parse latest', () => __awaiter(void 0, void 0, void 0, function* () {
                const envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: 'latest',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'https://wordpress.org/latest.zip',
                });
            }));
            it('should parse specific minor version', () => __awaiter(void 0, void 0, void 0, function* () {
                const envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: '5.9.0',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'https://wordpress.org/wordpress-5.9.zip',
                });
            }));
            it('should parse specific patch version', () => __awaiter(void 0, void 0, void 0, function* () {
                const envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: '6.0.1',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'https://wordpress.org/wordpress-6.0.1.zip',
                });
            }));
            it('should throw for version that does not exist', () => __awaiter(void 0, void 0, void 0, function* () {
                const expectation = () => (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: '1.0',
                });
                expect(expectation).rejects.toThrowError(/Failed to parse WP version/);
            }));
            it('should parse latest offset', () => __awaiter(void 0, void 0, void 0, function* () {
                const envVars = yield (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: 'latest-1',
                });
                expect(envVars).toEqual({
                    WP_ENV_CORE: 'https://wordpress.org/wordpress-6.1.2.zip',
                });
            }));
            it('should throw for latest offset that does not exist', () => __awaiter(void 0, void 0, void 0, function* () {
                const expectation = () => (0, test_environment_1.parseTestEnvConfig)({
                    wpVersion: 'latest-10',
                });
                expect(expectation).rejects.toThrowError(/Failed to parse WP version/);
            }));
        });
    });
});
