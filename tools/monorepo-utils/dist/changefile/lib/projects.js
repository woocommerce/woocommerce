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
exports.getTouchedProjectsRequiringChangelog = exports.getAllProjectPaths = exports.getTouchedChangeloggerProjectsPathsMappedToProjects = exports.getTouchedFilePaths = exports.getChangeloggerProjectPaths = exports.getAllProjectsPathsFromWorkspace = void 0;
/**
 * External dependencies
 */
const fs_1 = require("fs");
const promises_1 = require("fs/promises");
const path_1 = __importDefault(require("path"));
const glob_1 = require("glob");
const simple_git_1 = __importDefault(require("simple-git"));
/**
 * Internal dependencies
 */
const git_1 = require("../../core/git");
/**
 * Get all projects listed in the workspace yaml file.
 *
 * @param {string} tmpRepoPath   Path to the temporary repository.
 * @param {string} workspaceYaml Contents of the workspace yaml file.
 * @return {Array<string>} List of projects.
 */
const getAllProjectsPathsFromWorkspace = (tmpRepoPath, workspaceYaml) => __awaiter(void 0, void 0, void 0, function* () {
    const rawProjects = workspaceYaml.split('- ');
    // remove heading
    rawProjects.shift();
    const globbedProjects = yield Promise.all(rawProjects
        .map((project) => project.replace(/'/g, '').trim())
        .map((project) => __awaiter(void 0, void 0, void 0, function* () {
        if (project.includes('*')) {
            return yield (0, glob_1.glob)(project, { cwd: tmpRepoPath });
        }
        return project;
    })));
    const r = globbedProjects.flat();
    return r;
});
exports.getAllProjectsPathsFromWorkspace = getAllProjectsPathsFromWorkspace;
/**
 *	Get all projects that have Jetpack changelogger enabled
 *
 * @param {string}        tmpRepoPath Path to the temporary repository.
 * @param {Array<string>} projects    all projects listed in the workspace yaml file
 * @return {Array<string>} List of projects that have Jetpack changelogger enabled.
 */
const getChangeloggerProjectPaths = (tmpRepoPath, projects) => __awaiter(void 0, void 0, void 0, function* () {
    const projectsWithComposer = projects.filter((project) => {
        return (0, fs_1.existsSync)(`${tmpRepoPath}/${project}/composer.json`);
    });
    return projectsWithComposer.filter((project) => {
        const composer = JSON.parse((0, fs_1.readFileSync)(`${tmpRepoPath}/${project}/composer.json`, 'utf8'));
        return ((composer.require &&
            composer.require['automattic/jetpack-changelogger']) ||
            (composer['require-dev'] &&
                composer['require-dev']['automattic/jetpack-changelogger']));
    });
});
exports.getChangeloggerProjectPaths = getChangeloggerProjectPaths;
/**
 * Get an array of all files changed in a PR.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @param {string} base        base hash
 * @param {string} head        head hash
 * @param {string} fileName    changelog file name
 * @param {string} baseOwner   PR base owner
 * @param {string} baseName    PR base name
 * @return {Array<string>} List of files changed in a PR.
 */
const getTouchedFilePaths = (tmpRepoPath, base, head, fileName, baseOwner, baseName) => __awaiter(void 0, void 0, void 0, function* () {
    const git = (0, simple_git_1.default)({
        baseDir: tmpRepoPath,
        config: ['core.hooksPath=/dev/null'],
    });
    // make sure base sha is available.
    yield git.addRemote(baseOwner, (0, git_1.getAuthenticatedRemote)({ owner: baseOwner, name: baseName }));
    yield git.fetch(baseOwner, base);
    const diff = yield git.raw([
        'diff',
        '--name-only',
        `${base}...${head}`,
    ]);
    return (diff
        .split('\n')
        .map((item) => item.trim())
        // Don't count changelogs themselves as touched files.
        .filter((item) => !item.includes(`/changelog/${fileName}`)));
});
exports.getTouchedFilePaths = getTouchedFilePaths;
/**
 * Get an array of projects that have Jetpack changelogger enabled and have files changed in a PR. This function also maps names of projects that have been renamed in the monorepo from their paths.
 *
 * @param {Array<string>} touchedFiles         List of files changed in a PR. touchedFiles
 * @param {Array<string>} changeloggerProjects List of projects that have Jetpack changelogger enabled.
 * @return {Object.<string, string>} Paths to projects that have files changed in a PR keyed by the project name.
 */
const getTouchedChangeloggerProjectsPathsMappedToProjects = (touchedFiles, changeloggerProjects) => {
    const mappedTouchedFiles = touchedFiles.map((touchedProject) => {
        if (touchedProject.includes('plugins/woocommerce-admin')) {
            return touchedProject.replace('plugins/woocommerce-admin', 'plugins/woocommerce');
        }
        return touchedProject;
    });
    const touchedProjectPathsRequiringChangelog = changeloggerProjects.filter((project) => {
        return mappedTouchedFiles.some((touchedProject) => touchedProject.includes(project + '/'));
    });
    const projectPaths = {};
    for (const projectPath of touchedProjectPathsRequiringChangelog) {
        let project = projectPath;
        if (project.includes('plugins/')) {
            project = project.replace('plugins/', '');
        }
        else if (project.includes('packages/js/')) {
            project = project.replace('packages/js/', '@woocommerce/');
        }
        projectPaths[project] = projectPath;
    }
    return projectPaths;
};
exports.getTouchedChangeloggerProjectsPathsMappedToProjects = getTouchedChangeloggerProjectsPathsMappedToProjects;
/**
 * Get all projects listed in the workspace yaml file.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @return {Array<string>} List of projects.
 */
const getAllProjectPaths = (tmpRepoPath) => __awaiter(void 0, void 0, void 0, function* () {
    const workspaceYaml = yield (0, promises_1.readFile)(path_1.default.join(tmpRepoPath, 'pnpm-workspace.yaml'), 'utf8');
    return yield (0, exports.getAllProjectsPathsFromWorkspace)(tmpRepoPath, workspaceYaml);
});
exports.getAllProjectPaths = getAllProjectPaths;
/**
 * Get an array of projects that have Jetpack changelogger enabled and have files changed in a PR.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @param {string} base        base hash
 * @param {string} head        head hash
 * @param {string} fileName    changelog file name
 * @param {string} baseOwner   PR base owner
 * @param {string} baseName    PR base name
 * @return {Object.<string, string>} Paths to projects that have files changed in a PR keyed by the project name.
 */
const getTouchedProjectsRequiringChangelog = (tmpRepoPath, base, head, fileName, baseOwner, baseName) => __awaiter(void 0, void 0, void 0, function* () {
    const allProjectPaths = yield (0, exports.getAllProjectPaths)(tmpRepoPath);
    const changeloggerProjectsPaths = yield (0, exports.getChangeloggerProjectPaths)(tmpRepoPath, allProjectPaths);
    const touchedFilePaths = yield (0, exports.getTouchedFilePaths)(tmpRepoPath, base, head, fileName, baseOwner, baseName);
    return (0, exports.getTouchedChangeloggerProjectsPathsMappedToProjects)(touchedFilePaths, changeloggerProjectsPaths);
});
exports.getTouchedProjectsRequiringChangelog = getTouchedProjectsRequiringChangelog;
