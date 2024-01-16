"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const node_child_process_1 = require("node:child_process");
const node_fs_1 = __importDefault(require("node:fs"));
/**
 * Internal dependencies
 */
const config_1 = require("../config");
const package_file_1 = require("../package-file");
const project_graph_1 = require("../project-graph");
jest.mock('node:child_process');
jest.mock('../config');
jest.mock('../package-file');
describe('Project Graph', () => {
    describe('buildProjectGraph', () => {
        it('should build graph from pnpm list', () => {
            jest.mocked(node_child_process_1.execSync).mockImplementation((command) => {
                if (command === 'pnpm -w root') {
                    return '/test/monorepo/node_modules';
                }
                if (command === 'pnpm -r list --only-projects --json') {
                    return node_fs_1.default.readFileSync(__dirname + '/test-pnpm-list.json');
                }
                throw new Error('Invalid command');
            });
            jest.mocked(package_file_1.loadPackage).mockImplementation((path) => {
                if (!path.endsWith('package.json')) {
                    throw new Error('Invalid path');
                }
                const matches = path.match(/\/([^/]+)\/package.json$/);
                return {
                    name: matches[1],
                };
            });
            jest.mocked(config_1.parseCIConfig).mockImplementation((packageFile) => {
                expect(packageFile).toMatchObject({
                    name: expect.stringMatching(/project-[abcd]/),
                });
                return { jobs: [] };
            });
            const graph = (0, project_graph_1.buildProjectGraph)();
            expect(package_file_1.loadPackage).toHaveBeenCalled();
            expect(config_1.parseCIConfig).toHaveBeenCalled();
            expect(graph).toMatchObject({
                name: 'project-a',
                path: 'project-a',
                ciConfig: {
                    jobs: [],
                },
                dependencies: [
                    {
                        name: 'project-b',
                        path: 'project-b',
                        ciConfig: {
                            jobs: [],
                        },
                        dependencies: [
                            {
                                name: 'project-c',
                                path: 'project-c',
                                ciConfig: {
                                    jobs: [],
                                },
                                dependencies: [],
                            },
                        ],
                    },
                    {
                        name: 'project-c',
                        path: 'project-c',
                        ciConfig: {
                            jobs: [],
                        },
                        dependencies: [],
                    },
                    {
                        name: 'project-d',
                        path: 'project-d',
                        ciConfig: {
                            jobs: [],
                        },
                        dependencies: [
                            {
                                name: 'project-c',
                                path: 'project-c',
                                ciConfig: {
                                    jobs: [],
                                },
                                dependencies: [],
                            },
                        ],
                    },
                ],
            });
        });
    });
});
