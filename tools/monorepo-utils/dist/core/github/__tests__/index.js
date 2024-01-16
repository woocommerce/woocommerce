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
 * Internal dependencies
 */
const repo_1 = require("../repo");
jest.mock('../api', () => {
    return {
        graphqlWithAuth: () => jest.fn().mockResolvedValue({
            repository: {
                releases: {
                    nodes: [
                        {
                            tagName: 'nightly',
                            isLatest: false,
                        },
                        {
                            tagName: 'wc-beta-tester-99.99.0',
                            isLatest: false,
                        },
                        {
                            tagName: '1.0.0',
                            isLatest: false,
                        },
                        {
                            tagName: '1.1.0',
                            isLatest: false,
                        },
                        {
                            tagName: '1.2.0',
                            isLatest: false,
                        },
                        {
                            tagName: '2.0.0',
                            isLatest: false,
                        },
                        {
                            tagName: '2.0.1',
                            isLatest: true,
                        },
                        {
                            tagName: '1.0.1',
                            isLatest: false,
                        },
                    ],
                },
            },
        }),
    };
});
it('should return the latest release version', () => __awaiter(void 0, void 0, void 0, function* () {
    expect(yield (0, repo_1.getLatestGithubReleaseVersion)({
        owner: 'woocommerce',
        name: 'woocommerce',
    })).toBe('2.0.1');
}));
