"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
jest.spyOn(global.console, 'error').mockImplementation(() => { });
// @ts-expect-error -- We're mocking process exit, it has never return type!
jest.spyOn(global.process, 'exit').mockImplementation(() => { });
/**
 * External dependencies
 */
const chalk_1 = __importDefault(require("chalk"));
/**
 * Internal dependencies
 */
const logger_1 = require("../logger");
describe('Logger', () => {
    afterEach(() => {
        jest.resetAllMocks();
    });
    describe('error', () => {
        process.env.LOGGER_LEVEL = 'error';
        it('should log a message for string messages', () => {
            const message = 'test message';
            logger_1.Logger.error(message);
            expect(global.console.error).toHaveBeenCalledWith(chalk_1.default.red(message));
        });
        it('should log a message for errors', () => {
            const error = new Error('test error');
            logger_1.Logger.error(error);
            expect(global.console.error).toHaveBeenCalledWith(chalk_1.default.red(`${error.message}\n${error.stack}`));
        });
        it('should json stringify for unknown types', () => {
            logger_1.Logger.error({ foo: 'bar' });
            expect(global.console.error).toHaveBeenCalledWith(chalk_1.default.red(JSON.stringify({ foo: 'bar' }, null, 2)));
        });
        it('should call process.exit by default', () => {
            logger_1.Logger.error('test message');
            expect(global.process.exit).toHaveBeenCalledWith(1);
        });
        it('should not call process.exit when failOnErr is false', () => {
            logger_1.Logger.error('test message', false);
            expect(global.process.exit).not.toHaveBeenCalled();
        });
        it('should not log errors if the Logger is in silent mode', () => {
            process.env.LOGGER_LEVEL = 'silent';
            logger_1.Logger.error('test message');
            expect(global.console.error).not.toHaveBeenCalled();
        });
    });
});
