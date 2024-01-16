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
const job_processing_1 = require("../job-processing");
const test_environment_1 = require("../test-environment");
jest.mock('../test-environment');
describe('Job Processing', () => {
    describe('getFileChanges', () => {
        it('should do nothing with no CI configs', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                dependencies: [],
            }, {});
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(0);
        }));
        it('should trigger lint job for single node', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "lint" /* JobType.Lint */,
                            changes: [/test.js$/],
                            command: 'test-lint',
                        },
                    ],
                },
                dependencies: [],
            }, {
                test: ['test.js'],
            });
            expect(jobs.lint).toHaveLength(1);
            expect(jobs.lint).toContainEqual({
                projectName: 'test',
                command: 'test-lint',
            });
            expect(jobs.test).toHaveLength(0);
        }));
        it('should not trigger lint job for single node with no changes', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "lint" /* JobType.Lint */,
                            changes: [/test.js$/],
                            command: 'test-lint',
                        },
                    ],
                },
                dependencies: [],
            }, {});
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(0);
        }));
        it('should trigger lint job for project graph', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "lint" /* JobType.Lint */,
                            changes: [/test.js$/],
                            command: 'test-lint',
                        },
                    ],
                },
                dependencies: [
                    {
                        name: 'test-a',
                        path: 'test-a',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "lint" /* JobType.Lint */,
                                    changes: [/test-a.js$/],
                                    command: 'test-lint-a',
                                },
                            ],
                        },
                        dependencies: [],
                    },
                    {
                        name: 'test-b',
                        path: 'test-b',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "lint" /* JobType.Lint */,
                                    changes: [/test-b.js$/],
                                    command: 'test-lint-b',
                                },
                            ],
                        },
                        dependencies: [],
                    },
                ],
            }, {
                test: ['test.js'],
                'test-a': ['test-ignored.js'],
                'test-b': ['test-b.js'],
            });
            expect(jobs.lint).toHaveLength(2);
            expect(jobs.lint).toContainEqual({
                projectName: 'test',
                command: 'test-lint',
            });
            expect(jobs.lint).toContainEqual({
                projectName: 'test-b',
                command: 'test-lint-b',
            });
            expect(jobs.test).toHaveLength(0);
        }));
        it('should trigger lint job for project graph with empty config parent', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                dependencies: [
                    {
                        name: 'test-a',
                        path: 'test-a',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "lint" /* JobType.Lint */,
                                    changes: [/test-a.js$/],
                                    command: 'test-lint-a',
                                },
                            ],
                        },
                        dependencies: [],
                    },
                    {
                        name: 'test-b',
                        path: 'test-b',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "lint" /* JobType.Lint */,
                                    changes: [/test-b.js$/],
                                    command: 'test-lint-b',
                                },
                            ],
                        },
                        dependencies: [],
                    },
                ],
            }, {
                test: ['test.js'],
                'test-a': ['test-a.js'],
                'test-b': ['test-b.js'],
            });
            expect(jobs.lint).toHaveLength(2);
            expect(jobs.lint).toContainEqual({
                projectName: 'test-a',
                command: 'test-lint-a',
            });
            expect(jobs.lint).toContainEqual({
                projectName: 'test-b',
                command: 'test-lint-b',
            });
            expect(jobs.test).toHaveLength(0);
        }));
        it('should trigger test job for single node', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "test" /* JobType.Test */,
                            name: 'Default',
                            changes: [/test.js$/],
                            command: 'test-cmd',
                        },
                    ],
                },
                dependencies: [],
            }, {
                test: ['test.js'],
            });
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(1);
            expect(jobs.test).toContainEqual({
                projectName: 'test',
                name: 'Default',
                command: 'test-cmd',
            });
        }));
        it('should not trigger test job for single node with no changes', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "test" /* JobType.Test */,
                            name: 'Default',
                            changes: [/test.js$/],
                            command: 'test-cmd',
                        },
                    ],
                },
                dependencies: [],
            }, {});
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(0);
        }));
        it('should trigger test job for project graph', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "test" /* JobType.Test */,
                            name: 'Default',
                            changes: [/test.js$/],
                            command: 'test-cmd',
                        },
                    ],
                },
                dependencies: [
                    {
                        name: 'test-a',
                        path: 'test-a',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "test" /* JobType.Test */,
                                    name: 'Default A',
                                    changes: [/test-b.js$/],
                                    command: 'test-cmd-a',
                                },
                            ],
                        },
                        dependencies: [],
                    },
                    {
                        name: 'test-b',
                        path: 'test-b',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "test" /* JobType.Test */,
                                    name: 'Default B',
                                    changes: [/test-b.js$/],
                                    command: 'test-cmd-b',
                                },
                            ],
                        },
                        dependencies: [],
                    },
                ],
            }, {
                test: ['test.js'],
                'test-a': ['test-ignored.js'],
                'test-b': ['test-b.js'],
            });
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(2);
            expect(jobs.test).toContainEqual({
                projectName: 'test',
                name: 'Default',
                command: 'test-cmd',
            });
            expect(jobs.test).toContainEqual({
                projectName: 'test-b',
                name: 'Default B',
                command: 'test-cmd-b',
            });
        }));
        it('should trigger test job for dependent without changes when dependency has matching cascade key', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "test" /* JobType.Test */,
                            name: 'Default',
                            changes: [/test.js$/],
                            command: 'test-cmd',
                            cascadeKeys: ['test'],
                        },
                    ],
                },
                dependencies: [
                    {
                        name: 'test-a',
                        path: 'test-a',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "test" /* JobType.Test */,
                                    name: 'Default A',
                                    changes: [/test-a.js$/],
                                    command: 'test-cmd-a',
                                    cascadeKeys: ['test-a', 'test'],
                                },
                            ],
                        },
                        dependencies: [],
                    },
                ],
            }, {
                'test-a': ['test-a.js'],
            });
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(2);
            expect(jobs.test).toContainEqual({
                projectName: 'test',
                name: 'Default',
                command: 'test-cmd',
            });
            expect(jobs.test).toContainEqual({
                projectName: 'test-a',
                name: 'Default A',
                command: 'test-cmd-a',
            });
        }));
        it('should isolate dependency cascade keys to prevent cross-dependency matching', () => __awaiter(void 0, void 0, void 0, function* () {
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "test" /* JobType.Test */,
                            name: 'Default',
                            changes: [/test.js$/],
                            command: 'test-cmd',
                            cascadeKeys: ['test'],
                        },
                    ],
                },
                dependencies: [
                    {
                        name: 'test-a',
                        path: 'test-a',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "test" /* JobType.Test */,
                                    name: 'Default A',
                                    changes: [/test-a.js$/],
                                    command: 'test-cmd-a',
                                    cascadeKeys: ['test-a', 'test'],
                                },
                            ],
                        },
                        dependencies: [],
                    },
                    {
                        name: 'test-b',
                        path: 'test-b',
                        ciConfig: {
                            jobs: [
                                {
                                    type: "test" /* JobType.Test */,
                                    name: 'Default B',
                                    changes: [/test-b.js$/],
                                    command: 'test-cmd-b',
                                    cascadeKeys: ['test-b', 'test'],
                                },
                            ],
                        },
                        dependencies: [],
                    },
                ],
            }, {
                'test-a': ['test-a.js'],
            });
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(2);
            expect(jobs.test).toContainEqual({
                projectName: 'test',
                name: 'Default',
                command: 'test-cmd',
            });
            expect(jobs.test).toContainEqual({
                projectName: 'test-a',
                name: 'Default A',
                command: 'test-cmd-a',
            });
        }));
        it('should trigger test job for single node and parse test environment config', () => __awaiter(void 0, void 0, void 0, function* () {
            jest.mocked(test_environment_1.parseTestEnvConfig).mockResolvedValue({
                WP_ENV_CORE: 'https://wordpress.org/latest.zip',
            });
            const jobs = yield (0, job_processing_1.createJobsForChanges)({
                name: 'test',
                path: 'test',
                ciConfig: {
                    jobs: [
                        {
                            type: "test" /* JobType.Test */,
                            name: 'Default',
                            changes: [/test.js$/],
                            command: 'test-cmd',
                            testEnv: {
                                start: 'test-start',
                                config: {
                                    wpVersion: 'latest',
                                },
                            },
                        },
                    ],
                },
                dependencies: [],
            }, {
                test: ['test.js'],
            });
            expect(jobs.lint).toHaveLength(0);
            expect(jobs.test).toHaveLength(1);
            expect(jobs.test).toContainEqual({
                projectName: 'test',
                name: 'Default',
                command: 'test-cmd',
                testEnv: {
                    start: 'test-start',
                    envVars: {
                        WP_ENV_CORE: 'https://wordpress.org/latest.zip',
                    },
                },
            });
        }));
    });
});
