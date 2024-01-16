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
exports.updateTrunkChangelog = exports.updateReleaseBranchChangelogs = void 0;
/**
 * External dependencies
 */
const simple_git_1 = __importDefault(require("simple-git"));
const child_process_1 = require("child_process");
const promises_1 = require("fs/promises");
const path_1 = __importDefault(require("path"));
/**
 * Internal dependencies
 */
const logger_1 = require("../../../../core/logger");
const git_1 = require("../../../../core/git");
const repo_1 = require("../../../../core/github/repo");
const lib_1 = require("../../get-version/lib");
/**
 * Perform changelog adjustments after Jetpack Changelogger has run.
 *
 * @param {string} override    Time override.
 * @param {string} tmpRepoPath Path where the temporary repo is cloned.
 */
const updateReleaseChangelogs = (override, tmpRepoPath) => __awaiter(void 0, void 0, void 0, function* () {
    const today = (0, lib_1.getToday)(override);
    const releaseTime = today.plus({
        days: lib_1.DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
    });
    const releaseDate = releaseTime.toISODate();
    const readmeFile = path_1.default.join(tmpRepoPath, 'plugins', 'woocommerce', 'readme.txt');
    const nextLogFile = path_1.default.join(tmpRepoPath, 'plugins', 'woocommerce', 'NEXT_CHANGELOG.md');
    let readme = yield (0, promises_1.readFile)(readmeFile, 'utf-8');
    let nextLog = yield (0, promises_1.readFile)(nextLogFile, 'utf-8');
    nextLog = nextLog.replace(/= (\d+\.\d+\.\d+) YYYY-mm-dd =/, `= $1 ${releaseDate} =`);
    // Convert PR number to markdown link.
    nextLog = nextLog.replace(/\[#(\d+)\](?!\()/g, '[#$1](https://github.com/woocommerce/woocommerce/pull/$1)');
    readme = readme.replace(/== Changelog ==\n(.*?)\[See changelog for all versions\]/s, `== Changelog ==\n\n${nextLog}\n\n[See changelog for all versions]`);
    yield (0, promises_1.writeFile)(readmeFile, readme);
});
/**
 * Perform changelog operations on release branch by submitting a pull request. The release branch is a remote branch.
 *
 * @param {Object} options       CLI options
 * @param {string} tmpRepoPath   temp repo path
 * @param {string} releaseBranch release branch name. The release branch is a remote branch on Github.
 * @return {Object} update data
 */
const updateReleaseBranchChangelogs = (options, tmpRepoPath, releaseBranch) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name, version, commitDirectToBase } = options;
    try {
        // Do a full checkout so that we can find the correct PR numbers for changelog entries.
        yield (0, git_1.checkoutRemoteBranch)(tmpRepoPath, releaseBranch, false);
    }
    catch (e) {
        if (e.message.includes("couldn't find remote ref")) {
            logger_1.Logger.error(`${releaseBranch} does not exist on ${owner}/${name}.`);
        }
        logger_1.Logger.error(e);
    }
    const git = (0, simple_git_1.default)({
        baseDir: tmpRepoPath,
        config: ['core.hooksPath=/dev/null'],
    });
    const branch = `update/${version}-changelog`;
    try {
        if (!commitDirectToBase) {
            yield git.checkout({
                '-b': null,
                [branch]: null,
            });
        }
        logger_1.Logger.notice(`Running the changelog script in ${tmpRepoPath}`);
        (0, child_process_1.execSync)(`pnpm --filter=@woocommerce/plugin-woocommerce changelog write --add-pr-num -n -vvv --use-version ${version}`, {
            cwd: tmpRepoPath,
            stdio: 'inherit',
        });
        logger_1.Logger.notice(`Committing deleted files in ${tmpRepoPath}`);
        //Checkout pnpm-lock.yaml to prevent issues in case of an out of date lockfile.
        yield git.checkout('pnpm-lock.yaml');
        yield git.add('plugins/woocommerce/changelog/');
        yield git.commit(`Delete changelog files from ${version} release`);
        const deletionCommitHash = yield git.raw(['rev-parse', 'HEAD']);
        logger_1.Logger.notice(`git deletion hash: ${deletionCommitHash}`);
        logger_1.Logger.notice(`Updating readme.txt in ${tmpRepoPath}`);
        yield updateReleaseChangelogs(options.override, tmpRepoPath);
        logger_1.Logger.notice(`Committing readme.txt changes in ${branch} on ${tmpRepoPath}`);
        yield git.add('plugins/woocommerce/readme.txt');
        yield git.commit(`Update the readme files for the ${version} release`);
        yield git.push('origin', commitDirectToBase ? releaseBranch : branch);
        yield git.checkout('.');
        if (commitDirectToBase) {
            logger_1.Logger.notice(`Changelog update was committed directly to ${releaseBranch}`);
            return {
                deletionCommitHash: deletionCommitHash.trim(),
                prNumber: -1,
            };
        }
        logger_1.Logger.notice(`Creating PR for ${branch}`);
        const pullRequest = yield (0, repo_1.createPullRequest)({
            owner,
            name,
            title: `Release: Prepare the changelog for ${version}`,
            body: `This pull request was automatically generated during the code freeze to prepare the changelog for ${version}`,
            head: branch,
            base: releaseBranch,
        });
        logger_1.Logger.notice(`Pull request created: ${pullRequest.html_url}`);
        return {
            deletionCommitHash: deletionCommitHash.trim(),
            prNumber: pullRequest.number,
        };
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.updateReleaseBranchChangelogs = updateReleaseBranchChangelogs;
/**
 * Perform changelog operations on trunk by submitting a pull request.
 *
 * @param {Object} options                                 CLI options
 * @param {string} tmpRepoPath                             temp repo path
 * @param {string} releaseBranch                           release branch name
 * @param {Object} releaseBranchChanges                    update data from updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.deletionCommitHash commit from the changelog deletions in updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.prNumber           pr number created in updateReleaseBranchChangelogs
 */
const updateTrunkChangelog = (options, tmpRepoPath, releaseBranch, releaseBranchChanges) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name, version } = options;
    const { deletionCommitHash, prNumber } = releaseBranchChanges;
    logger_1.Logger.notice(`Deleting changelogs from trunk ${tmpRepoPath}`);
    const git = (0, simple_git_1.default)({
        baseDir: tmpRepoPath,
        config: ['core.hooksPath=/dev/null'],
    });
    try {
        yield git.checkout('trunk');
        const branch = `delete/${version}-changelog`;
        logger_1.Logger.notice(`Committing deletions in ${branch} on ${tmpRepoPath}`);
        yield git.checkout({
            '-b': null,
            [branch]: null,
        });
        yield git.raw(['cherry-pick', deletionCommitHash]);
        yield git.push('origin', branch);
        logger_1.Logger.notice(`Creating PR for ${branch}`);
        const pullRequest = yield (0, repo_1.createPullRequest)({
            owner,
            name,
            title: `Release: Remove ${version} change files`,
            body: `This pull request was automatically generated during the code freeze to remove the changefiles from ${version} that are compiled into the \`${releaseBranch}\` ${prNumber > 0 ? `branch via #${prNumber}` : ''}`,
            head: branch,
            base: 'trunk',
        });
        logger_1.Logger.notice(`Pull request created: ${pullRequest.html_url}`);
    }
    catch (e) {
        logger_1.Logger.error(e);
    }
});
exports.updateTrunkChangelog = updateTrunkChangelog;
