"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const node_child_process_1 = require("node:child_process");
/**
 * Internal dependencies
 */
const file_changes_1 = require("../file-changes");
jest.mock('node:child_process');
describe('File Changes', () => {
    describe('getFileChanges', () => {
        it('should associate git changes with projects', () => {
            jest.mocked(node_child_process_1.execSync).mockImplementation((command) => {
                if (command === 'git diff --name-only origin/trunk') {
                    return `test/project-a/package.json
foo/project-b/foo.js
bar/project-c/bar.js
baz/project-d/baz.js`;
                }
                throw new Error('Invalid command');
            });
            const fileChanges = (0, file_changes_1.getFileChanges)({
                name: 'project-a',
                path: 'test/project-a',
                dependencies: [
                    {
                        name: 'project-b',
                        path: 'foo/project-b',
                        dependencies: [
                            {
                                name: 'project-c',
                                path: 'bar/project-c',
                                dependencies: [],
                            },
                        ],
                    },
                    {
                        name: 'project-c',
                        path: 'bar/project-c',
                        dependencies: [],
                    },
                ],
            }, 'origin/trunk');
            expect(fileChanges).toMatchObject({
                'project-a': ['package.json'],
                'project-b': ['foo.js'],
                'project-c': ['bar.js'],
            });
        });
    });
});
