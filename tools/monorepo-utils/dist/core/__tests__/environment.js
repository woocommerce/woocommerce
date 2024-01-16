"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Internal dependencies
 */
const environment_1 = require("../environment");
describe('isGithubCI', () => {
    it('should return true if GITHUB_ACTIONS is true', () => {
        process.env.GITHUB_ACTIONS = 'true';
        expect((0, environment_1.isGithubCI)()).toBe(true);
    });
    it('should return false if GITHUB_ACTIONS is false', () => {
        process.env.GITHUB_ACTIONS = 'false';
        expect((0, environment_1.isGithubCI)()).toBe(false);
    });
    it('should return false if GITHUB_ACTIONS is not set', () => {
        process.env.GITHUB_ACTIONS = undefined;
        expect((0, environment_1.isGithubCI)()).toBe(false);
    });
    afterAll(() => {
        delete process.env.GITHUB_ACTIONS;
    });
});
