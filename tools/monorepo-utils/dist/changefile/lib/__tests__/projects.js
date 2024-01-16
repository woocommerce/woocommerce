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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
jest.mock('uuid', () => {
    return {
        v4: jest.fn(() => 1),
    };
});
/**
 * External dependencies
 */
const path_1 = __importDefault(require("path"));
/**
 * Internal dependencies
 */
const projects_1 = require("../projects");
const sampleWorkspaceYaml = `
packages:
    - 'folder-with-lots-of-projects/*'
    - 'projects/cool-project'
    - 'projects/very-cool-project'
    - 'interesting-project'
`;
const tmpRepoPath = path_1.default.join(__dirname, 'test-repo');
describe('Changelog project functions', () => {
    it('getAllProjectsPathsFromWorkspace should provide a list of all projects supplied by pnpm-workspace.yml', () => __awaiter(void 0, void 0, void 0, function* () {
        const projects = yield (0, projects_1.getAllProjectsPathsFromWorkspace)(tmpRepoPath, sampleWorkspaceYaml);
        const expectedProjects = [
            'folder-with-lots-of-projects/project-b',
            'folder-with-lots-of-projects/project-a',
            'projects/cool-project',
            'projects/very-cool-project',
            'interesting-project',
        ];
        expectedProjects.forEach((expectedProject) => {
            expect(projects).toContain(expectedProject);
        });
        expect(projects).toHaveLength(expectedProjects.length);
    }));
    it('getChangeloggerProjectPaths should provide a list of all projects that use Jetpack changelogger', () => __awaiter(void 0, void 0, void 0, function* () {
        const projects = yield (0, projects_1.getAllProjectsPathsFromWorkspace)(tmpRepoPath, sampleWorkspaceYaml);
        const changeloggerProjects = yield (0, projects_1.getChangeloggerProjectPaths)(tmpRepoPath, projects);
        const expectedChangeLoggerProjects = [
            'folder-with-lots-of-projects/project-b',
            'folder-with-lots-of-projects/project-a',
            'projects/very-cool-project',
        ];
        expectedChangeLoggerProjects.forEach((expectedChangeLoggerProject) => {
            expect(changeloggerProjects).toContain(expectedChangeLoggerProject);
        });
        expect(changeloggerProjects).toHaveLength(expectedChangeLoggerProjects.length);
    }));
    it('getTouchedChangeloggerProjectsPathsMappedToProjects should combine touched and changelogger projects and return a list that is a subset of both', () => __awaiter(void 0, void 0, void 0, function* () {
        const touchedFiles = [
            'folder-with-lots-of-projects/project-b/src/index.js',
            'projects/very-cool-project/src/index.js',
        ];
        const changeLoggerProjects = [
            'folder-with-lots-of-projects/project-b',
            'folder-with-lots-of-projects/project-a',
            'projects/very-cool-project',
        ];
        const intersectedProjects = (0, projects_1.getTouchedChangeloggerProjectsPathsMappedToProjects)(touchedFiles, changeLoggerProjects);
        expect(intersectedProjects).toMatchObject({
            'folder-with-lots-of-projects/project-b': 'folder-with-lots-of-projects/project-b',
            'projects/very-cool-project': 'projects/very-cool-project',
        });
    }));
    it('getTouchedChangeloggerProjectsPathsMappedToProjects should map plugins and js packages to the correct name', () => __awaiter(void 0, void 0, void 0, function* () {
        const touchedFiles = [
            'plugins/beta-tester/src/index.js',
            'plugins/woocommerce/src/index.js',
            'packages/js/components/src/index.js',
            'packages/js/data/src/index.js',
        ];
        const changeLoggerProjects = [
            'plugins/woocommerce',
            'plugins/beta-tester',
            'packages/js/data',
            'packages/js/components',
        ];
        const intersectedProjects = (0, projects_1.getTouchedChangeloggerProjectsPathsMappedToProjects)(touchedFiles, changeLoggerProjects);
        expect(intersectedProjects).toMatchObject({
            woocommerce: 'plugins/woocommerce',
            'beta-tester': 'plugins/beta-tester',
            '@woocommerce/components': 'packages/js/components',
            '@woocommerce/data': 'packages/js/data',
        });
    }));
    it('getTouchedChangeloggerProjectsPathsMappedToProjects should handle woocommerce-admin projects mapped to woocommerce core', () => __awaiter(void 0, void 0, void 0, function* () {
        const touchedFiles = [
            'plugins/beta-tester/src/index.js',
            'plugins/woocommerce-admin/src/index.js',
        ];
        const changeLoggerProjects = ['plugins/woocommerce'];
        const intersectedProjects = (0, projects_1.getTouchedChangeloggerProjectsPathsMappedToProjects)(touchedFiles, changeLoggerProjects);
        expect(intersectedProjects).toMatchObject({
            woocommerce: 'plugins/woocommerce',
        });
    }));
});
