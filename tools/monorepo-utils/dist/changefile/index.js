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
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const simple_git_1 = __importDefault(require("simple-git"));
const path_1 = __importDefault(require("path"));
const fs_1 = require("fs");
/**
 * Internal dependencies
 */
const logger_1 = require("../core/logger");
const environment_1 = require("../core/environment");
const git_1 = require("../core/git");
const github_1 = require("./lib/github");
const projects_1 = require("./lib/projects");
const program = new extra_typings_1.Command('changefile')
    .description('Changelog utilities')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-d --dev-repo-path <devRepoPath>', 'Path to existing repo. Use this option to avoid cloning a fresh repo for development purposes. Note that using this option assumes dependencies are already installed.')
    .argument('<pr-number>', 'Pull request number')
    .action((prNumber, options) => __awaiter(void 0, void 0, void 0, function* () {
    var _a, _b;
    const { owner, name, devRepoPath } = options;
    logger_1.Logger.startTask(`Getting pull request data for PR number ${prNumber}`);
    const { prBody, headOwner, branch, fileName, head, base } = yield (0, github_1.getPullRequestData)({ owner, name }, prNumber);
    logger_1.Logger.endTask();
    if (!(0, github_1.shouldAutomateChangelog)(prBody)) {
        logger_1.Logger.notice(`PR #${prNumber} does not have the "Automatically create a changelog entry from the details" checkbox checked. No changelog will be created.`);
        process.exit(0);
    }
    const details = (0, github_1.getChangelogDetails)(prBody);
    const { significance, type, message, comment } = details;
    const changelogDetailsError = (0, github_1.getChangelogDetailsError)(details);
    if (changelogDetailsError) {
        logger_1.Logger.error(changelogDetailsError);
    }
    logger_1.Logger.startTask(`Making a temporary clone of '${headOwner}/${name}'`);
    const tmpRepoPath = devRepoPath
        ? devRepoPath
        : yield (0, git_1.cloneAuthenticatedRepo)({ owner: headOwner, name }, false);
    logger_1.Logger.endTask();
    logger_1.Logger.notice(`Temporary clone of '${headOwner}/${name}' created at ${tmpRepoPath}`);
    // If a pull request is coming from a contributor's fork's trunk branch, we don't nee to checkout the remote branch because its already available as part of the clone.
    if (branch !== 'trunk') {
        logger_1.Logger.notice(`Checking out remote branch ${branch}`);
        yield (0, git_1.checkoutRemoteBranch)(tmpRepoPath, branch, false);
    }
    logger_1.Logger.notice(`Getting all touched projects requiring a changelog`);
    const touchedProjectsRequiringChangelog = yield (0, projects_1.getTouchedProjectsRequiringChangelog)(tmpRepoPath, base, head, fileName, owner, name);
    try {
        const allProjectPaths = yield (0, projects_1.getAllProjectPaths)(tmpRepoPath);
        logger_1.Logger.notice('Removing existing changelog files in case a change is reverted and the entry is no longer needed');
        allProjectPaths.forEach((projectPath) => {
            var _a, _b;
            const composerFilePath = path_1.default.join(tmpRepoPath, projectPath, 'composer.json');
            if (!(0, fs_1.existsSync)(composerFilePath)) {
                return;
            }
            // Figure out where the changelog files belong for this project.
            const composerFile = JSON.parse((0, fs_1.readFileSync)(composerFilePath, {
                encoding: 'utf-8',
            }));
            const changelogFilePath = path_1.default.join(tmpRepoPath, projectPath, (_b = (_a = composerFile.extra) === null || _a === void 0 ? void 0 : _a.changelogger['changes-dir']) !== null && _b !== void 0 ? _b : 'changelog', fileName);
            if (!(0, fs_1.existsSync)(changelogFilePath)) {
                return;
            }
            logger_1.Logger.notice(`Remove existing changelog file ${changelogFilePath}`);
            (0, fs_1.rmSync)(changelogFilePath);
        });
        if (!touchedProjectsRequiringChangelog) {
            logger_1.Logger.notice('No projects require a changelog');
            process.exit(0);
        }
        for (const project in touchedProjectsRequiringChangelog) {
            const projectPath = path_1.default.join(tmpRepoPath, touchedProjectsRequiringChangelog[project]);
            logger_1.Logger.notice(`Generating changefile for ${project} (${projectPath}))`);
            // Figure out where the changelog file belongs for this project.
            const composerFile = JSON.parse((0, fs_1.readFileSync)(path_1.default.join(projectPath, 'composer.json'), { encoding: 'utf-8' }));
            const changelogFilePath = path_1.default.join(projectPath, (_b = (_a = composerFile.extra) === null || _a === void 0 ? void 0 : _a.changelogger['changes-dir']) !== null && _b !== void 0 ? _b : 'changelog', fileName);
            // Write the changefile using the correct format.
            let fileContent = `Significance: ${significance}\n`;
            fileContent += `Type: ${type}\n`;
            if (comment) {
                fileContent += `Comment: ${comment}\n`;
            }
            fileContent += `\n${message}`;
            (0, fs_1.writeFileSync)(changelogFilePath, fileContent);
        }
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
    const touchedProjectsString = Object.keys(touchedProjectsRequiringChangelog).join(', ');
    logger_1.Logger.notice(`Changelogs created for ${touchedProjectsString}`);
    const git = (0, simple_git_1.default)({
        baseDir: tmpRepoPath,
        config: ['core.hooksPath=/dev/null'],
    });
    if ((0, environment_1.isGithubCI)()) {
        yield git.raw('config', '--global', 'user.email', 'github-actions@github.com');
        yield git.raw('config', '--global', 'user.name', 'github-actions');
    }
    const shortStatus = yield git.raw(['status', '--short']);
    if (shortStatus.length === 0) {
        logger_1.Logger.notice(`No changes in changelog files. Skipping commit and push.`);
        process.exit(0);
    }
    logger_1.Logger.notice(`Adding and committing changes`);
    yield git.add('.');
    yield git.commit(`Add changefile(s) from automation for the following project(s): ${touchedProjectsString}`);
    yield git.push('origin', branch);
    logger_1.Logger.notice(`Pushed changes to ${branch}`);
}));
exports.default = program;
